<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roomreservation_model extends CI_Model {

	private $table = 'booked_info';

	public function create($data = array())
	{
		return $this->db->insert($this->table, $data);
	}
	public function delete($id = null)
	{
		$this->db->where('bookedid',$id)
			->delete($this->table);

		if ($this->db->affected_rows()) {
			return true;
		} else {
			return false;
		}
	}
	public function update($data = array())
	{
		return $this->db->where('bookedid',$data["bookedid"])
			->update($this->table, $data);
	}

    public function read($limit = null, $start = null)
	{
	    $this->db->select('booked_info.*,roomdetails.roomtype');
        $this->db->from($this->table);
		$this->db->join('roomdetails','roomdetails.roomid=booked_info.roomid','left');
        $this->db->order_by('booked_info.bookedid', 'desc');
        $this->db->limit($limit, $start);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
	}
	public function headcode($lvl,$code){
        $query=$this->db->query("SELECT MAX(HeadCode) as HeadCode FROM acc_coa WHERE HeadLevel='$lvl' And HeadCode LIKE '$code%'");
        return $query->row();
    }

	public function findById($id = null)
	{
		// CRITICAL: Join booked_details to get discountamount for correct calculations
		$this->db->select('booked_info.*,booked_details.discountamount');
        $this->db->from($this->table);
		$this->db->join('booked_details', 'booked_details.bookedid=booked_info.bookedid', 'left');
		$this->db->where('booked_info.bookedid',$id);
        $this->db->order_by('booked_info.bookedid', 'desc');
        $query = $this->db->get();
	    return $query->row();
	}


public function countlist()
	{
		$this->db->select('booked_info.*,roomdetails.roomtype');
        $this->db->from($this->table);
		$this->db->join('roomdetails','roomdetails.roomid=booked_info.roomid','left');
        $this->db->order_by('booked_info.bookedid', 'desc');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return false;
	}
public function allrooms()
	{
		$data = $this->db->select("*")
			->from('roomdetails')
			->get()
			->result();

		$list[''] = 'Select Room';
		if (!empty($data)) {
			foreach($data as $value)
				$list[$value->roomid] = $value->roomtype;
			return $list;
		} else {
			return false;
		}
	}
public function customerlist()
	{
		$data = $this->db->select("*")
			->from('customerinfo')
			->get()
			->result();

		$list[''] = 'Select Guest';

		if (!empty($data)) {
			foreach($data as $value)
				$list[$value->customerid] = $value->firstname.' '.$value->lastname;
			return $list;
		} else {
			return $list;
		}
	}
public function allpayments(){
	return $data = $this->db->select("tbl_guestpayments.*,booked_info.bookedid")
			->from('tbl_guestpayments')
			->join('booked_info','booked_info.booking_number=tbl_guestpayments.bookingnumber','left')
			->get()
			->result();
	}
public function paymentlist()
	{
		$data = $this->db->select("*")
			->from('payment_method')
			->get()
			->result();

		$list[''] = 'Select Payment';
		if (!empty($data)) {
			foreach($data as $value)
				$list[$value->payment_method_id] = $value->payment_method;
			return $list;
		} else {
			return false;
		}
	}
public function createpayment($data = array())
	{
		return $this->db->insert('tbl_guestpayments', $data);
	}
public function updatepayment($data = array())
	{
		return $this->db->where('payid',$data["payid"])
			->update('tbl_guestpayments', $data);
	}
public function findBypayId($id = null)
	{
		$this->db->select('*');
        $this->db->from('tbl_guestpayments');
        $this->db->join('booked_info','booked_info.bookedid=tbl_guestpayments.bookedid','left');
		$this->db->where('booked_info.booking_number',$id);
        $this->db->order_by('tbl_guestpayments.payid', 'desc');
        $query = $this->db->get();
	    return $query->row();
	}
	public function chargeinfo(){
		$this->db->select('*');
		$this->db->from('setting');
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			return $query->row();
		}
		return false;
	}
	public function insert_data($table, $data)
    {
        $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    public function update_date($table, $data, $field_name, $field_value)
    {
        $this->db->where($field_name, $field_value);
        return $this->db->update($table, $data);
    }

	public function get_all($select_items, $table, $orderby,$limit=NULL,$start=NULL)
    {
        $this->db->select($select_items);
        $this->db->from($table);
        $this->db->limit($limit, $start);
        $this->db->order_by($orderby,'DESC');
        return $this->db->get()->result();
    }
   public function read2($select_items, $table, $orderby, $where_array, $or_where=NULL)
    {

        $this->db->select($select_items);
        $this->db->from($table);
        foreach ($where_array as $field => $value) {
            $this->db->where($field, $value);

        }
        if($or_where!=NULL){
        foreach ($or_where as $field => $value) {
            $this->db->where($field, $value);

        }
        }
        $this->db->order_by($orderby,'DESC');
        return $this->db->get()->result();
    }
   public function readall($select_items, $table, $orderby, $where_array)
    {

        $this->db->select($select_items);
        $this->db->from($table);
        foreach ($where_array as $field => $value) {
            $this->db->where($field, $value);

        }
        $this->db->order_by($orderby,'Asc');
        return $this->db->get()->result();

    }
	public function readone($select_items, $table, $where_array, $where_array2=NULL)
    {
        $this->db->select($select_items);
        $this->db->from($table);
        foreach ($where_array as $field => $value) {
            $this->db->where($field, $value);
        }
        if($where_array2!=NULL){
        foreach ($where_array2 as $field => $value) {
            $this->db->where($field, $value);
        }
        }
        return $this->db->get()->row();
    }
    public function get_all_group($select_items, $table, $order_by_name = NULL, $order_by = NULL, $group_by = NULL)
    {
		$this->db->select($select_items);
        $this->db->from($table);
        if ($order_by_name != NULL && $order_by != NULL)
        {
            $this->db->order_by($order_by_name, $order_by);
        }
		$this->db->group_by($group_by);
        return $this->db->get()->result();
    }
	public function editbooking($id){
		$this->db->select("booked_info.*,booked_details.*");
		$this->db->from("booked_info");
		$this->db->join("booked_details","booked_details.bookedid=booked_info.bookedid","left");
		$this->db->where("booked_info.bookedid",$id);
		return $this->db->get()->row();
	}
	public function detailbooking($id){
		$this->db->select("booked_info.*,booked_details.*,customerinfo.*,GROUP_CONCAT(roomdetails.roomtype ORDER BY booked_info.room_no) as roomtype,roomdetails.bedcharge,roomdetails.personcharge");
		$this->db->from("booked_info");
		$this->db->join("booked_details","booked_details.bookedid=booked_info.bookedid","left");
		$this->db->join("customerinfo","customerinfo.customerid=booked_info.cutomerid","left");
		$this->db->join('tbl_roomnofloorassign','FIND_IN_SET(tbl_roomnofloorassign.roomno,booked_info.room_no)<>0','left');
		$this->db->join('roomdetails','FIND_IN_SET(roomdetails.roomid,tbl_roomnofloorassign.roomid)<>0','left');
		$this->db->where("booked_info.bookedid",$id);
		$row = $this->db->get()->row();
		$singlerate = explode(",", $row->roomrate);
		$total=0;
		for($i=0;$i<count($singlerate); $i++){
			$total += $singlerate[$i];
		}
		$rate = ($row->discountamount*100)/$total;
		$row->disrate = $rate;
		return $row;
	}
	public function findBookingDetail($id){
		$this->db->select("bi.*,bd.extrabed as bed,bd.extraperson as person,bd.extrachild as child,bd.extra_facility_days as exday,bd.complementaryprice as comprice,bd.booked_from,bd.discountamount");
		$this->db->from("booked_info bi");
		$this->db->join("booked_details bd","bd.bookedid=bi.bookedid","left");
		$this->db->where("bi.bookedid", $id);
		$query = $this->db->get();
		$row = $query->row();
		$row->totalExAmount = $this->exRoomBill($row->roomid,$row->bed,$row->person,$row->child,$row->exday);
		$row->totalComplementary = $this->roomComplementary($row->comprice);
		return $row;
	}
	public function exRoomBill($roomid,$bed,$person,$child,$day){
		if (empty($roomid) || empty($person) || empty($bed) || empty($day)) {
        return 0;
    }
		$singleid = explode(",", $roomid);
		$singlebed = explode(",", $bed);
		$singleperson = explode(",", $person);
		$singlechild = explode(",", $child);
		$singleday = explode(",", $day);
		$total = 0;
		for($i=0;$i<count($singleid); $i++){
			$charge = $this->db->select("bedcharge,personcharge")->from("roomdetails")->where("roomid",$singleid[$i])->get()->row();
			$total += ($charge->bedcharge*$singleday[$i]*$singlebed[$i])+($charge->personcharge*$singleday[$i]*$singleperson[$i])+(($charge->personcharge/2)*$singleday[$i]*$singlechild[$i]);
		}
		return $total;
	}
	public function roomComplementary($price){
		$singleprice = explode(",", $price);
		$total = 0;
		for($i=0; $i<count($singleprice); $i++){
			$total += $singleprice[$i];
		}
		return $total;
	}
	public function poolcastinfodata($poollastins){
		$this->db->select('tbl_pool_booking.*,customerinfo.*');
        $this->db->from('tbl_pool_booking');
		$this->db->join('customerinfo','customerinfo.customerid=tbl_pool_booking.custid','left');
		$this->db->where('pbookingid',$poollastins);
        $query = $this->db->get();
	    return $query->row();

	}
	public function pitemlistdata($poollastins){
		$this->db->select('tbl_pool_bookingitem.*,tbl_pool_package.*');
        $this->db->from('tbl_pool_bookingitem');
		$this->db->join('tbl_pool_package','tbl_pool_package.packageid=tbl_pool_bookingitem.packageid','left');
		$this->db->where('tbl_pool_bookingitem.pbokingid',$poollastins);
        $query = $this->db->get();
	    return $query->result();

	}
	public function pitemdatarow($poollastins){
		$this->db->select('*');
        $this->db->from('tbl_pool_booking');
		$this->db->where('pbookingid',$poollastins);
        $query = $this->db->get();
	    return $query->row();

	}
	public function restaurantCust($id){
		$this->db->select("ci.*");
		$this->db->from("customer_order co");
		$this->db->join("customerinfo ci","ci.customerid=co.customer_id","left");
		$this->db->where("co.order_id", $id);
		$query = $this->db->get();
		$row = $query->row();
		return $row;
	}
	public function ritemlistdata($id){
		$this->db->select('if.ProductName,om.price,om.menuqty');
        $this->db->from('customer_order co');
		$this->db->join('order_menu om','om.order_id=co.order_id','left');
		$this->db->join('item_foods if','if.ProductsID=om.menu_id','left');
		$this->db->where('co.order_id',$id);
        $query = $this->db->get();
	    $result = $query->result();
		foreach($result as $k => $val){
			$result[$k]->subtotal = $val->price*$val->menuqty;
		}
		return $result;
	}
	public function ritemdatasingle($id){
		$this->db->select('co.order_id,co.totalamount');
        $this->db->from('customer_order co');
		$this->db->where('order_id',$id);
        $query = $this->db->get();
	    $row = $query->row();
		$row->details = $this->ritemlistdata($row->order_id);
		return $row;

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
	public function currencysetting($id = null)
	{
		return $this->db->select("*")->from('currency')
			->where('currencyid',$id)
			->get()
			->row();
	}
	public function hallRoomCust($id){
		$this->db->select("ci.*");
		$this->db->from("tbl_hallroom_booking hb");
		$this->db->join("customerinfo ci","ci.customerid=hb.customerid","left");
		$this->db->where("hb.hbid", $id);
		$query = $this->db->get();
		$row = $query->row();
		return $row;
	}
	public function hallDetailsList($id){
		$this->db->select('hb.*');
        $this->db->from('tbl_hallroom_booking hb');
		$this->db->where('hbid',$id);
        $query = $this->db->get();
	    $row = $query->row();
		return $row;
	}
	public function carParkingCust($id){
		$this->db->select("ci.*");
		$this->db->from("tbl_bookParking bp");
		$this->db->join("booked_info bi","bi.bookedid=bp.bookedid","left");
		$this->db->join("customerinfo ci","ci.customerid=bi.cutomerid","left");
		$this->db->where("bp.bookParking_id", $id);
		$query = $this->db->get();
		$row = $query->row();
		return $row;
	}
	public function parkingDetailsList($id){
		$this->db->select('bp.*');
        $this->db->from('tbl_bookParking bp');
		$this->db->where('bookParking_id',$id);
        $query = $this->db->get();
	    $row = $query->row();
		return $row;
	}
	public function floorwithRoom(){
		$this->db->select("f.floorid, f.floorname");
		$this->db->from("tbl_floorplan fp");
		$this->db->join("tbl_floor f", "f.floorid = fp.floorName", "left");
		$this->db->group_by("f.floorid");
		$get = $this->db->get();
		$result = $get->result();
		return $result;
	}
	public function matchedRooms($search=NULL,$key=NULL,$key1=NULL,$key2=NULL){
		if($search!=null){
			$this->db->select("rfa.*");
			$this->db->from('tbl_roomnofloorassign rfa');
			$this->db->join("roomdetails rd","rd.roomid=rfa.roomid","left");
			$this->db->join("tbl_floor f","f.floorid=rfa.floorid","left");
			$this->db->like("rfa.roomno",$search);
			$this->db->or_like("rd.roomtype",$search);
			$this->db->or_like("rd.rate",$search);
			$this->db->or_like("rd.number_of_star",$search);
			$this->db->or_like("f.floorname",$search);
			$this->db->order_by("rfa.roomno", "ASC");
			$get = $this->db->get();
			$result = $get->result();
			return $result;
		}
		else if($key!="null" & $key!=null){
			$this->db->select("rfa.*");
			$this->db->from('tbl_roomnofloorassign rfa');
			$this->db->order_by("rfa.roomno", "ASC");
			if($key2!="null"){
				$this->db->where("rfa.floorid",$key2);
			}
			$get1 = $this->db->get();
			$result1 = $get1->result();
			foreach($result1 as $res1){
				$res1->date = $key;
				$res1->status = 1;
			}

			$this->db->select("room_no,bookingstatus, rfa.*");
			$this->db->from("booked_info");
			$this->db->join('tbl_roomnofloorassign rfa','FIND_IN_SET(rfa.roomno,booked_info.room_no)<>0','left');
			$this->db->where("date(checkindate)<=",$key);
			$this->db->where("date(checkoutdate)>=",$key);
			if($key2!="null"){
				$this->db->where("rfa.floorid",$key2);
			}
			if($key1!="null"){
				if($key1==1){
					$this->db->where_in("bookingstatus",array(0,4));
				}else{
					$this->db->where("bookingstatus",$key1);
				}
			}
			if($key1=="null"){
				$this->db->where_in("bookingstatus",array(0,4,5));
			}
			$get2 = $this->db->get();
			$result2 = $get2->result();
			if($result2){
				foreach($result2 as $res2){
					if($res2->bookingstatus==0 | $res2->bookingstatus==4 | $res2->bookingstatus==5){
						if($res2->bookingstatus==0){
							$res2->status = 2;
						}
						if($res2->bookingstatus==4){
							$res2->status = 0;
						}
						$res2->bookingstatus = 2;
					}
					foreach($result1 as $res1){
						$sroom = explode(",", $res2->room_no);
						for($i=0; $i<count($sroom); $i++){
							if($res1->roomno == $sroom[$i]){
								$res1->status = $res2->bookingstatus;
								break;
							}
						}
					}
				}
			}
			if($key1==1){
				foreach($result1 as $k => $res1){
					if($res1->status!= 1){
						unset($result1[$k]);
					}
				}
				return array_values($result1);
			}
			if($key1=="null"){
				return $result1;
			}else{
				return $result2;
			}
		}
		else if($key1!="null" & $key1!=null){
			$this->db->select("rfa.*");
			$this->db->from('tbl_roomnofloorassign rfa');
			$this->db->order_by("rfa.floorid");
			$get1 = $this->db->get();
			$result1 = $get1->result();
			foreach($result1 as $res1){
				$res1->date = date("Y-m-d");
				$res1->status = 1;
			}

			$this->db->select("room_no,bookingstatus, rfa.*");
			$this->db->from("booked_info");
			$this->db->join('tbl_roomnofloorassign rfa','FIND_IN_SET(rfa.roomno,booked_info.room_no)<>0','left');
			$this->db->where("date(date_time)",date("Y-m-d"));
			if($key1==1){
				$this->db->where("bookingstatus!=",$key1);
			}else{
				$this->db->where("bookingstatus",$key1);
			}
			$get2 = $this->db->get();
			$result2 = $get2->result();
			if($result2){
				foreach($result2 as $res2){
					if($res2->bookingstatus==4){
						$res2->bookingstatus = 0;
					}
					if($res2->bookingstatus==0 | $res2->bookingstatus==5){
						$res2->bookingstatus = 2;
					}
					foreach($result1 as $res1){
						$sroom = explode(",", $res2->room_no);
						for($i=0; $i<count($sroom); $i++){
							if($res1->roomno == $sroom[$i]){
								$res1->status = $res2->bookingstatus;
								break;
							}
						}
					}
				}
			}
			if($key1==1){
				return $result1;
			}
			return $result2;
		}
		else if($key2!=null & $key2!="null"){
			$this->db->select("rfa.*");
			$this->db->from('tbl_roomnofloorassign rfa');
			$this->db->order_by("rfa.floorid");
			if($key2!=null){
				$this->db->where("rfa.floorid",$key2);
			}
			$get1 = $this->db->get();
			$result1 = $get1->result();
			foreach($result1 as $res1){
				$res1->date = date("Y-m-d");
				$res1->status = 1;
			}
			$this->db->select("room_no,bookingstatus, rfa.*");
			$this->db->from("booked_info");
			$this->db->join('tbl_roomnofloorassign rfa','FIND_IN_SET(rfa.roomno,booked_info.room_no)<>0','left');
			$this->db->where("checkindate<=",date("Y-m-d"));
			$this->db->where("checkoutdate>=",date("Y-m-d"));
			$get2 = $this->db->get();
			$result2 = $get2->result();
			if($result2){
				foreach($result2 as $res2){
					if($res2->bookingstatus==4){
						$res2->bookingstatus = 0;
					}
					if($res2->bookingstatus==0 | $res2->bookingstatus==5){
						$res2->bookingstatus = 2;
					}
					foreach($result1 as $res1){
						$sroom = explode(",", $res2->room_no);
						for($i=0; $i<count($sroom); $i++){
							if($res1->roomno == $sroom[$i]){
								$res1->status = $res2->bookingstatus;
								break;
							}
						}
					}
				}
			}
			return $result1;
		}
	}
// public function send_email($email, $subject, $body, $emailtext, $path = null)
// {
//     // 1. Check permission
//     $check  = explode(' ', $subject);
//     $status = $this->db
//         ->select('status')
//         ->from('tbl_email_permission')
//         ->where('permission', lcfirst($check[0]))
//         ->get()
//         ->row();

//     if ($status && (int)$status->status === 0) {
//         log_message('error', 'Email blocked by permission rule: ' . $subject);
//         return false;
//     }

//     // 2. Load email config
//     $send_email = $this->readone('*', 'email_config', ['email_config_id' => 1]);

//     if (!$send_email || empty($send_email->smtp_host)) {
//         log_message('error', 'Email configuration missing or invalid');
//         return false;
//     }

//     // 3. Email configuration (SMTP – Hostinger compatible)
//     $config = [
//         'protocol'     => 'smtp',
//         'smtp_host'    => $send_email->smtp_host, // smtp.hostinger.com
//         'smtp_port'    => (int) $send_email->smtp_port, // 587
//         'smtp_user'    => $send_email->sender,
//         'smtp_pass'    => $send_email->smtp_password,
//         'smtp_crypto'  => 'tls', // REQUIRED
//         'mailtype'     => 'html',
//         'charset'      => 'utf-8',
//         'wordwrap'     => true,
//         'crlf'         => "\r\n",
//         'newline'      => "\r\n"
//     ];

//     // 4. Initialize email library
//     $this->load->library('email');
//     $this->email->initialize($config);
//     $this->email->clear(true);

//     // 5. Build email
//     $this->email->from($send_email->sender, 'System Mailer');
//     $this->email->to($email);
//     $this->email->subject($subject);
//     $this->email->message($emailtext);

//     // 6. Attach file (must be server path, not URL)
//     if (!empty($path)) {
//         $filePath = FCPATH . ltrim($path, '/');
//         if (file_exists($filePath)) {
//             $this->email->attach($filePath);
//         } else {
//             log_message('error', 'Email attachment not found: ' . $filePath);
//         }
//     }

//     // 7. Send email
//     if (!$this->email->send()) {
//         $debug = $this->email->print_debugger(['headers']);
//         log_message('error', "Email sending failed.\nSubject: {$subject}\nTo: {$email}\nDebug:\n{$debug}");
//         return false;
//     }

//     return true;
// }

public function send_email($email, $subject, $body, $emailtext, $path = null)
{
    // 1. Check permission
    $check  = explode(' ', $subject);
    $status = $this->db
        ->select('status')
        ->from('tbl_email_permission')
        ->where('permission', lcfirst($check[0]))
        ->get()
        ->row();

    if ($status && (int)$status->status === 0) {
        log_message('error', 'Email blocked by permission rule: ' . $subject);
        return false;
    }

    // 2. Load email config
    $send_email = $this->readone('*', 'email_config', ['email_config_id' => 1]);

    if (!$send_email || empty($send_email->smtp_host)) {
        log_message('error', 'Email configuration missing or invalid');
        return false;
    }

   
	 $config = [
        'protocol' => 'sendmail',
        'mailpath' => '/usr/sbin/sendmail',
        'mailtype' => 'html',
        'charset'  => 'utf-8',
        'wordwrap' => true,
        'crlf'     => "\r\n",
        'newline'  => "\r\n"
    ];

    // 4. Initialize email library
    $this->load->library('email');
    $this->email->initialize($config);
    $this->email->clear(true);

    // 5. Build email
    $this->email->from($send_email->sender, 'The Auraapartments NG');
    $this->email->to($email);
    $this->email->subject($subject);
    $this->email->message($emailtext);

    // 6. Attach file (must be server path, not URL)
    if (!empty($path)) {
        $filePath = FCPATH . ltrim($path, '/');
        if (file_exists($filePath)) {
            $this->email->attach($filePath);
        } else {
            log_message('error', 'Email attachment not found: ' . $filePath);
        }
    }

    // 7. Send email
    if (!$this->email->send()) {
        $debug = $this->email->print_debugger(['headers']);
        log_message('error', "Email sending failed.\nSubject: {$subject}\nTo: {$email}\nDebug:\n{$debug}");
        return false;
    }

    return true;
}

	/**
	 * Test email sending function
	 * This function allows you to test email configuration and sending
	 *
	 * @param string $test_email Optional. Email address to send test email to. If not provided, uses sender email from config
	 * @param bool $return_debug Optional. If true, returns debug information instead of boolean
	 * @return mixed Returns true on success, false on failure, or debug info if $return_debug is true
	 */
	public function test_send_email($test_email = null, $return_debug = false){
		// Get email configuration
		$send_email = $this->readone('*', 'email_config', array('email_config_id' => 1));

		// Check if email config exists
		if(!$send_email || empty($send_email->smtp_host)){
			$error = 'Email configuration not found or invalid';
			log_message('error', $error);
			if($return_debug){
				return array(
					'success' => false,
					'error' => $error,
					'config' => null
				);
			}
			return false;
		}

		// Use test email or fallback to sender email
		$recipient_email = !empty($test_email) ? $test_email : $send_email->sender;

		// Helper function for html escaping
		if (!function_exists('html_escape')) {
			function html_escape($var) {
				return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
			}
		}

		// Get hotel settings from database (with fallbacks)
		$hotel_settings = $this->db->select("*")->from("common_setting")->where("id", 1)->get()->row();
		$appName = $this->db->select("title")->from("setting")->where("id", 2)->get()->row();

		// Safely map hotel meta data with fallbacks
		$hotelEmail = (!empty($hotel_settings) && !empty($hotel_settings->email)) ? $hotel_settings->email : 'info@hotel.com';
		$hotelPhone = (!empty($hotel_settings) && !empty($hotel_settings->phone)) ? $hotel_settings->phone : '+234 123 456 7890';
		$hotelAddress = (!empty($hotel_settings) && !empty($hotel_settings->address)) ? $hotel_settings->address : '123 Hotel Street, City, State';
		$hotelBankName = (!empty($hotel_settings) && !empty($hotel_settings->bank_name)) ? $hotel_settings->bank_name : 'Zenith Bank';
		$hotelAccountNumber = (!empty($hotel_settings) && !empty($hotel_settings->account_number)) ? $hotel_settings->account_number : '0033088864';
		$hotelAccountName = (!empty($hotel_settings) && !empty($hotel_settings->account_name)) ? $hotel_settings->account_name : null;
		$hotelName = (!empty($appName) && !empty($appName->title)) ? $appName->title : 'Sample Hotel & Resort';

		// Get currency
		$currency = getCurrency();
		$currencySymbol = !empty($currency->curr_icon) ? $currency->curr_icon : 'NGN';
		$formatMoney = function ($amount) use ($currencySymbol) {
			return trim($currencySymbol) . ' ' . number_format((float)$amount, 2);
		};

		// Optimized sample data for test email
		$test_subject = "Test Email - Reservation Confirmed - " . date('M d, Y H:i');
		$test_body = $hotelName;
		$email_title = "Reservation Confirmed";
		$greeting_message = "Thank you for choosing " . $hotelName . ". We are pleased to confirm your reservation with us.";

		// Realistic test booking data
		$guestName = "Sarah Johnson";
		$roomType = "Executive Suite";
		$roomNo = "305";
		$reservationStatus = "Confirmed";
		$booking_number = "BK" . strtoupper(substr(uniqid(), -8));

		// Realistic test dates (next week)
		$checkin = new DateTime('+7 days');
		$checkin->setTime(14, 0); // 2:00 PM check-in
		$checkout = new DateTime('+9 days');
		$checkout->setTime(11, 0); // 11:00 AM check-out
		$checkin_formatted = $checkin->format('l, jS F Y \a\t g:i A');
		$checkout_formatted = $checkout->format('l, jS F Y \a\t g:i A');
		$nights = 2;

		// Realistic financial data with proper calculations
		$room_rate_per_night = 45000;
		$base_rent = $room_rate_per_night * $nights;
		$total_tax_rate = 7.5;
		$tax_amount = round(($base_rent * $total_tax_rate) / 100, 2);
		$service_charge_rate = 10;
		$service_charge = round(($base_rent * $service_charge_rate) / 100, 2);
		$discount_amount = 7500; // Early booking discount
		$base_rent_after_discount = max(0, $base_rent - $discount_amount);
		$final_total = round($base_rent_after_discount + $tax_amount + $service_charge, 2);
		$advance_amount = 30000; // 30% advance payment
		$remaining_amount = max(0, round($final_total - $advance_amount, 2));
		$payment_status = $advance_amount >= $final_total ? 'Fully Paid' : ($advance_amount > 0 ? 'Partial Payment' : 'Pending Payment');
		$paymentStatusColor = $payment_status == 'Fully Paid' ? '#1b4332' : ($payment_status == 'Partial Payment' ? '#b7791f' : '#c53030');
		$nuofpeople = 2;
		$discount_reason = "Early Booking Discount";

		// Generate email template using the exact same structure as reservation confirmed
		ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($email_title); ?></title>
    <style>
        /* Email-safe CSS with fallbacks */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: #f4f6fb !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .email-wrapper {
            background-color: #f4f6fb;
            padding: 20px 0;
        }
        .email-shell {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0f3d2c 0%, #1f6f54 100%);
            padding: 32px 28px 24px;
            color: #ffffff;
        }
        .header p {
            margin: 0 0 8px 0;
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }
        .section {
            padding: 24px 28px;
            border-top: 1px solid #e4e9f2;
        }
        .section:first-of-type {
            border-top: none;
        }
        .section h3 {
            margin: 0 0 16px 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2933;
        }
        .hello {
            font-size: 17px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #1f2933;
        }
        .test-notice {
            margin: 16px 0 0 0;
            padding: 12px 16px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            font-size: 13px;
            line-height: 1.5;
            color: #856404;
        }
        .test-notice strong {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
        }
        .summary-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 10px;
            margin: 0 -5px;
        }
        .summary-card {
            display: table-cell;
            background-color: #ecf8f3;
            border-radius: 8px;
            padding: 16px;
            vertical-align: top;
        }
        .summary-card .label {
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 6px;
            font-weight: 600;
            display: block;
        }
        .summary-card .value {
            font-size: 16px;
            font-weight: 700;
            color: #1f2933;
            display: block;
            line-height: 1.3;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            background-color: rgba(31, 111, 84, 0.15);
            color: #1f6f54;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table td {
            padding: 12px 0;
            text-align: left;
            border-bottom: 1px solid #e4e9f2;
            font-size: 14px;
            color: #1f2933;
        }
        table td.amount {
            text-align: right;
            font-weight: 600;
        }
        table .totals td {
            font-size: 16px;
            font-weight: 700;
            padding-top: 16px;
            border-top: 2px solid #1f6f54;
            border-bottom: none;
        }
        .pill {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            background-color: rgba(31, 111, 84, 0.1);
            color: #1f2933;
            line-height: 1.6;
        }
        .note {
            padding: 16px;
            background-color: #f0fcf7;
            border-radius: 8px;
            border: 1px solid rgba(31, 111, 84, 0.2);
            font-size: 14px;
            line-height: 1.6;
            color: #1f2933;
        }
        .footer {
            background-color: #f3f5f9;
            padding: 24px 28px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.8;
        }
        .footer strong {
            display: block;
            color: #1f2933;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        /* Mobile responsive */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }
            .email-shell {
                border-radius: 4px;
            }
            .header, .section, .footer {
                padding-left: 20px;
                padding-right: 20px;
            }
            .summary-grid {
                display: block;
            }
            .summary-card {
                display: block;
                margin-bottom: 10px;
            }
            table td.amount {
                font-size: 13px;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6fb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <div class="email-wrapper">
        <div class="email-shell">
            <div class="header">
                <p><?= html_escape($hotelName); ?></p>
                <h1><?= html_escape($email_title); ?></h1>
            </div>

            <div class="section">
                <p class="hello">Dear <?= html_escape($guestName); ?>,</p>
                <p style="margin: 0 0 12px 0; color: #1f2933; line-height: 1.6;"><?= html_escape($greeting_message); ?></p>
                <p style="margin: 0 0 0 0; color: #6b7280; line-height: 1.6;">We are getting everything ready for a seamless stay. Below is a quick snapshot of your reservation.</p>
                <div class="test-notice">
                    <strong>Test Email Notice</strong>
                    This is a test email sent on <?= date('F j, Y \a\t g:i A'); ?> to verify your email configuration. If you received this email, your email settings are working correctly.
                </div>
            </div>

        <div class="section">
            <div class="summary-grid">
                <div class="summary-card">
                    <span class="label">Reservation</span>
                    <span class="value">#<?= html_escape($booking_number); ?></span>
                </div>
                <div class="summary-card">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="badge"><?= html_escape($reservationStatus); ?></span>
                    </span>
                </div>
                <div class="summary-card">
                    <span class="label">Payment Status</span>
                    <span class="value" style="color: <?= html_escape($paymentStatusColor); ?>;"><?= html_escape($payment_status); ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Stay Details</h3>
            <table>
                <tbody>
                    <tr>
                        <td style="color: #6b7280;">Guest Name</td>
                        <td class="amount"><?= html_escape($guestName); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Room</td>
                        <td class="amount"><?= html_escape($roomType); ?> · Room <?= html_escape($roomNo); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Check-in</td>
                        <td class="amount"><?= html_escape($checkin_formatted); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Check-out</td>
                        <td class="amount"><?= html_escape($checkout_formatted); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Duration</td>
                        <td class="amount"><?= (int)$nights; ?> <?= $nights == 1 ? 'Night' : 'Nights'; ?></td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Number of Guests</td>
                        <td class="amount"><?= (int)$nuofpeople; ?> <?= $nuofpeople == 1 ? 'Guest' : 'Guests'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Investment Summary</h3>
            <table>
                <tbody>
                    <tr>
                        <td style="color: #6b7280;">Room Rent (<?= (int)$nights; ?> <?= $nights == 1 ? 'Night' : 'Nights'; ?>)</td>
                        <td class="amount"><?= $formatMoney($base_rent); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">VAT (<?= number_format($total_tax_rate, 1); ?>%)</td>
                        <td class="amount"><?= $formatMoney($tax_amount); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Service Charge (<?= number_format($service_charge_rate, 1); ?>%)</td>
                        <td class="amount"><?= $formatMoney($service_charge); ?></td>
                    </tr>
					<?php if ($discount_amount > 0): ?>
                    <tr>
                        <td style="color: #6b7280;">Discount (<?= html_escape($discount_reason); ?>)</td>
                        <td class="amount" style="color: #1f6f54;">-<?= $formatMoney($discount_amount); ?></td>
                    </tr>
					<?php endif; ?>
                    <tr class="totals">
                        <td>Total Payable</td>
                        <td class="amount"><?= $formatMoney($final_total); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #6b7280;">Advance Paid</td>
                        <td class="amount" style="color: #1b4332;"><?= $advance_amount > 0 ? $formatMoney($advance_amount) : 'NIL'; ?></td>
                    </tr>
					<?php if ($remaining_amount > 0): ?>
                    <tr>
                        <td style="color: #6b7280; font-weight: 600;">Balance Due</td>
                        <td class="amount" style="color: #c53030; font-weight: 700;"><?= $formatMoney($remaining_amount); ?></td>
                    </tr>
					<?php else: ?>
                    <tr>
                        <td style="color: #6b7280; font-weight: 600;">Balance Due</td>
                        <td class="amount" style="color: #1b4332; font-weight: 700;">Paid in Full</td>
                    </tr>
					<?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Payment Instructions</h3>
            <p style="margin: 0 0 16px 0; color: #6b7280; line-height: 1.6;">Bank transfer protects your reservation. Use the details below and send us the payment slip for instant confirmation.</p>
            <div class="pill">
                <strong>Bank:</strong> <?= html_escape($hotelBankName); ?><br>
                <strong>Account Number:</strong> <?= html_escape($hotelAccountNumber); ?><br>
                <strong>Account Name:</strong> <?= html_escape($hotelAccountName ?: $hotelName); ?>
            </div>
        </div>

        <div class="section">
            <div class="note">
                <strong style="display: block; margin-bottom: 4px;">Need Assistance?</strong>
                We are here 24/7. Reply to this email or call <?= html_escape($hotelPhone); ?> for quick assistance.
            </div>
        </div>

        <div class="footer">
            <strong><?= html_escape($hotelName); ?></strong>
            <div><?= html_escape($hotelEmail); ?></div>
            <div><?= html_escape($hotelPhone); ?></div>
            <div><?= html_escape($hotelAddress); ?></div>
        </div>
        </div>
    </div>
</body>
</html>
<?php
		$test_message = ob_get_clean();

		$config = array(
			'protocol'  => $send_email->protocol,
			'smtp_host' => $send_email->smtp_host,
			'smtp_port' => $send_email->smtp_port,
			'smtp_crypto' => 'ssl',
			'smtp_user' => $send_email->sender,
			'smtp_pass' => $send_email->smtp_password,
			'mailtype'  => $send_email->mailtype,
			'charset' => 'utf-8',
			'wordwrap' => TRUE
		);

		$this->load->library('email');

		// Don't suppress errors for testing - we want to see what's happening
		$this->email->initialize($config);
		$this->email->set_newline("\r\n");
		$this->email->set_mailtype("html");
		$this->email->from($send_email->sender, $test_body);
		$this->email->to($recipient_email);
		$this->email->subject($test_subject);
		$this->email->message($test_message);

		// Attempt to send
		$result = $this->email->send();
		$debug_info = $this->email->print_debugger();

		if($return_debug){
			return array(
				'success' => $result ? true : false,
				'result' => $result,
				'debug_info' => $debug_info,
				'config' => array(
					'smtp_host' => $send_email->smtp_host,
					'smtp_port' => $send_email->smtp_port,
					'protocol' => $send_email->protocol,
					'sender' => $send_email->sender,
					'recipient' => $recipient_email
				),
				'error' => $result ? null : $debug_info
			);
		}

		if(!$result) {
			log_message('error', 'Test email sending failed. Error: ' . $debug_info);
			return false;
		}

		log_message('info', 'Test email sent successfully to: ' . $recipient_email);
		return true;
	}

	/**
	 * Generate unique invoice number with race condition protection
	 * Uses table locking to prevent duplicate invoice numbers
	 *
	 * @return string Invoice number in format 000000XX
	 */
	public function generateInvoiceNumber() {
		$this->db->trans_start();
		try {
			// Lock table for exclusive access to prevent race conditions
			$this->db->query("LOCK TABLES tbl_guestpayments WRITE");

			// Get last invoice number
			$payinfo = $this->db->select("invoice")
				->from('tbl_guestpayments')
				->order_by('payid', 'desc')
				->limit(1)
				->get()
				->row();

			if (!empty($payinfo) && !empty($payinfo->invoice)) {
				// Extract numeric part from invoice
				$invoicenum = preg_replace('/[^0-9]/', '', $payinfo->invoice);
				if (empty($invoicenum)) {
					$invoicenum = "000000";
				}
			} else {
				$invoicenum = "000000";
			}

			$nextno = (int)$invoicenum + 1;
			$bk_length = strlen((string)$nextno);
			$bkstr = '000000';
			$bknumber = substr($bkstr, $bk_length);
			$invoice_no = $bknumber . $nextno;

			// Verify uniqueness (additional safety check)
			$exists = $this->db->select("payid")
				->from('tbl_guestpayments')
				->where('invoice', $invoice_no)
				->get()
				->row();

			if (!empty($exists)) {
				// If exists, increment again (shouldn't happen with locks)
				$nextno++;
				$bk_length = strlen((string)$nextno);
				$bknumber = substr($bkstr, $bk_length);
				$invoice_no = $bknumber . $nextno;
			}

			// Unlock tables
			$this->db->query("UNLOCK TABLES");
			$this->db->trans_complete();

			return $invoice_no;
		} catch (Exception $e) {
			// Ensure tables are unlocked even on error
			$this->db->query("UNLOCK TABLES");
			$this->db->trans_rollback();
			log_message('error', 'Invoice generation failed: ' . $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Calculate tax amount for a given base amount
	 * Centralized tax calculation method
	 *
	 * @param float $baseAmount Base amount to calculate tax on
	 * @param bool $returnBreakdown If true, returns breakdown by tax type; if false, returns total only
	 * @return float|array If breakdown=false: total tax amount; If breakdown=true: array with 'total', 'rate', and 'breakdown'
	 */
	public function calculateTax($baseAmount, $returnBreakdown = false) {
		// Get all active tax rates
		$taxes = $this->db->select("taxname,rate")
			->from("tbl_taxmgt")
			->where("isactive", 1)
			->get()
			->result();

		$totalTax = 0;
		$totalRate = 0;
		$breakdown = array();

		if (!empty($taxes)) {
			foreach ($taxes as $tax) {
				$totalRate += $tax->rate;
				$taxAmount = ($baseAmount * $tax->rate) / 100;
				$totalTax += $taxAmount;

				if ($returnBreakdown) {
					$breakdown[] = array(
						'name' => $tax->taxname,
						'rate' => $tax->rate,
						'amount' => $taxAmount
					);
				}
			}
		}

		if ($returnBreakdown) {
			return array(
				'total' => $totalTax,
				'rate' => $totalRate,
				'breakdown' => $breakdown
			);
		}

		return $totalTax;
	}

	/**
	 * Get active tax rates (for display purposes)
	 *
	 * @return array|false Array of tax objects with taxname and rate, or false if none found
	 */
	public function getActiveTaxRates() {
		$taxes = $this->db->select("taxname,rate")
			->from("tbl_taxmgt")
			->where("isactive", 1)
			->get()
			->result();

		return (!empty($taxes)) ? $taxes : false;
	}

	/**
	 * Get total tax rate percentage (sum of all active tax rates)
	 *
	 * @return float Total tax rate percentage
	 */
	public function getTotalTaxRate() {
		$taxes = $this->db->select("rate")
			->from("tbl_taxmgt")
			->where("isactive", 1)
			->get()
			->result();

		$totalRate = 0;
		if (!empty($taxes)) {
			foreach ($taxes as $tax) {
				$totalRate += $tax->rate;
			}
		}

		return $totalRate;
	}

	/**
	 * Calculate payment status for a booking
	 * Accounts for room charges, taxes, service charges, and parking
	 *
	 * @param int $bookedid Booking ID
	 * @return string|false Payment status ('Success' or 'Pending') or false on error
	 */
	public function calculatePaymentStatus($bookedid) {
		// Get booking details
		$booking = $this->findById($bookedid);
		if (empty($booking)) {
			return false;
		}

		// Calculate total charges (room price * number of days)
		$datediff = (strtotime($booking->checkoutdate) - strtotime($booking->checkindate)) / (60 * 60 * 24);
		$datediff = ceil($datediff);
		$totalPrice = $booking->total_price * ($datediff > 0 ? $datediff : 1);

		// Get taxes using centralized method
		$totalTax = $this->calculateTax($totalPrice, false);

		// Get service charge
		$scharge = $this->db->select("servicecharge")->from("setting")->get()->row();
		$totalScharge = 0;
		if (!empty($scharge->servicecharge)) {
			$totalScharge = ($totalPrice * $scharge->servicecharge) / 100;
		}

		// Get parking charges
		$car_parking = $this->db->where('directory', 'car_parking')->where('status', 1)->get('module')->num_rows();
		$totalParking = 0;
		if ($car_parking == 1) {
			$parking_records = $this->db->select("total_price")
				->from("tbl_bookParking")
				->where("bookedid", $bookedid)
				->get()
				->result();
			if (!empty($parking_records)) {
				foreach ($parking_records as $cp) {
					$totalParking += $cp->total_price;
				}
			}
		}

		// Get discount amount from booking
		$discountAmount = !empty($booking->discountamount) ? $booking->discountamount : 0;

		// Calculate subtotal (rent + tax + service charge + parking)
		$subtotal = $totalPrice + $totalTax + $totalScharge + $totalParking;

		// Apply discount to get total due
		$totalDue = $subtotal - $discountAmount;
		$due = $totalDue - $booking->paid_amount;

		// Determine status (allow small rounding differences)
		if ($due <= 0.01) {
			return 'Success';
		} else {
			return 'Pending';
		}
	}

	/**
	 * Centralized booking calculation method
	 * Calculates total charges, tax, service charge, parking, discount, and due amount
	 * Ensures consistency across all booking calculations
	 *
	 * @param int|object $booking Booking ID or booking object
	 * @param int|null $days Number of days (if null, calculates from checkin/checkout dates)
	 * @return array|false Array with calculation breakdown or false on error
	 *                    Array keys: baseRent, totalTax, totalScharge, totalParking,
	 *                               subtotal, discountAmount, totalAfterDiscount, due
	 */
	public function calculateBookingTotal($booking, $days = null) {
		// Get booking object if ID provided
		if (is_numeric($booking)) {
			$booking = $this->findById($booking);
			if (empty($booking)) {
				return false;
			}
		}

		if (empty($booking)) {
			return false;
		}

		// Calculate number of days if not provided
		if ($days === null) {
			if (empty($booking->checkindate) || empty($booking->checkoutdate)) {
				return false;
			}
			$datediff = (strtotime($booking->checkoutdate) - strtotime($booking->checkindate)) / (60 * 60 * 24);
			$days = ceil($datediff);
			if ($days < 1) {
				$days = 1; // Minimum 1 day
			}
		}

		// Step 1: Base rent (per day * number of days)
		$baseRent = !empty($booking->total_price) ? floatval($booking->total_price) : 0;
		$totalRent = $baseRent * $days;

		// Step 2: Calculate tax on base rent × days
		$totalTax = $this->calculateTax($totalRent, false);

		// Step 3: Get service charge percentage
		$scharge_setting = $this->db->select("servicecharge")->from("setting")->get()->row();
		$serviceChargeRate = !empty($scharge_setting->servicecharge) ? floatval($scharge_setting->servicecharge) : 0;

		// Step 4: Calculate service charge on base rent × days
		$totalScharge = 0;
		if ($serviceChargeRate > 0 && $totalRent > 0) {
			$totalScharge = ($totalRent * $serviceChargeRate) / 100;
		}

		// Step 5: Get parking charges
		$totalParking = 0;
		$car_parking = $this->db->where('directory', 'car_parking')->where('status', 1)->get('module')->num_rows();
		if ($car_parking == 1 && !empty($booking->bookedid)) {
			$parking_records = $this->db->select("total_price")
				->from("tbl_bookParking")
				->where("bookedid", $booking->bookedid)
				->get()
				->result();
			if (!empty($parking_records)) {
				foreach ($parking_records as $cp) {
					$totalParking += floatval($cp->total_price);
				}
			}
		}

		// Step 6: Get discount amount
		$discountAmount = !empty($booking->discountamount) ? floatval($booking->discountamount) : 0;

		// Step 7: Calculate subtotal (rent + tax + service charge + parking)
		$subtotal = $totalRent + $totalTax + $totalScharge + $totalParking;

		// Step 8: Apply discount to get total after discount
		$totalAfterDiscount = $subtotal - $discountAmount;
		// Ensure total doesn't go negative
		if ($totalAfterDiscount < 0) {
			$totalAfterDiscount = 0;
		}

		// Step 9: Calculate due amount (total after discount - paid amount)
		$paidAmount = !empty($booking->paid_amount) ? floatval($booking->paid_amount) : 0;
		$due = $totalAfterDiscount - $paidAmount;
		// Ensure due doesn't go negative
		if ($due < 0) {
			$due = 0;
		}

		// Return detailed breakdown
		return array(
			'days' => $days,
			'baseRentPerDay' => $baseRent,
			'totalRent' => $totalRent,
			'totalTax' => $totalTax,
			'serviceChargeRate' => $serviceChargeRate,
			'totalScharge' => $totalScharge,
			'totalParking' => $totalParking,
			'subtotal' => $subtotal,
			'discountAmount' => $discountAmount,
			'totalAfterDiscount' => $totalAfterDiscount,
			'paidAmount' => $paidAmount,
			'due' => $due
		);
	}
		public function calculateFullBookingTotal($booking) {
	

		// Calculate days
		$datediff = (strtotime($booking->checkoutdate) - strtotime($booking->checkindate)) / (60 * 60 * 24);
		$days = ceil($datediff > 0 ? $datediff : 1);

		// Base rent (total_price is per day, multiply by days)
		$baseRent = floatval($booking->total_price ?? 0) * $days;
		
		// Get discount amount (from booked_details table - now available via join or direct property)
		// Check if discountamount is already available from join, otherwise query
		$discountAmount = 0;
		if (isset($booking->discountamount)) {
			// Discount is already joined in the query
			$discountAmount = floatval($booking->discountamount ?? 0) * $days;
		} else {
			// Fallback: Query if not joined (backward compatibility)
			$discountQuery = $this->db->select('discountamount')
				->from('booked_details')
				->where('bookedid', $booking->bookedid)
				->get();
			if ($discountQuery->num_rows() > 0) {
				$discountAmount = floatval($booking->discountamount ?? 0);

				//$discountAmount = floatval($discountQuery->row()->discountamount ?? 0) * $days;
			}
		}

		// Apply discount to base rent only
		$baseRentAfterDiscount = $baseRent - $discountAmount;
	

		$baseRentAfterDiscount = max(0, $baseRentAfterDiscount);

		// Calculate tax on base rent (before discount)
		$taxes = $this->db->select("rate")
			->from("tbl_taxmgt")
			->where("isactive", 1)
			->get()
			->result();
		$totalTax = 0;
		if (!empty($taxes)) {
			foreach ($taxes as $tax) {
				$totalTax += ($baseRent * floatval($tax->rate ?? 0)) / 100;
			}
		}

		// Calculate service charge on base rent (before discount)
		$setting = $this->db->select("servicecharge")
			->from("setting")
			->get()
			->row();
		$serviceCharge = 0;
		if (!empty($setting->servicecharge)) {
			$serviceCharge = ($baseRent * floatval($setting->servicecharge)) / 100;
		}

		// Total = (base rent - discount) + tax + service charge
		$fullTotal = $baseRentAfterDiscount + $totalTax + $serviceCharge;
		
			return (float)$fullTotal;
	}

}
