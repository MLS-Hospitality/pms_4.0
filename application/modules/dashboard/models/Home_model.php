<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Home_model extends CI_Model {


	public function checkUser($data = array())
	{
		return $this->db->select("
				user.id,
				CONCAT_WS(' ', user.firstname, user.lastname) AS fullname,
				user.email,
				user.image,
				user.last_login,
				user.last_logout,
				user.ip_address,
				user.status,
				user.is_admin,
				IF (user.is_admin=1, 'Admin', 'User') as user_level
			")
			->from('user')
			->where('email', $data['email'])
			->where('password', md5($data['password']))
			->get();
	}

	public function userPermission($id = null)
	{
		return $this->db->select("
			module.controller,
			module_permission.fk_module_id,
			module_permission.create,
			module_permission.read,
			module_permission.update,
			module_permission.delete
			")
			->from('module_permission')
			->join('module', 'module.id = module_permission.fk_module_id', 'full')
			->where('module_permission.fk_user_id', $id)
			->get()
			->result();
	}


	public function last_login($id = null)
	{
		return $this->db->set('last_login', date('Y-m-d H:i:s'))
			->set('ip_address', $this->input->ip_address())
			->where('id',$this->session->userdata('id'))
			->update('user');
	}

	public function last_logout($id = null)
	{
		return $this->db->set('last_logout', date('Y-m-d H:i:s'))
			->where('id', $this->session->userdata('id'))
			->update('user');
	}

	public function profile($id = null)
	{
		return $this->db->select("
			*,
				CONCAT_WS(' ', firstname, lastname) AS fullname,
				IF (user.is_admin=1, 'Admin', 'User') as user_level
			")
			->from("user")
			->where("id", $id)
			->get()
			->row();
	}

	public function setting($data = array())
	{
		return $this->db->where('id', $data['id'])
			->update('user', $data);
	}

	public function countorder()
	{
		$this->db->select('*');
        $this->db->from('booked_info');
		$this->db->where('bookingstatus=', 5);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return 0;
	}
	public function countcheckin()
	{
		$this->db->select('*');
        $this->db->from('booked_info');
		$this->db->where('bookingstatus=', 4);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return 0;
	}
	public function countpending()
	{
		$this->db->select('*');
        $this->db->from('booked_info');
		 $this->db->where('bookingstatus=', 0);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return 0;
	}
	public function countcancel()
	{
		$this->db->select('*');
        $this->db->from('booked_info');
		 $this->db->where('bookingstatus=', 1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return 0;
	}
	public function countcompleteorder()
	{
		$this->db->select('*');
        $this->db->from('customer_order');
		 $this->db->where('order_status', 4);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return 0;
	}

	/**
	 * Get today's bookings count (based on check-in date)
	 * FIXED: Now uses checkindate instead of date_time (booking creation date)
	 * This is consistent with todayorderlist() and makes more sense for "today's bookings"
	 */
	public function todayorder()
	{
		$today=date('Y-m-d');
		$this->db->select('*');
        $this->db->from('booked_info');
		$this->db->where('DATE(checkindate)', $today);
		$this->db->where('bookingstatus!=', 1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return 0;
	}

	public function totalcustomer()
	{
		$this->db->select('*');
        $this->db->from('customerinfo');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return 0;
	}
	public function customerlist()
	{
		$this->db->select('*');
        $this->db->from('customerinfo');
		$this->db->order_by('customerid', 'DESC');
		$this->db->limit(50);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
	}

	/**
	 * Calculate total revenue across all bookings
	 * FIXED: Now calculates full booking total including taxes, service charges, and discounts
	 * OPTIMIZED: Joins booked_details to avoid N+1 queries
	 */
	public function totalamount()
	{
		$this->db->select('booked_info.*, booked_details.discountamount');
		$this->db->from('booked_info');
		$this->db->join('booked_details', 'booked_details.bookedid = booked_info.bookedid', 'left');
		$this->db->where('booked_info.bookingstatus!=', 1); // Exclude cancelled
		$query = $this->db->get();
		$bookings = $query->result();

		$totalRevenue = 0;
		foreach ($bookings as $booking) {
			$fullTotal = $this->calculateFullBookingTotal($booking);
			$totalRevenue += $fullTotal;
		}

		// Return as object with 'amount' property for backward compatibility
		$result = new stdClass();
		$result->amount = $totalRevenue;
		return $result;
	}
	public function totalreservation()
	{
		$this->db->select('*');
        $this->db->from('tblreservation');
		$this->db->where('status', '5');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows();
        }
        return 0;
	}
	public function todayorderlist()
	{
		$today=date('Y-m-d');
		$this->db->select('booked_info.*,customerinfo.cust_phone,customerinfo.firstname,customerinfo.lastname');
        $this->db->from('booked_info');
		$this->db->join('customerinfo','booked_info.cutomerid=customerinfo.customerid','left');
		$this->db->where('DATE(booked_info.checkindate)', $today);
		$this->db->where('booked_info.bookingstatus!=', 1);
		$this->db->order_by('booked_info.bookedid', 'DESC');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
	}
	public function nextdayorderlist()
	{
	    $nextDate = date("Y-m-d", strtotime("+ 1 day"));
		$this->db->select('booked_info.*,customerinfo.cust_phone,customerinfo.firstname,customerinfo.lastname');
        $this->db->from('booked_info');
		$this->db->join('customerinfo','booked_info.cutomerid=customerinfo.customerid','left');
		$this->db->where('DATE(booked_info.checkindate)', $nextDate);
		$this->db->where('booked_info.bookingstatus!=', 1);
		$this->db->order_by('booked_info.bookedid', 'DESC');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
	}
	public function latestoredercount()
	{
		$this->db->select('*');
        $this->db->from('booked_info');
		$this->db->where('isSeen', 0);
		$this->db->or_where('isSeen',NULL);
		$this->db->order_by('booking_number', 'DESC');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->num_rows;
        }
        return 0;
	}
	public function latestonline()
	{
		$this->db->select('customer_order.*,customer_info.customer_name,customer_info.customer_phone,rest_table.tablename');
        $this->db->from('customer_order');
		$this->db->join('customer_info','customer_order.customer_id=customer_info.customer_id','left');
		$this->db->join('rest_table','customer_order.table_no=rest_table.tableid','left');
		$this->db->where('order_status!=', 5);
		$this->db->where('cutomertype', 2);
		$this->db->order_by('saleinvoice', 'DESC');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
	}
	public function latestreservation()
	{
		$this->db->select('tblreservation.*,customer_info.customer_name,customer_info.customer_phone,rest_table.tablename');
        $this->db->from('tblreservation');
		$this->db->join('customer_info','tblreservation.cid=customer_info.customer_id','left');
		$this->db->join('rest_table','tblreservation.tableid=rest_table.tableid','left');
		$this->db->where('tblreservation.status', 2);
		$this->db->order_by('tblreservation.reserveday', 'DESC');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
	}
	public function latestpending()
	{
		$this->db->select('customer_order.*,customer_info.customer_name,customer_info.customer_phone,rest_table.tablename');
        $this->db->from('customer_order');
		$this->db->join('customer_info','customer_order.customer_id=customer_info.customer_id','left');
		$this->db->join('rest_table','customer_order.table_no=rest_table.tableid','left');
		$this->db->where('order_status', 1);
		$this->db->order_by('saleinvoice', 'DESC');
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
	}

	/**
	 * Get monthly booking amount
	 * CRITICAL FIX: Removed the buggy condition 'total_price=paid_amount' that only counted fully paid bookings
	 * Now calculates full booking total including taxes, service charges, and discounts for ALL bookings
	 * OPTIMIZED: Joins booked_details to avoid N+1 queries
	 */
	public function monthlybookingamount($year,$month)
		{
			// Get all bookings for the specified month (excluding cancelled)
			$wherequery = "YEAR(booked_info.date_time)='$year' AND MONTH(booked_info.date_time)='$month' AND booked_info.bookingstatus!=1";
			$this->db->select('booked_info.*, booked_details.discountamount');
			$this->db->from('booked_info');
			$this->db->join('booked_details', 'booked_details.bookedid = booked_info.bookedid', 'left');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();

			if ($query->num_rows() > 0) {
				$bookings = $query->result();
				$totalAmount = 0;

				// Calculate full booking total for each booking
				foreach($bookings as $booking){
					$fullTotal = $this->calculateFullBookingTotal($booking);
					$totalAmount += $fullTotal;
				}

				return $totalAmount > 0 ? $totalAmount : 0;
			}
			return 0;
		}
	public function monthlybookingorder($year,$month)
		{
			$groupby="GROUP BY YEAR(date_time), MONTH(date_time)";
			$totalorder='';
			$wherequery = "YEAR(date_time)='$year' AND month(date_time)='$month' AND bookingstatus=5 GROUP BY YEAR(date_time), MONTH(date_time)";
			$this->db->select('count(bookedid) as totalorder');
			$this->db->from('booked_info');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				$result=$query->result();
				foreach($result as $row){
					$totalorder.=$row->totalorder.", ";
					}
				return trim($totalorder,', ');
			}
			return 0;
		}
		public function monthlybookingpending($year,$month)
		{
			$groupby="GROUP BY YEAR(date_time), MONTH(date_time)";
			$totalorder='';
			$wherequery = "YEAR(date_time)='$year' AND month(date_time)='$month' AND bookingstatus=0 GROUP BY YEAR(date_time), MONTH(date_time)";
			$this->db->select('count(bookedid) as totalorder');
			$this->db->from('booked_info');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				$result=$query->result();
				foreach($result as $row){
					$totalorder.=$row->totalorder.", ";
					}
				return trim($totalorder,', ');
			}
			return 0;
		}
		public function monthlybookingcancel($year,$month)
		{
			$groupby="GROUP BY YEAR(date_time), MONTH(date_time)";
			$totalorder='';
			$wherequery = "YEAR(date_time)='$year' AND month(date_time)='$month' AND bookingstatus=1 GROUP BY YEAR(date_time), MONTH(date_time)";
			$this->db->select('count(bookedid) as totalorder');
			$this->db->from('booked_info');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				$result=$query->result();
				foreach($result as $row){
					$totalorder.=$row->totalorder.", ";
					}
				return trim($totalorder,', ');
			}
			return 0;
		}
		public function monthlybookingtotal($year,$month)
		{
			$groupby="GROUP BY YEAR(date_time), MONTH(date_time)";
			$totalorder='';
			$wherequery = "YEAR(date_time)='$year' AND month(date_time)='$month' AND bookingstatus!=1 GROUP BY YEAR(date_time), MONTH(date_time)";
			$this->db->select('count(bookedid) as totalorder');
			$this->db->from('booked_info');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				$result=$query->result();
				foreach($result as $row){
					$totalorder.=$row->totalorder.", ";
					}
				return trim($totalorder,', ');
			}
			return 0;
		}
	public function onlinesaleamount($year,$month)
		{
			$groupby="GROUP BY YEAR(order_date), MONTH(order_date)";
			$amount='';
			$wherequery = "YEAR(order_date)='$year' AND month(order_date)='$month' AND cutomertype=2 AND order_status!=5 GROUP BY YEAR(order_date), MONTH(order_date)";
			$this->db->select('SUM(totalamount) as amount');
			$this->db->from('customer_order');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				$result=$query->result();
				foreach($result as $row){
					$amount.=$row->amount.", ";
					}
				return trim($amount,', ');
			}
			return 0;
		}
	public function onlinesaleorder($year,$month)
		{
			$groupby="GROUP BY YEAR(order_date), MONTH(order_date)";
			$totalorder='';
			$wherequery = "YEAR(order_date)='$year' AND month(order_date)='$month' AND cutomertype=2 AND order_status!=5 GROUP BY YEAR(order_date), MONTH(order_date)";
			$this->db->select('count(order_id) as totalorder');
			$this->db->from('customer_order');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				$result=$query->result();
				foreach($result as $row){
					$totalorder.=$row->totalorder.", ";
					}
				return trim($totalorder,', ');
			}
			return 0;
		}

	public function offlinesaleamount($year,$month)
		{
			$groupby="GROUP BY YEAR(order_date), MONTH(order_date)";
			$amount='';
			$wherequery = "YEAR(order_date)='$year' AND month(order_date)='$month' AND cutomertype=1 AND order_status!=5 GROUP BY YEAR(order_date), MONTH(order_date)";
			$this->db->select('SUM(totalamount) as amount');
			$this->db->from('customer_order');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				$result=$query->result();
				foreach($result as $row){
					$amount.=$row->amount.", ";
					}
				return trim($amount,', ');
			}
			return 0;
		}
	public function offlinesaleorder($year,$month)
		{
			$groupby="GROUP BY YEAR(order_date), MONTH(order_date)";
			$totalorder='';
			$wherequery = "YEAR(order_date)='$year' AND month(order_date)='$month' AND cutomertype=1 AND order_status!=5 GROUP BY YEAR(order_date), MONTH(order_date)";
			$this->db->select('count(order_id) as totalorder');
			$this->db->from('customer_order');
			$this->db->where($wherequery, NULL, FALSE);
			$query = $this->db->get();
			if ($query->num_rows() > 0) {
				$result=$query->result();
				foreach($result as $row){
					$totalorder.=$row->totalorder.", ";
					}
				return trim($totalorder,', ');
			}
			return 0;
		}

	// ========================================================================
	// ENHANCED DASHBOARD COUNTERS/WIDGETS WITH ACCURATE CALCULATIONS
	// ========================================================================

	/**
	 * Calculate full booking total including tax, service charge, and discount
	 *
	 * This is a CRITICAL helper method used by all revenue calculations to ensure accuracy.
	 *
	 * Formula: (base_rent * days - discount) + tax + service_charge
	 * - Base rent is calculated per day and multiplied by number of days
	 * - Discount is applied to the base rent
	 * - Tax is calculated on base rent (before discount)
	 * - Service charge is calculated on base rent (before discount)
	 *
	 * @param object $booking Booking object from booked_info
	 * @return float Full booking total (always >= 0)
	 */
	private function calculateFullBookingTotal($booking) {
	

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
			$discountAmount = floatval($booking->discountamount ?? 0);
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

	/**
	 * Get today's reservations count
	 * FIXED: Counts all active reservations for today (checking in today OR already checked in and staying today)
	 * This includes:
	 * - Reservations checking in today (checkindate = today)
	 * - Reservations already checked in and staying today (checkindate <= today AND checkoutdate >= today AND status = checked in)
	 * - Excludes cancelled bookings
	 */
	public function todayReservations()
	{
		$today = date('Y-m-d');

		// Count distinct bookings that are active today
		// Active means: checking in today OR already checked in and staying today
		$whereClause = "bookingstatus != 1 AND (
			DATE(checkindate) = '$today'
			OR (DATE(checkindate) <= '$today' AND DATE(checkoutdate) >= '$today' AND bookingstatus = 4)
		)";

		$this->db->select('COUNT(DISTINCT bookedid) as reservations_count');
		$this->db->from('booked_info');
		$this->db->where($whereClause, NULL, FALSE);

		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->reservations_count : 0;
	}

	/**
	 * Get today's paid amount
	 */
	public function todayPaidAmount()
	{
		$today = date('Y-m-d');
		$this->db->select('SUM(paymentamount) as total_paid');
		$this->db->from('tbl_guestpayments');
		$this->db->where('DATE(paydate)', $today);
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_paid : 0;
	}

	/**
	 * Get pending amount (full booking total - actual paid amount)
	 * FIXED: Properly handles partial payments by summing all payment records
	 * Handles bookedid as both INT and VARCHAR for compatibility
	 * Excludes cancelled bookings and only counts bookings with actual pending amounts
	 */
	public function pendingAmount()
	{
		// Only include bookings that are not cancelled and not fully checked out
		// bookingstatus: 0=pending, 2=checked in, 3=checked out, 1=cancelled
		$this->db->select('booked_info.*, booked_details.advance_amount, booked_details.discountamount');
		$this->db->from('booked_info');
		$this->db->join('booked_details', 'booked_details.bookedid = booked_info.bookedid', 'left');
		$this->db->where('booked_info.bookingstatus !=', 1);
	
		$query = $this->db->get();
		$bookings = $query->result();
		$totalPending =0;

		
		foreach ($bookings as $index=>$booking) {
			
			
			// Calculate full booking total (base rent + tax + service charge - discount)
			$fullTotal = $this->calculateFullBookingTotal($booking);
			
		 

   
   
			// Skip if full total is 0 or invalid
			if ($fullTotal <= 0) {
				continue;
			}

			// Get actual total paid from payments table
			// Handle bookedid as both INT and VARCHAR (tbl_guestpayments uses VARCHAR)
			$bookedidStr = (string)$booking->bookedid;
			$bookedidInt = (int)$booking->bookedid;

			// Use raw SQL query to properly handle type conversion and sum all payments
			// This handles both cases: multiple payment records (INSERT) and single updated record (UPDATE)
			// Handles bookedid stored as both VARCHAR and INT
			// Try multiple query approaches to ensure we find all payments
			$paymentsPaid = 0;

			// Approach 1: Try as string (most common in tbl_guestpayments)
			$sql1 = "SELECT COALESCE(SUM(CAST(paymentamount AS DECIMAL(10,2))), 0) as actual_paid
					FROM tbl_guestpayments
					WHERE book_type = 0 AND bookedid = ?";
			$paymentQuery1 = $this->db->query($sql1, [$bookedidStr]);
			if ($paymentQuery1 && $paymentQuery1->num_rows() > 0) {
				$result1 = $paymentQuery1->row();
				if (!empty($result1->actual_paid) && $result1->actual_paid !== null) {
					$paymentsPaid = max($paymentsPaid, floatval($result1->actual_paid));
				}
			}

			// Approach 2: Try as integer (fallback)
			$sql2 = "SELECT COALESCE(SUM(CAST(paymentamount AS DECIMAL(10,2))), 0) as actual_paid
					FROM tbl_guestpayments
					WHERE book_type = 0 AND CAST(bookedid AS UNSIGNED) = ?";
			$paymentQuery2 = $this->db->query($sql2, [$bookedidInt]);
			if ($paymentQuery2 && $paymentQuery2->num_rows() > 0) {
				$result2 = $paymentQuery2->row();
				if (!empty($result2->actual_paid) && $result2->actual_paid !== null) {
					$paymentsPaid = max($paymentsPaid, floatval($result2->actual_paid));
				}
			}
			// Get advance_amount and paid_amount as fallbacks
			$advanceAmount = floatval($booking->advance_amount ?? 0);

			$bookedPaidAmount = floatval($booking->paid_amount ?? 0);
			

			// Determine actual paid amount - use the MAXIMUM of all available sources
			// This ensures we don't undercount payments if one source is outdated
			// Priority: Use the highest value among all payment sources
			$actualPaid = max($paymentsPaid, $advanceAmount, $bookedPaidAmount);
	

			// However, if paymentsPaid > 0, prefer it (most accurate for partial payments)
			// But if paid_amount is higher, it might be more up-to-date (updated during customer payment)
			if ($paymentsPaid > 0 && $bookedPaidAmount > $paymentsPaid) {
				// If paid_amount is higher than sum of payments, use paid_amount
				// This handles cases where payments were made but not all recorded in tbl_guestpayments
				$actualPaid = $bookedPaidAmount;
			} elseif ($paymentsPaid > 0) {
				// Use sum of payments if it's the highest or equal
				$actualPaid = $paymentsPaid;
			}
			// Calculate pending amount
			$pending = $fullTotal - $actualPaid;
			

// Store per-booking data
$data[$index] = [
    'bookedid'   => $booking->bookedid,
    'full_total' => $fullTotal,
    'booked_paid'       => $actualPaid,
	'payment_paid'=>$paymentsPaid,
    'pending'    => $pending,
];

			// Only count as pending if difference is more than 0.01 (rounding tolerance)
			// This ensures fully paid bookings (pending <= 0.01) are not counted
			// Also ensure we don't have negative pending (overpaid)
			if ($pending > 0.01) {
				$totalPending += $pending;
			}
		}
		
		
		$data['total_pending'] = max(0, $totalPending);
		
		
		return max(0, $totalPending); // Ensure non-negative
	}

	/**
	 * Get total completed bookings (checked out)
	 */
	public function totalCompletedBookings()
	{
		$this->db->select('*');
		$this->db->from('booked_info');
		$this->db->where('bookingstatus', 5); // Checked out
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get current month revenue
	 */
	public function currentMonthRevenue()
	{
		$this->db->select('SUM(paymentamount) as monthly_revenue');
		$this->db->from('tbl_guestpayments');
		$this->db->where('MONTH(paydate)', date('m'));
		$this->db->where('YEAR(paydate)', date('Y'));
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->monthly_revenue : 0;
	}

	/**
	 * Get occupancy rate for today
	 * CRITICAL FIX: Count all occupied rooms (checked in status or checked out today)
	 */
	public function todayOccupancyRate()
	{
		$today = date('Y-m-d');

		// Total rooms
		$this->db->select('COUNT(*) as total_rooms');
		$this->db->from('tbl_roomnofloorassign');
		$total_rooms_query = $this->db->get();
		$total_rooms = $total_rooms_query->row()->total_rooms;
		

		if ($total_rooms <= 0) {
			return 0;
		}

		// Occupied rooms today
		// Rooms are occupied if: checkindate <= today AND checkoutdate >= today AND status is checked in (4)
		$this->db->select('COUNT(DISTINCT room_no) as occupied_rooms');
		$this->db->from('booked_info');
		$this->db->where('DATE(checkindate) <=', $today);
		$this->db->where('DATE(checkoutdate) >=', $today);
		$this->db->where('bookingstatus', 4); // Currently checked in
		$occupied_query = $this->db->get();
		$occupied_rooms = $occupied_query->row()->occupied_rooms ?? 0;
		return round(($occupied_rooms / $total_rooms) * 100, 1);
	}

	/**
	 * Get average daily rate (ADR)
	 * CRITICAL FIX: Calculate ADR using full booking total, not just base rent
	 * OPTIMIZED: Joins booked_details to avoid N+1 queries
	 */
	public function averageDailyRate()
	{
		$this->db->select('booked_info.*, booked_details.discountamount');
		$this->db->from('booked_info');
		$this->db->join('booked_details', 'booked_details.bookedid = booked_info.bookedid', 'left');
		$this->db->where('booked_info.bookingstatus !=', 1); // Exclude cancelled
		$this->db->where('MONTH(booked_info.date_time)', date('m'));
		$this->db->where('YEAR(booked_info.date_time)', date('Y'));
		$query = $this->db->get();
		$bookings = $query->result();

		if (empty($bookings)) {
			return 0;
		}

		$totalRevenue = 0;
		$totalNights = 0;

		foreach ($bookings as $booking) {
			
			$fullTotal = $this->calculateFullBookingTotal($booking);
			$datediff = (strtotime($booking->checkoutdate) - strtotime($booking->checkindate)) / (60 * 60 * 24);
			$nights = ceil($datediff > 0 ? $datediff : 1);

			$totalRevenue += $fullTotal;
			$totalNights += $nights;
		}

		if ($totalNights > 0) {
			return round($totalRevenue / $totalNights, 2);
		}

		return 0;
	}

	/**
	 * Get customers checked-in today
	 * FIXED: Counts only customers who actually checked in TODAY (checkindate = today)
	 * This is different from "currently checked in" which includes customers who checked in earlier
	 */
	public function customersCheckedInToday()
	{
		$today = date('Y-m-d');
		$this->db->select('COUNT(DISTINCT cutomerid) as customers_count');
		$this->db->from('booked_info');
		$this->db->where('DATE(checkindate)', $today); // Only those who checked in TODAY
		$this->db->where_in('bookingstatus', [4, 5]); // Checked in or checked out (actual check-ins today)
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->customers_count : 0;
	}

	/**
	 * Get actual check-ins that happened today
	 */
	public function todayCheckIns()
	{
		$today = date('Y-m-d');
		$this->db->select('*');
		$this->db->from('booked_info');
		$this->db->where('DATE(checkindate)', $today);
		$this->db->where_in('bookingstatus', [4, 5]); // Checked in or checked out (actual check-ins)
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get actual check-outs that happened today
	 */
	public function todayCheckOuts()
	{
		$today = date('Y-m-d');
		$this->db->select('*');
		$this->db->from('booked_info');
		$this->db->where('DATE(checkoutdate)', $today);
		$this->db->where('bookingstatus', 5); // Actually checked out
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get revenue comparison (this month vs last month)
	 */
	public function revenueComparison()
	{
		// Current month
		$this->db->select('SUM(paymentamount) as current_month');
		$this->db->from('tbl_guestpayments');
		$this->db->where('MONTH(paydate)', date('m'));
		$this->db->where('YEAR(paydate)', date('Y'));
		$current_query = $this->db->get();
		$current_month = $current_query->row()->current_month ?: 0;

		// Last month
		$last_month = date('m') - 1;
		$year = date('Y');
		if ($last_month == 0) {
			$last_month = 12;
			$year = date('Y') - 1;
		}

		$this->db->select('SUM(paymentamount) as last_month');
		$this->db->from('tbl_guestpayments');
		$this->db->where('MONTH(paydate)', $last_month);
		$this->db->where('YEAR(paydate)', $year);
		$last_query = $this->db->get();
		$last_month_amount = $last_query->row()->last_month ?: 0;

		$percentage = 0;
		if ($last_month_amount > 0) {
			$percentage = round((($current_month - $last_month_amount) / $last_month_amount) * 100, 1);
		}

		return [
			'current_month' => $current_month,
			'last_month' => $last_month_amount,
			'percentage' => $percentage
		];
	}

	/**
	 * Get top performing room types
	 * Shows base room rent only (excludes taxes, service charges, and discounts)
	 */
	public function topRoomTypes()
	{
		$this->db->select('booked_info.*, roomdetails.roomtype');
		$this->db->from('booked_info');
		$this->db->join('tbl_roomnofloorassign', 'FIND_IN_SET(tbl_roomnofloorassign.roomno, booked_info.room_no)', 'left');
		$this->db->join('roomdetails', 'roomdetails.roomid = tbl_roomnofloorassign.roomid', 'left');
		$this->db->where('booked_info.bookingstatus !=', 1);
		$this->db->where('MONTH(booked_info.date_time)', date('m'));
		$this->db->where('YEAR(booked_info.date_time)', date('Y'));
		$this->db->where('roomdetails.roomtype IS NOT NULL');
		$query = $this->db->get();
		$bookings = $query->result();

		// Group by room type and calculate totals
		$roomTypeStats = [];
		foreach ($bookings as $booking) {
			$roomType = $booking->roomtype;
			if (empty($roomType)) {
				continue;
			}

			if (!isset($roomTypeStats[$roomType])) {
				$roomTypeStats[$roomType] = [
					'bookings' => 0,
					'revenue' => 0
				];
			}

			$roomTypeStats[$roomType]['bookings']++;

			// Calculate only base room rent (room rate × number of days)
			$datediff = (strtotime($booking->checkoutdate) - strtotime($booking->checkindate)) / (60 * 60 * 24);
			$days = ceil($datediff > 0 ? $datediff : 1);
			$baseRent = floatval($booking->total_price ?? 0) * $days;

			$roomTypeStats[$roomType]['revenue'] += $baseRent;
		}

		// Convert to objects and sort by revenue
		$results = [];
		foreach ($roomTypeStats as $roomType => $stats) {
			$obj = new stdClass();
			$obj->roomtype = $roomType;
			$obj->bookings = $stats['bookings'];
			$obj->revenue = $stats['revenue'];
			$results[] = $obj;
		}

		// Sort by revenue descending
		usort($results, function($a, $b) {
			return $b->revenue <=> $a->revenue;
		});

		return array_slice($results, 0, 5);
	}

	/**
	 * Get recent activities for dashboard
	 */
	public function recentActivities()
	{
		$activities = [];

		// Recent bookings
		$this->db->select('booked_info.*, customerinfo.firstname, customerinfo.lastname');
		$this->db->from('booked_info');
		$this->db->join('customerinfo', 'booked_info.cutomerid = customerinfo.customerid', 'left');
		$this->db->order_by('booked_info.date_time', 'DESC');
		$this->db->limit(5);
		$bookings = $this->db->get()->result();

		foreach ($bookings as $booking) {
			$activities[] = [
				'type' => 'booking',
				'message' => 'New booking by ' . $booking->firstname . ' ' . $booking->lastname,
				'time' => $booking->date_time,
				'status' => $booking->bookingstatus
			];
		}

		// Recent payments
		$this->db->select('tbl_guestpayments.*, booked_info.booking_number');
		$this->db->from('tbl_guestpayments');
		$this->db->join('booked_info', 'tbl_guestpayments.bookedid = booked_info.bookedid', 'left');
		$this->db->order_by('tbl_guestpayments.paydate', 'DESC');
		$this->db->limit(5);
		$payments = $this->db->get()->result();

		foreach ($payments as $payment) {
			$activities[] = [
				'type' => 'payment',
				'message' => 'Payment received for booking #' . $payment->booking_number,
				'time' => $payment->paydate,
				'amount' => $payment->paymentamount
			];
		}

		// Sort by time
		usort($activities, function($a, $b) {
			return strtotime($b['time']) - strtotime($a['time']);
		});

		return array_slice($activities, 0, 10);
	}

	// ========================================================================
	// RESTAURANT ORDER STATISTICS
	// ========================================================================

	/**
	 * Get today's restaurant orders count
	 */
	public function todayRestaurantOrders()
	{
		$today = date('Y-m-d');
		$this->db->select('*');
		$this->db->from('customer_order');
		$this->db->where('DATE(order_date)', $today);
		$this->db->where('order_status !=', 5); // Exclude cancelled
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get pending restaurant orders count
	 */
	public function pendingRestaurantOrders()
	{
		$this->db->select('*');
		$this->db->from('customer_order');
		$this->db->where('order_status', 1); // Pending
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get today's restaurant revenue
	 */
	public function todayRestaurantRevenue()
	{
		$today = date('Y-m-d');
		$this->db->select('SUM(totalamount) as total_revenue');
		$this->db->from('customer_order');
		$this->db->where('DATE(order_date)', $today);
		$this->db->where('order_status !=', 5); // Exclude cancelled
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get total restaurant orders (all time, excluding cancelled)
	 */
	public function totalRestaurantOrders()
	{
		$this->db->select('*');
		$this->db->from('customer_order');
		$this->db->where('order_status !=', 5); // Exclude cancelled
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get total restaurant revenue (all time, excluding cancelled)
	 */
	public function totalRestaurantRevenue()
	{
		$this->db->select('SUM(totalamount) as total_revenue');
		$this->db->from('customer_order');
		$this->db->where('order_status !=', 5); // Exclude cancelled
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get processing restaurant orders count (currently being prepared)
	 */
	public function processingRestaurantOrders()
	{
		$this->db->select('*');
		$this->db->from('customer_order');
		$this->db->where('order_status', 2); // Processing
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get ready restaurant orders count (ready to serve)
	 */
	public function readyRestaurantOrders()
	{
		$this->db->select('*');
		$this->db->from('customer_order');
		$this->db->where('order_status', 3); // Ready
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get recent restaurant orders list
	 */
	public function recentRestaurantOrders($limit = 10)
	{
		$this->db->select('customer_order.*, customerinfo.firstname, customerinfo.lastname, customer_type.customer_type, rest_table.tablename');
		$this->db->from('customer_order');
		$this->db->join('customerinfo', 'customer_order.customer_id = customerinfo.customerid', 'left');
		$this->db->join('customer_type', 'customer_order.cutomertype = customer_type.customer_type_id', 'left');
		$this->db->join('rest_table', 'customer_order.table_no = rest_table.tableid', 'left');
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->order_by('customer_order.order_id', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();
		return $query->result();
	}

	/**
	 * Get current month restaurant revenue (only paid/completed orders - status 4 and 6)
	 * Status 4 = Served, Status 6 = Hotel Order
	 */
	public function currentMonthRestaurantRevenue()
	{
		$this->db->select('SUM(totalamount) as monthly_revenue');
		$this->db->from('customer_order');
		$this->db->where('MONTH(order_date)', date('m'));
		$this->db->where('YEAR(order_date)', date('Y'));
		$this->db->where_in('order_status', [4, 6]); // Served and Hotel Order
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->monthly_revenue : 0;
	}

	/**
	 * Get current month restaurant orders count
	 */
	public function currentMonthRestaurantOrders()
	{
		$this->db->select('*');
		$this->db->from('customer_order');
		$this->db->where('MONTH(order_date)', date('m'));
		$this->db->where('YEAR(order_date)', date('Y'));
		$this->db->where('order_status !=', 5); // Exclude cancelled
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get today's restaurant orders count (Active + Pending + Paid)
	 * Active = Processing (2) + Ready (3)
	 * Pending = 1
	 * Paid = Served (4)
	 */
	public function todayRestaurantOrdersAll()
	{
		$today = date('Y-m-d');
		$this->db->select('*');
		$this->db->from('customer_order');
		$this->db->where('DATE(order_date)', $today);
		$this->db->where_in('order_status', [1, 2, 3, 4]); // Pending, Processing, Ready, Served
		$query = $this->db->get();
		return $query->num_rows();
	}

	/**
	 * Get today's paid restaurant revenue (only Served/Paid orders - status 4 and 6)
	 * Status 4 = Served, Status 6 = Hotel Order
	 */
	public function todayPaidRestaurantRevenue()
	{
		$today = date('Y-m-d');
		$this->db->select('SUM(totalamount) as total_revenue');
		$this->db->from('customer_order');
		$this->db->where('DATE(order_date)', $today);
		$this->db->where_in('order_status',4); // Served
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	// ========================================================================
	// FOOD AND BEVERAGE REPORTS
	// ========================================================================

	/**
	 * Get top selling food items
	 */
	public function topSellingFoodItems($limit = 10)
	{
		$this->db->select('item_foods.ProductName, item_foods.ProductsID, item_category.Name as CategoryName,
			SUM(order_menu.menuqty) as total_quantity,
			SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->join('item_category', 'item_foods.CategoryID = item_category.CategoryID', 'left');
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->where('item_foods.menutype', 'food'); // Food items only
		$this->db->group_by('order_menu.menu_id');
		$this->db->order_by('total_quantity', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();
		return $query->result();
	}

	/**
	 * Get top selling beverage items
	 */
	public function topSellingBeverageItems($limit = 10)
	{
		$this->db->select('item_foods.ProductName, item_foods.ProductsID, item_category.Name as CategoryName,
			SUM(order_menu.menuqty) as total_quantity,
			SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->join('item_category', 'item_foods.CategoryID = item_category.CategoryID', 'left');
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->where('item_foods.menutype', 'beverage'); // Beverage items only
		$this->db->group_by('order_menu.menu_id');
		$this->db->order_by('total_quantity', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();
		return $query->result();
	}

	/**
	 * Get top selling items (all types) - fallback if menutype is not set
	 */
	public function topSellingItems($limit = 10)
	{
		$this->db->select('item_foods.ProductName, item_foods.ProductsID, item_foods.menutype, item_category.Name as CategoryName,
			SUM(order_menu.menuqty) as total_quantity,
			SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->join('item_category', 'item_foods.CategoryID = item_category.CategoryID', 'left');
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->group_by('order_menu.menu_id');
		$this->db->order_by('total_quantity', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();
		return $query->result();
	}

	/**
	 * Get today's food sales revenue
	 */
	public function todayFoodRevenue()
	{
		$today = date('Y-m-d');
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('DATE(customer_order.order_date)', $today);
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->where('item_foods.menutype', 'food');
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get today's beverage sales revenue
	 */
	public function todayBeverageRevenue()
	{
		$today = date('Y-m-d');
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('DATE(customer_order.order_date)', $today);
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->where('item_foods.menutype', 'beverage');
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get today's paid food sales revenue (only Served/Paid orders - status 4 and 6)
	 * Status 4 = Served, Status 6 = Hotel Order
	 */
	public function todayPaidFoodRevenue()
	{
		$today = date('Y-m-d');
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('DATE(customer_order.order_date)', $today);
		$this->db->where_in('customer_order.order_status', [4, 6]); // Served and Hotel Order
		$this->db->where('item_foods.menutype', 'food');
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get today's paid beverage sales revenue (only Served/Paid orders - status 4 and 6)
	 * Status 4 = Served, Status 6 = Hotel Order
	 */
	public function todayPaidBeverageRevenue()
	{
		$today = date('Y-m-d');
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('DATE(customer_order.order_date)', $today);
		$this->db->where_in('customer_order.order_status', [4, 6]); // Served and Hotel Order
		$this->db->where('item_foods.menutype', 'beverage');
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get current month food sales revenue
	 */
	public function currentMonthFoodRevenue()
	{
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('MONTH(customer_order.order_date)', date('m'));
		$this->db->where('YEAR(customer_order.order_date)', date('Y'));
		$this->db->where_in('customer_order.order_status', [4, 6]); // Served and Hotel Order
		$this->db->where('item_foods.menutype', 'food');
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get current month beverage sales revenue (only paid/completed orders - status 4 and 6)
	 * Status 4 = Served, Status 6 = Hotel Order
	 */
	public function currentMonthBeverageRevenue()
	{
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('MONTH(customer_order.order_date)', date('m'));
		$this->db->where('YEAR(customer_order.order_date)', date('Y'));
		$this->db->where_in('customer_order.order_status', [4, 6]); // Served and Hotel Order
		$this->db->where('item_foods.menutype', 'beverage');
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get total food sales revenue (all time)
	 */
	public function totalFoodRevenue()
	{
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->where('item_foods.menutype', 'food');
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get total beverage sales revenue (all time)
	 */
	public function totalBeverageRevenue()
	{
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as total_revenue');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->where('item_foods.menutype', 'beverage');
		$query = $this->db->get();
		$result = $query->row();
		return $result ? $result->total_revenue : 0;
	}

	/**
	 * Get category-wise sales report
	 */
	public function categoryWiseSales($limit = 10)
	{
		$this->db->select('item_category.Name as CategoryName, item_category.CategoryID,
			SUM(order_menu.menuqty) as total_quantity,
			SUM(order_menu.price * order_menu.menuqty) as total_revenue,
			COUNT(DISTINCT order_menu.order_id) as total_orders');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->join('item_category', 'item_foods.CategoryID = item_category.CategoryID', 'left');
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled
		$this->db->group_by('item_category.CategoryID');
		$this->db->order_by('total_revenue', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();
		return $query->result();
	}

	/**
	 * Get food vs beverage comparison (today)
	 */
	public function foodBeverageComparisonToday()
	{
		$today = date('Y-m-d');

		// Food sales
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as food_revenue, SUM(order_menu.menuqty) as food_quantity');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('DATE(customer_order.order_date)', $today);
		$this->db->where('customer_order.order_status !=', 5);
		$this->db->where('item_foods.menutype', 'food');
		$foodQuery = $this->db->get();
		$foodResult = $foodQuery->row();

		// Beverage sales
		$this->db->select('SUM(order_menu.price * order_menu.menuqty) as beverage_revenue, SUM(order_menu.menuqty) as beverage_quantity');
		$this->db->from('order_menu');
		$this->db->join('customer_order', 'order_menu.order_id = customer_order.order_id', 'left');
		$this->db->join('item_foods', 'order_menu.menu_id = item_foods.ProductsID', 'left');
		$this->db->where('DATE(customer_order.order_date)', $today);
		$this->db->where('customer_order.order_status !=', 5);
		$this->db->where('item_foods.menutype', 'beverage');
		$beverageQuery = $this->db->get();
		$beverageResult = $beverageQuery->row();

		return [
			'food_revenue' => $foodResult ? $foodResult->food_revenue : 0,
			'food_quantity' => $foodResult ? $foodResult->food_quantity : 0,
			'beverage_revenue' => $beverageResult ? $beverageResult->beverage_revenue : 0,
			'beverage_quantity' => $beverageResult ? $beverageResult->beverage_quantity : 0,
			'total_revenue' => ($foodResult ? $foodResult->food_revenue : 0) + ($beverageResult ? $beverageResult->beverage_revenue : 0)
		];
	}

	// ========================================================================
	// PAYMENT CATEGORIES ANALYTICS
	// ========================================================================

	/**
	 * Get all payments categorized by type (Booking, F&B, Pools, Parking, Other)
	 * This method aggregates payments from all sources and categorizes them
	 *
	 * @return array Categorized payment amounts
	 */
	public function getCategorizedPayments()
	{
		$categories = [
			'booking' => 0,
			'f_b' => 0,
			'pools' => 0,
			'parking' => 0,
			'other' => 0
		];

		// 1. BOOKING PAYMENTS - from tbl_guestpayments where book_type = 0 (room bookings)
		$this->db->select('SUM(paymentamount) as total_amount');
		$this->db->from('tbl_guestpayments');
		$this->db->where('book_type', 0); // Room bookings
		$bookingQuery = $this->db->get();
		$bookingResult = $bookingQuery->row();
		$categories['booking'] = $bookingResult && $bookingResult->total_amount ? floatval($bookingResult->total_amount) : 0;

		// 2. F&B (FOOD & BEVERAGE) PAYMENTS - from bill table (linked to customer_order)
		// First try to get from bill table
		$this->db->select('SUM(bill.bill_amount) as total_amount');
		$this->db->from('bill');
		$this->db->join('customer_order', 'bill.order_id = customer_order.order_id', 'left');
		$this->db->where('bill.bill_status', 1); // Paid bills only
		$this->db->where('customer_order.order_status !=', 5); // Exclude cancelled orders
		$fbQuery = $this->db->get();
		$fbResult = $fbQuery->row();
		$fbFromBills = $fbResult && $fbResult->total_amount ? floatval($fbResult->total_amount) : 0;

		// Also get from customer_order where customerpaid > 0 (direct payments)
		$this->db->select('SUM(customerpaid) as total_amount');
		$this->db->from('customer_order');
		$this->db->where('order_status !=', 5); // Exclude cancelled
		$this->db->where('customerpaid >', 0); // Only paid orders
		$fbDirectQuery = $this->db->get();
		$fbDirectResult = $fbDirectQuery->row();
		$fbFromOrders = $fbDirectResult && $fbDirectResult->total_amount ? floatval($fbDirectResult->total_amount) : 0;

		// Use the maximum to avoid double counting, or sum if they're different sources
		$categories['f_b'] = max($fbFromBills, $fbFromOrders);

		// 3. POOL PAYMENTS - from tbl_pool_booking table
		// Check if pool module exists
		$pool_module = $this->db->where('directory', 'swimming_pool')->where('status', 1)->get('module')->num_rows();
		if ($pool_module > 0) {
			// Check if table exists
			$tableExists = $this->db->query("SHOW TABLES LIKE 'tbl_pool_booking'")->num_rows();
			if ($tableExists > 0) {
				$this->db->select('SUM(total_amount) as total_amount');
				$this->db->from('tbl_pool_booking');
				$this->db->where('status', 1); // Paid/Completed bookings
				$poolQuery = $this->db->get();
				$poolResult = $poolQuery->row();
				$categories['pools'] = $poolResult && $poolResult->total_amount ? floatval($poolResult->total_amount) : 0;
			}
		}

		// 4. PARKING PAYMENTS - from tbl_bookParking table
		// Check if parking module exists
		$parking_module = $this->db->where('directory', 'car_parking')->where('status', 1)->get('module')->num_rows();
		if ($parking_module > 0) {
			// Check if table exists
			$tableExists = $this->db->query("SHOW TABLES LIKE 'tbl_bookParking'")->num_rows();
			if ($tableExists > 0) {
				// Try status = 0 (paid) first, if no results, try all non-cancelled
				$this->db->select('SUM(total_price) as total_amount');
				$this->db->from('tbl_bookParking');
				$this->db->where('status', 0); // Completed/Paid parking (status 0 = paid based on checkout code)
				$parkingQuery = $this->db->get();
				$parkingResult = $parkingQuery->row();
				$parkingPaid = $parkingResult && $parkingResult->total_amount ? floatval($parkingResult->total_amount) : 0;

				// If no paid records found, get all (assuming they're all paid if status field works differently)
				if ($parkingPaid == 0) {
					$this->db->select('SUM(total_price) as total_amount');
					$this->db->from('tbl_bookParking');
					$parkingAllQuery = $this->db->get();
					$parkingAllResult = $parkingAllQuery->row();
					$parkingPaid = $parkingAllResult && $parkingAllResult->total_amount ? floatval($parkingAllResult->total_amount) : 0;
				}

				$categories['parking'] = $parkingPaid;
			}
		}

		// 5. OTHER PAYMENTS - from tbl_guestpayments where book_type != 0 (hall room bookings, etc.)
		$this->db->select('SUM(paymentamount) as total_amount');
		$this->db->from('tbl_guestpayments');
		$this->db->where('book_type !=', 0); // Non-room bookings (hall rooms, etc.)
		$otherQuery = $this->db->get();
		$otherResult = $otherQuery->row();
		$categories['other'] = $otherResult && $otherResult->total_amount ? floatval($otherResult->total_amount) : 0;

		// Calculate total for validation
		$total = array_sum($categories);

		return [
			'categories' => $categories,
			'total' => $total,
			'booking' => $categories['booking'],
			'f_b' => $categories['f_b'],
			'pools' => $categories['pools'],
			'parking' => $categories['parking'],
			'other' => $categories['other']
		];
	}

}
