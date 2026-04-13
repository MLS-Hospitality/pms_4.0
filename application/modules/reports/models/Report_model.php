<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class report_model extends CI_Model {


	public function getlist($customer=NULL,$status=NULL,$payment_status=NULL,$fromdate=NULL,$todate=NULL)
	{
		$this->db->select('booked_info.*,booked_details.*,roomdetails.roomtype');
        $this->db->from('booked_info');
		$this->db->join('roomdetails','roomdetails.roomid=booked_info.roomid','left');
		$this->db->join('booked_details','booked_details.bookedid=booked_info.bookedid','left');
		if($fromdate != NULL && $fromdate != ''){
			$this->db->where('booked_info.checkindate >=', $fromdate);
		}
		if($todate != NULL && $todate != ''){
			$this->db->where('booked_info.checkoutdate <=', $todate);
		}
		if($status != NULL){
			$this->db->where('booked_info.bookingstatus',$status);
		}
		if($customer != NULL){
			$this->db->where('booked_info.cutomerid',$customer);
		}
        $this->db->order_by('booked_info.bookedid', 'desc');
        $query = $this->db->get();
		$scharge = $this->settinginfo();
		$charge = $scharge->servicecharge;
		$paymentarray = array();
        if ($query->num_rows() > 0) {
            $result = $query->result();
			foreach($result as $k => $r){
				$start = strtotime($r->checkindate);
				$end = strtotime($r->checkoutdate);
				$datediff = $end - $start;
				$days = ceil($datediff / (60 * 60 * 24));
				$result[$k]->roomtype = $this->room_type($r->roomid);
				if($r->booked_from==1 && $r->bookingstatus==0){
					$result[$k]->total_price = $r->total_price;
					if($result[$k]->total_price>$result[$k]->paid_amount && $payment_status==3){
						$paymentarray[$k] = $result[$k];
					}
					else if($result[$k]->total_price<=$result[$k]->paid_amount && $payment_status==1){
						$paymentarray[$k] = $result[$k];
					}
				}else{
					$roomId = !empty($r->roomid) ? explode(",",$r->roomid) : array();
					$rent = !empty($r->roomrate) ? explode(",",$r->roomrate) : array();
					$offer_discount = !empty($r->offer_discount) ? explode(",",$r->offer_discount) : array();
					$totalrent=0;
					$totaloffer=0;
					$rentCount = count($rent);
					for($i=0;$i<$rentCount; $i++){
						$discount = isset($offer_discount[$i]) ? floatval($offer_discount[$i]) : 0;
						$rentValue = isset($rent[$i]) ? floatval($rent[$i]) : 0;
						$totalrent += $rentValue - $discount;
						$totaloffer += $discount;
					}
					$promocode = 0;
					if(!empty($r->promocode)){
						$pdiscount = $this->db->select("discount")->from("promocode")->where("promocode", $r->promocode)->get()->row();
						$promocode = !empty($pdiscount) ? floatval($pdiscount->discount ?? 0) : 0;
					}
					if($r->bookingstatus!=5){
						$serviceCharge = (!empty($charge) && floatval($charge) > 0) ? (floatval($days) * ((floatval($totalrent) * floatval($charge)) / 100)) : 0;
						$result[$k]->total_price = floatval($days) * floatval($r->total_price ?? 0) + floatval($promocode) + $serviceCharge;
						$result[$k]->paid_amount = floatval($r->paid_amount ?? 0);
					}
					if($r->bookingstatus==5){
						$creditamt = $this->db->select("rate,credit,complementary,extrabpc,additional_charges,additional_charges,ex_discount,swimming_pool,restaurant,hallroom,car_parking,special_discount,scharge")->from("tbl_postedbills")->where("bookedid",$r->bookedid)->get()->row();
						// CRITICAL FIX: Check if $creditamt is not null before accessing properties
						if(!empty($creditamt)){
							$result[$k]->creditamt = floatval($creditamt->credit ?? 0);
							$result[$k]->total_price = floatval($creditamt->complementary ?? 0) + floatval($creditamt->extrabpc ?? 0) + floatval($days) * floatval($r->total_price ?? 0) + floatval($promocode) + floatval($creditamt->scharge ?? 0) + floatval($creditamt->additional_charges ?? 0) - floatval($creditamt->ex_discount ?? 0) + floatval($creditamt->swimming_pool ?? 0) + floatval($creditamt->restaurant ?? 0) + floatval($creditamt->hallroom ?? 0) + floatval($creditamt->car_parking ?? 0) - floatval($creditamt->special_discount ?? 0);
							$serviceCharge = (!empty($charge) && floatval($charge) > 0) ? (floatval($days) * ((floatval($totalrent) * floatval($charge)) / 100)) : 0;
							$result[$k]->paid_amount = floatval($r->paid_amount ?? 0) + $serviceCharge + floatval($creditamt->restaurant ?? 0) + floatval($creditamt->hallroom ?? 0) + floatval($creditamt->car_parking ?? 0) - floatval($creditamt->ex_discount ?? 0) - floatval($creditamt->special_discount ?? 0);
							if(floatval($creditamt->credit ?? 0)>0 && $payment_status==2){
								$paymentarray[$k] = $result[$k];
							}else if(floatval($creditamt->credit ?? 0)==0 && $r->bookingstatus==5 && $payment_status==1){
								$paymentarray[$k] = $result[$k];
							}
							if($totaloffer>0){
								$datediff = strtotime($r->checkoutdate) - strtotime($r->checkindate);
								$datediff = ceil($datediff/(60*60*24));
								$singletax = explode(",", $creditamt->rate ?? '');
								$total=0;
								for($li = 0; $li < count($rent); $li++){
									for($in = 0; $in < $datediff; $in++){
										$alldays= date("Y-m-d", strtotime($r->checkindate . ' + ' . $in . 'day'));
										$getroom=$this->db->select("*")->from('tbl_room_offer')->where('roomid',$roomId[$li])->where('offer_date',$alldays)->get()->row();
										if(!empty($getroom)){
											$singleDiscount=$getroom->offer;
											$roomrate=$rent[$li]-$singleDiscount;
											}
										else{
											$roomrate=$rent[$li];
											}
										$price=$roomrate;
										$total=$total+$price;
									}
								}
								$toaltax=0;
								for($j=0; $j<count($singletax); $j++){
									$toaltax += (floatval($total) * floatval($singletax[$j] ?? 0)) / 100;
								}
								$result[$k]->total_price = floatval($creditamt->complementary ?? 0) + floatval($creditamt->extrabpc ?? 0) + floatval($total) + floatval($toaltax) + floatval($promocode) + floatval($creditamt->scharge ?? 0) + floatval($creditamt->additional_charges ?? 0) - floatval($creditamt->ex_discount ?? 0) + floatval($creditamt->swimming_pool ?? 0) + floatval($creditamt->restaurant ?? 0) + floatval($creditamt->hallroom ?? 0) + floatval($creditamt->car_parking ?? 0) - floatval($creditamt->special_discount ?? 0);
							}
						} else {
							// If no postedbills record exists, use default calculation
							$result[$k]->creditamt = 0;
							$serviceCharge = (!empty($charge) && floatval($charge) > 0) ? (floatval($days) * ((floatval($totalrent) * floatval($charge)) / 100)) : 0;

							// Base rent after discount
							$baseRentAfterDiscount = $baseRent - $discountAmount - $promocode;
							$baseRentAfterDiscount = max(0, $baseRentAfterDiscount);

							// Total = (Base Rent - Discount - Promocode) + Tax + Service Charge
							$result[$k]->total_price = $baseRentAfterDiscount + $totaltax + $serviceCharge;
							$result[$k]->paid_amount = floatval($r->paid_amount ?? 0);
						}
					}else{
						if($result[$k]->total_price>$result[$k]->paid_amount && $payment_status==3){
							$paymentarray[$k] = $result[$k];
						}
						else if($result[$k]->total_price<=$result[$k]->paid_amount && $payment_status==1){
							$paymentarray[$k] = $result[$k];
						}
					}
				}
			}
			if(!empty($payment_status)){
				return $paymentarray;
			}
			return $result;
        }
        return false;

	}
	public function getstocklist()
	{

		$this->db->select("products.product_name,unit_of_measurement.uom_name,unit_of_measurement.uom_short_code,purchase_details.*,SUM(purchase_details.quantity) as qty,SUM(purchase_details.price) as sumprice");
		$this->db->from('purchase_details');
		$this->db->join('products','products.id = purchase_details.proid', 'left');
		$this->db->join('unit_of_measurement','unit_of_measurement.id = products.uom_id', 'Inner');
		$this->db->group_by('purchase_details.proid');
		$this->db->order_by('purchase_details.purchaseid','desc');
		$query = $this->db->get();
		return $query->result();

	}

	public function details($id)
	{

		$this->db->select('booked_info.*,GROUP_CONCAT(roomdetails.roomtype ORDER BY booked_info.roomid,roomdetails.roomtype) as roomtype,roomdetails.rate');
        $this->db->from('booked_info');
		$this->db->join('tbl_roomnofloorassign','FIND_IN_SET(tbl_roomnofloorassign.roomno,booked_info.room_no)<>0','left');
		$this->db->join('roomdetails','FIND_IN_SET(roomdetails.roomid,tbl_roomnofloorassign.roomid)<>0','left');
		$this->db->where('booked_info.bookedid',$id);
		$query = $this->db->get();
        if ($query->num_rows() > 0) {
             return $query->row();
        }
        return false;

	}

	public function customerinfo($cid){
			$this->db->select('*');
			$this->db->from('customerinfo');
			$this->db->where('customerid',$cid);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				return $query->row();
			}
			return false;
		}
	public function storeinfo(){
			$this->db->select('*');
			$this->db->from('setting');
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				return $query->row();
			}
			return false;
		}
	public function taxinfo(){
			$this->db->select('*');
			$this->db->from('tbl_taxmgt');
			$this->db->where('isactive',1);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				return $query->result();
			}
			return false;
		}
	public function btaxinfo($id){
			$this->db->select('*');
			$this->db->from('tbl_postedbills');
			$this->db->where('bookedid',$id);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				return $query->row();
			}
			return false;
		}
	public function commoninfo(){
			$this->db->select('*');
			$this->db->from('common_setting');
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				return $query->row();
			}
			return false;
		}
	public function paymentinfo($bookno){
			$this->db->select('tbl_guestpayments.*,payment_method.payment_method,booked_info.paid_amount');
			$this->db->from('tbl_guestpayments');
			$this->db->join('payment_method','payment_method.payment_method_id=tbl_guestpayments.paymenttype','left');
			$this->db->join('booked_info','booked_info.bookedid=tbl_guestpayments.bookedid','left');
			$this->db->where('tbl_guestpayments.bookedid',$bookno);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				return $query->row();
			}
			return false;
		}

	public function pruchasereport($start_date,$end_date)
	{
		$dateRange = "a.purchasedate BETWEEN '$start_date%' AND '$end_date%'";
		$this->db->select("a.*,b.supid,b.supName");
		$this->db->from('purchaseitem a');
		$this->db->join('supplier b','b.supid = a.suplierID');
		$this->db->where($dateRange, NULL, FALSE);
		$this->db->order_by('a.purchasedate','desc');
		$query = $this->db->get();
		return $query->result();
	}
	public function settinginfo()
	{
		return $this->db->select("*")->from('setting')
			->get()
			->row();
	}
	public function currencysetting($id = null)
	{
		return $this->db->select("*")->from('currency')
			->where('currencyid',$id)
			->get()
			->row();
	}

	public function read($limit = null, $start = null)
	{
	    $this->db->select('booked_info.*,booked_details.*');
        $this->db->from('booked_info');
		$this->db->join('booked_details','booked_details.bookedid=booked_info.bookedid','left');
        $this->db->order_by('booked_info.bookedid', 'desc');
        $this->db->limit($limit, $start);
        $query = $this->db->get();
		$scharge = $this->settinginfo();
		$charge = $scharge->servicecharge;
        if ($query->num_rows() > 0) {
            $result = $query->result();
			foreach($result as $k => $r){
				$start = strtotime($r->checkindate);
				$end = strtotime($r->checkoutdate);
				$datediff = $end - $start;
				$days = ceil($datediff / (60 * 60 * 24));
				$result[$k]->roomtype = $this->room_type($r->roomid);
				if($r->booked_from==1 && $r->bookingstatus==0){
					$result[$k]->total_price = $r->total_price;
				}else{
					$roomId = !empty($r->roomid) ? explode(",",$r->roomid) : array();
					$rent = !empty($r->roomrate) ? explode(",",$r->roomrate) : array();
					$offer_discount = !empty($r->offer_discount) ? explode(",",$r->offer_discount) : array();
					$totalrent=0;
					$totaloffer=0;
					$rentCount = count($rent);
					for($i=0;$i<$rentCount; $i++){
						$discount = isset($offer_discount[$i]) ? floatval($offer_discount[$i]) : 0;
						$rentValue = isset($rent[$i]) ? floatval($rent[$i]) : 0;
						$totalrent += $rentValue - $discount;
						$totaloffer += $discount;
					}
					$promocode = 0;
					if(!empty($r->promocode)){
						$pdiscount = $this->db->select("discount")->from("promocode")->where("promocode", $r->promocode)->get()->row();
						$promocode = !empty($pdiscount) ? floatval($pdiscount->discount ?? 0) : 0;
					}

					// Calculate base rent (for all days)
					$baseRent = floatval($days) * floatval($r->total_price ?? 0);

					// Get discount amount (per day, multiply by days)
					$discountAmount = 0;
					if(!empty($r->discountamount)){
						$discountAmount = floatval($r->discountamount) * $days;
					}

					// Calculate tax on BASE RENT (before discount)
					$totaltax = 0;
					$taxinfo = $this->taxinfo();
					if(!empty($taxinfo) && is_array($taxinfo)){
						foreach($taxinfo as $tax){
							$taxRate = floatval($tax->rate ?? 0);
							if($taxRate > 0){
								$totaltax += ($baseRent * $taxRate) / 100;
							}
						}
					}

					if($r->bookingstatus!=5){
						// Calculate service charge on BASE RENT (before discount)
						$serviceCharge = (!empty($charge) && floatval($charge) > 0) ? (floatval($days) * ((floatval($totalrent) * floatval($charge)) / 100)) : 0;

						// Total = (Base Rent - Discount - Promocode) + Tax + Service Charge
						$baseRentAfterDiscount = $baseRent - $discountAmount - $promocode;
						$baseRentAfterDiscount = max(0, $baseRentAfterDiscount);
						$result[$k]->total_price = $baseRentAfterDiscount + $totaltax + $serviceCharge;
						$result[$k]->paid_amount = floatval($r->paid_amount ?? 0);
					}
					if($r->bookingstatus==5){
						$creditamt = $this->db->select("rate,credit,complementary,extrabpc,additional_charges,additional_charges,ex_discount,swimming_pool,restaurant,hallroom,car_parking,special_discount, scharge")->from("tbl_postedbills")->where("bookedid",$r->bookedid)->get()->row();
						// CRITICAL FIX: Check if $creditamt is not null before accessing properties
						if(!empty($creditamt)){
							$result[$k]->creditamt = floatval($creditamt->credit ?? 0);

							// Calculate tax from posted bills if available, otherwise use active tax rates
							$totaltaxPosted = 0;
							if(!empty($creditamt->rate)){
								$rate = explode(",", $creditamt->rate ?? '');
								if(!empty($rate[0])){
									for($bt=0; $bt<count($rate); $bt++){
										$taxRate = floatval($rate[$bt] ?? 0);
										if($taxRate > 0){
											$totaltaxPosted += ($baseRent * $taxRate) / 100;
										}
									}
								}
							} else {
								// Fallback to active tax rates if posted bill has no rate
								$totaltaxPosted = $totaltax;
							}

							// Calculate service charge
							$serviceChargePosted = 0;
							if(!empty($creditamt->scharge)){
								$serviceChargePosted = floatval($creditamt->scharge);
							} else {
								$serviceChargePosted = (!empty($charge) && floatval($charge) > 0) ? (floatval($days) * ((floatval($totalrent) * floatval($charge)) / 100)) : 0;
							}

							// Base rent after discount
							$baseRentAfterDiscountPosted = $baseRent - $discountAmount - $promocode;
							$baseRentAfterDiscountPosted = max(0, $baseRentAfterDiscountPosted);

							// Total = (Base Rent - Discount - Promocode) + Tax + Service Charge + Posted Bill Charges
							$result[$k]->total_price = $baseRentAfterDiscountPosted + $totaltaxPosted + $serviceChargePosted + floatval($creditamt->complementary ?? 0) + floatval($creditamt->extrabpc ?? 0) + floatval($creditamt->additional_charges ?? 0) - floatval($creditamt->ex_discount ?? 0) + floatval($creditamt->swimming_pool ?? 0) + floatval($creditamt->restaurant ?? 0) + floatval($creditamt->hallroom ?? 0) + floatval($creditamt->car_parking ?? 0) - floatval($creditamt->special_discount ?? 0);

							$result[$k]->paid_amount = floatval($r->paid_amount ?? 0) + $serviceChargePosted + floatval($creditamt->restaurant ?? 0) + floatval($creditamt->hallroom ?? 0) + floatval($creditamt->car_parking ?? 0) - floatval($creditamt->ex_discount ?? 0) - floatval($creditamt->special_discount ?? 0);
						} else {
							// If no postedbills record exists, use default calculation
							$result[$k]->creditamt = 0;
							$serviceCharge = (!empty($charge) && floatval($charge) > 0) ? (floatval($days) * ((floatval($totalrent) * floatval($charge)) / 100)) : 0;

							// Base rent after discount
							$baseRentAfterDiscount = $baseRent - $discountAmount - $promocode;
							$baseRentAfterDiscount = max(0, $baseRentAfterDiscount);

							// Total = (Base Rent - Discount - Promocode) + Tax + Service Charge
							$result[$k]->total_price = $baseRentAfterDiscount + $totaltax + $serviceCharge;
							$result[$k]->paid_amount = floatval($r->paid_amount ?? 0);
						}
						if($totaloffer>0 && !empty($creditamt)){
							$datediff = strtotime($r->checkoutdate) - strtotime($r->checkindate);
							$datediff = ceil($datediff/(60*60*24));
							$singletax = explode(",", $creditamt->rate ?? '');
							$total=0;
							for($li = 0; $li < count($rent); $li++){
								for($in = 0; $in < $datediff; $in++){
									$alldays= date("Y-m-d", strtotime($r->checkindate . ' + ' . $in . 'day'));
									$getroom=$this->db->select("*")->from('tbl_room_offer')->where('roomid',$roomId[$li])->where('offer_date',$alldays)->get()->row();
									if(!empty($getroom)){
										$singleDiscount=$getroom->offer;
										$roomrate=$rent[$li]-$singleDiscount;
										}
									else{
										$roomrate=$rent[$li];
										}
									$price=$roomrate;
									$total=$total+$price;
								}
							}
							$toaltax=0;
							for($j=0; $j<count($singletax); $j++){
								$toaltax += (floatval($total) * floatval($singletax[$j] ?? 0)) / 100;
							}
							$result[$k]->total_price = floatval($creditamt->complementary ?? 0) + floatval($creditamt->extrabpc ?? 0) + floatval($total) + floatval($toaltax) + floatval($promocode) + floatval($creditamt->scharge ?? 0) + floatval($creditamt->additional_charges ?? 0) - floatval($creditamt->ex_discount ?? 0) + floatval($creditamt->swimming_pool ?? 0) + floatval($creditamt->restaurant ?? 0) + floatval($creditamt->hallroom ?? 0) + floatval($creditamt->car_parking ?? 0) - floatval($creditamt->special_discount ?? 0);
						}
					}
				}
			}
			return $result;
        }
        return false;
	}
	function room_type($rtype){
		if(empty($rtype)){
			return "";
		}
		$sroomtype = explode(",", $rtype);
		$type = "";
		for($i=0; $i<count($sroomtype); $i++){
			$row = $this->db->select("roomtype")->from("roomdetails")->where("roomid",$sroomtype[$i])->get()->row();
			if(!empty($row) && !empty($row->roomtype)){
				$type .= $row->roomtype.",";
			}
		}
		return trim($type,",");
	}

	public function customerlist()
	{
		$data = $this->db->select("customerid,firstname,lastname,cust_phone")
			->from('customerinfo')
			->get()
			->result();

		$list[''] = 'Select Customer';

		if (!empty($data)) {
			foreach($data as $value)
				$list[$value->customerid] = $value->firstname.' '.$value->lastname.'-'.$value->cust_phone;
			return $list;
		} else {
			return $list;
		}
	}

	/**
	 * Get all paid payments
	 * @param string $start_date Optional start date filter
	 * @param string $end_date Optional end date filter
	 * @return array|bool Array of payment records or false if none found
	 */
	public function getPaidPayments($start_date = NULL, $end_date = NULL)
	{
		$this->db->select('
			tbl_guestpayments.*,
			payment_method.payment_method,
			booked_info.booking_number,
			booked_info.checkindate,
			booked_info.checkoutdate,
			booked_info.total_price,
			booked_info.bookingstatus,
			customerinfo.firstname,
			customerinfo.lastname,
			customerinfo.cust_phone
		');
		$this->db->from('tbl_guestpayments');
		$this->db->join('payment_method', 'payment_method.payment_method_id = tbl_guestpayments.paymenttype', 'left');
		$this->db->join('booked_info', 'booked_info.bookedid = tbl_guestpayments.bookedid', 'left');
		$this->db->join('customerinfo', 'customerinfo.customerid = booked_info.cutomerid', 'left');

		// Apply date filters if provided
		if(!empty($start_date)){
			$this->db->where('DATE(tbl_guestpayments.paydate) >=', $start_date);
		}
		if(!empty($end_date)){
			$this->db->where('DATE(tbl_guestpayments.paydate) <=', $end_date);
		}

		// Only get payments where payment amount > 0 (actual paid payments)
		$this->db->where('tbl_guestpayments.paymentamount >', 0);

		// Order by payment date descending (most recent first)
		$this->db->order_by('tbl_guestpayments.paydate', 'DESC');
		$this->db->order_by('tbl_guestpayments.payid', 'DESC');

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->result();
		}
		return false;
	}
}
