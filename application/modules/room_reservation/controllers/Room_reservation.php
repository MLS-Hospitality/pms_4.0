<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Dompdf\Dompdf;
use Dompdf\Options;

class Room_reservation extends MX_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array(
			'roomreservation_model',
			'paystack_reference_model'
		));
	}

public function bookingdatatable()
{
 
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
    

    $params = $_REQUEST;

    $columns = [
        0 => 'booked_info.bookedid',
        1 => 'booking_number',
        2 => 'roomtype',
        3 => 'booked_info.room_no',
        4 => 'customerinfo.firstname',
        5 => 'customerinfo.cust_phone',
        6 => 'checkindate',
        7 => 'checkoutdate',
        8 => 'bookingstatus',
        9 => 'paid_amount',
    ];

    // Search / filter
    if (!empty($params['search']['value'])) {
        $search = $this->db->escape_like_str($params['search']['value']);
        $where = " WHERE 
            booked_info.booking_number LIKE '{$search}%'
            OR booked_info.full_guest_name LIKE '{$search}%'
            OR booked_info.room_no LIKE '{$search}%'
            OR customerinfo.firstname LIKE '{$search}%'
            OR customerinfo.cust_phone LIKE '{$search}%'
            OR booked_info.checkindate LIKE '{$search}%'
            OR booked_info.checkoutdate LIKE '{$search}%'";
    } else {
        $where = " WHERE booked_info.bookingstatus IN (0,1,2,3,4,5)";
    }

    $sql = "SELECT booked_info.*, customerinfo.firstname, customerinfo.cust_phone, booked_details.discountamount
            FROM booked_info
            LEFT JOIN customerinfo ON customerinfo.customerid = booked_info.cutomerid
            LEFT JOIN booked_details ON booked_details.bookedid = booked_info.bookedid";

    $totalRecords = $this->db->query($sql . $where)->num_rows();

    if ($params['length'] == -1) {
        $params['length'] = $totalRecords;
    }

    $orderCol = $params['order'][0]['column'] ?? 0;
    $orderDir = $params['order'][0]['dir'] ?? 'asc';

    $sql .= $where . " ORDER BY {$columns[$orderCol]} {$orderDir}
              LIMIT " . intval($params['start']) . "," . intval($params['length']);

    $queryRecords = $this->db->query($sql)->result();

    $data = [];
    $i = intval($params['start']) + 1;

    foreach ($queryRecords as $value) {
		

        // Safe defaults
        $value->roomid        = $value->roomid ?? '';
        $value->paid_amount  = $value->paid_amount ?? 0;
        $value->discountamount = $value->discountamount ?? 0;

        /** ---------------- Actions ---------------- */
        $update = $checkin = $cancel = $view = $print = $delete = $Payment = '';

        if ($this->permission->method('room_reservation', 'update')->access()) {
            $update  = '<a onclick="editresrvation('.$value->bookedid.')" class="btn btn-warning btn-sm"><i class="ti-pencil-alt"></i></a>';
            $checkin = '<a onclick="checkinresrvation('.$value->bookedid.')" class="btn btn-dark btn-sm"><i class="ti-check-box"></i></a>';
            $cancel  = '<a onclick="cancelreservation('.$value->bookedid.')" class="btn btn-danger btn-sm"><i class="ti-close"></i></a>';
			$delete = '<a href="'.base_url('room_reservation/delete/booking/'.$value->bookedid).'" class="btn btn-danger btn-sm" 
onclick="return confirm(\'Are you sure you want to delete this booking?\')"><i class="ti-trash"></i></a>';
			
        }

        if ($this->permission->method('room_reservation', 'read')->access()) {
            $view  = '<a href="'.base_url("room_reservation/booking-information/{$value->bookedid}").'" class="btn btn-info btn-sm"><i class="ti-eye"></i></a>';
            $print = '<a onclick="printresrvation('.$value->bookedid.')" class="btn btn-primary btn-sm"><i class="fa fa-print"></i></a>';
        }

        if ($this->permission->method('room_reservation', 'create')->access()) {
            $Payment = '<a href="'.base_url("room_reservation/payment-information/{$value->bookedid}").'" class="btn btn-success btn-sm"><i class="ti-wallet"></i></a>';
        }

        /** ---------------- Room names ---------------- */
        $allroomname = '';
        foreach (explode(',', $value->roomid) as $rid) {
            $room = $this->db->select('roomtype')->from('roomdetails')->where('roomid', $rid)->get()->row();
            if (!empty($room->roomtype)) {
                $allroomname .= $room->roomtype . ', ';
            }
        }
        $allroomname = rtrim($allroomname, ', ');

        /** ---------------- TOTAL & PAYMENT (CORRECT LOGIC) ---------------- */
        $fullTotal = $this->roomreservation_model->calculateFullBookingTotal($value);

        $paymentsPaid = 0;
        $bookedidStr = (string)$value->bookedid;
        $bookedidInt = (int)$value->bookedid;

        $q1 = $this->db->query(
            "SELECT COALESCE(SUM(CAST(paymentamount AS DECIMAL(10,2))),0) total
             FROM tbl_guestpayments WHERE book_type = 0 AND bookedid = ?",
            [$bookedidStr]
        );
        if ($q1 && $q1->num_rows()) {
            $paymentsPaid = (float)$q1->row()->total;
        }

        $q2 = $this->db->query(
            "SELECT COALESCE(SUM(CAST(paymentamount AS DECIMAL(10,2))),0) total
             FROM tbl_guestpayments WHERE book_type = 0 AND CAST(bookedid AS UNSIGNED) = ?",
            [$bookedidInt]
        );
        if ($q2 && $q2->num_rows()) {
            $paymentsPaid = max($paymentsPaid, (float)$q2->row()->total);
        }

        $advanceAmount = (float)($value->advance_amount ?? 0);
        $paidAmount    = (float)($value->paid_amount ?? 0);

        $actualPaid = max($paymentsPaid, $advanceAmount, $paidAmount);
        $due = max($fullTotal - $actualPaid, 0);

        /** ---------------- Status ---------------- */
        $statusArr = ["Pending","Cancel","Success","Completed","Check In","Checkout"];
        $status = $statusArr[$value->bookingstatus] ?? 'Unknown';

        $paymentStatus = $this->roomreservation_model->calculatePaymentStatus($value->bookedid);
        if ($paymentStatus === false) {
            $paymentStatus = ($actualPaid < $fullTotal) ? "Pending" : "Success";
        }

        /** ---------------- Row ---------------- */
        $data[] = [
            $i++,
            $value->booking_number,
            $allroomname,
            $value->room_no,
            $value->firstname,
            $value->cust_phone,
			$value->date_time,
            $value->checkindate,
            $value->checkoutdate,
            number_format($actualPaid, 2),
            number_format($due, 2),
            $status,
            $paymentStatus,
            $update.$checkin.$view.$print.$cancel.$delete.$Payment
        ];
    }

$json_data = [
    "draw" => intval($params['draw']),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecords,
    "data" => $data
];

$this->output
    ->set_content_type('application/json')
    ->set_output(json_encode($json_data));


}


	public function bookingdatatablepend()
{
    // Start output buffering to prevent "headers already sent"

error_reporting(E_ERROR | E_PARSE);  // hides warnings & notices
    ini_set('display_errors', 0);

    $params = $_REQUEST;
    $columns = array(
        0 => 'booked_info.bookedid',
        1 => 'booking_number',
        2 => 'roomtype',
        3 => 'booked_info.room_no',
        4 => 'customerinfo.firstname',
        5 => 'customerinfo.cust_phone',
		6=>"activity",
        7 => 'checkindate',
        8 => 'checkoutdate',
        9 => 'bookingstatus',
        10 => 'paid_amount',
    );

    $where = '';
    if (!empty($params['search']['value'])) {
        $search = $this->db->escape_like_str($params['search']['value']);
        $where .= " WHERE 
            booked_info.booking_number LIKE '{$search}%' 
            OR booked_info.full_guest_name LIKE '{$search}%' 
            OR booked_info.room_no LIKE '{$search}%' 
            OR customerinfo.firstname LIKE '{$search}%' 
            OR customerinfo.cust_phone LIKE '{$search}%' 
            OR booked_info.checkindate LIKE '{$search}%' 
            OR booked_info.checkoutdate LIKE '{$search}%' 
            OR booked_info.bookingstatus = 0";
    } else {
       $where = " WHERE booked_info.bookingstatus = 0";

    $sql = "SELECT booked_info.*, customerinfo.firstname, customerinfo.cust_phone, booked_details.discountamount 
            FROM booked_info 
            LEFT JOIN customerinfo ON customerinfo.customerid = booked_info.cutomerid 
            LEFT JOIN booked_details ON booked_details.bookedid = booked_info.bookedid";

    $sqlTot = $sql . $where;
    $sqlRec = $sql . $where;

    $totalRecords = $this->db->query($sqlTot)->num_rows();

    if ($params['length'] == '-1') $params['length'] = intval($totalRecords);

    $orderCol = isset($params['order'][0]['column']) ? $params['order'][0]['column'] : 0;
    $orderDir = isset($params['order'][0]['dir']) ? $params['order'][0]['dir'] : 'asc';
    $sqlRec .= " ORDER BY " . $columns[$orderCol] . " " . $orderDir . " LIMIT " . intval($params['start']) . "," . intval($params['length']);

    $queryRecords = $this->db->query($sqlRec)->result();
    $data = array();
    $i = intval($params['start']) + 1;

    foreach ($queryRecords as $value) {
        $row = [];

        // Default safe values to avoid notices
        $value->roomid = $value->roomid ?? '';
        $value->total_price = $value->total_price ?? 0;
        $value->paid_amount = $value->paid_amount ?? 0;
        $value->discountamount = $value->discountamount ?? 0;

        // Action buttons
        $update = $checkin = $cancel = $view = $print = $Payment = '';
        if ($this->permission->method('room_reservation', 'update')->access()) {
            $update = '<a onclick="editresrvation(' . $value->bookedid . ')" class="btn btn-warning btn-sm"><i class="ti-pencil-alt"></i></a>';
            $checkin = '<a onclick="checkinresrvation(' . $value->bookedid . ')" class="btn btn-dark btn-sm"><i class="ti-check-box"></i></a>';
            $cancel = '<a onclick="cancelreservation(' . $value->bookedid . ')" class="btn btn-danger btn-sm"><i class="ti-close"></i></a>';
        }
        if ($this->permission->method('room_reservation', 'read')->access()) {
            $view = '<a href="' . base_url("room_reservation/booking-information/{$value->bookedid}") . '" class="btn btn-info btn-sm"><i class="ti-eye"></i></a>';
            $print = '<a onclick="printresrvation(' . $value->bookedid . ')" class="btn btn-primary btn-sm"><i class="fa fa-print"></i></a>';
        }
        if ($this->permission->method('room_reservation', 'create')->access()) {
            $Payment = '<a href="' . base_url("room_reservation/payment-information/{$value->bookedid}") . '" class="btn btn-success btn-sm"><i class="ti-wallet"></i></a>';
        }

        // Room names
        $allroomname = '';
        $rnameArr = explode(',', $value->roomid);
        foreach ($rnameArr as $rid) {
            $room = $this->db->select('roomtype')->from('roomdetails')->where('roomid', $rid)->get()->row();
            $allroomname .= isset($room->roomtype) ? $room->roomtype . ', ' : '';
        }

        $allroomname = rtrim($allroomname, ', ');

     // --- Use the SAME logic as pendingAmount() ---
$fullTotal = $this->roomreservation_model->calculateFullBookingTotal($value);

// Get actual paid amount (same logic)
$paymentsPaid = 0;
$bookedidStr = (string)$value->bookedid;
$bookedidInt = (int)$value->bookedid;

// Payments table (string)
$q1 = $this->db->query(
    "SELECT COALESCE(SUM(CAST(paymentamount AS DECIMAL(10,2))),0) actual_paid
     FROM tbl_guestpayments WHERE book_type = 0 AND bookedid = ?",
    [$bookedidStr]
);
if ($q1 && $q1->num_rows()) {
    $paymentsPaid = max($paymentsPaid, (float)$q1->row()->actual_paid);
}

// Payments table (int fallback)
$q2 = $this->db->query(
    "SELECT COALESCE(SUM(CAST(paymentamount AS DECIMAL(10,2))),0) actual_paid
     FROM tbl_guestpayments WHERE book_type = 0 AND CAST(bookedid AS UNSIGNED) = ?",
    [$bookedidInt]
);
if ($q2 && $q2->num_rows()) {
    $paymentsPaid = max($paymentsPaid, (float)$q2->row()->actual_paid);
}

// Fallbacks
$advanceAmount = (float)($value->advance_amount ?? 0);
$paidAmount    = (float)($value->paid_amount ?? 0);

// Final actual paid
$actualPaid = max($paymentsPaid, $advanceAmount, $paidAmount);

// Pending / Due
$due = $fullTotal - $actualPaid;
$due = $due > 0 ? $due : 0;


        $statusArr = ["Pending","Cancel","Success","Completed","Check In","Checkout"];
        $status = $statusArr[$value->bookingstatus] ?? 'Unknown';
        $paymentStatus = $this->roomreservation_model->calculatePaymentStatus($value->bookedid);
        if ($paymentStatus === false) $paymentStatus = ($value->paid_amount < $totalPrice) ? "Pending" : "Success";

        // Build row
        $row[] = $i++;
        $row[] = $value->booking_number;
        $row[] = $allroomname;
        $row[] = $value->room_no;
        $row[] = $value->firstname;
        $row[] = $value->cust_phone;
        $row[] = $value->checkindate;
        $row[] = $value->checkoutdate;
		$row[] = $value->date_time;
       $row[] = number_format($actualPaid, 2);
        $row[] = number_format($due, 2);
        $row[] = $status;
        $row[] = $paymentStatus;
        $row[] = $update . $checkin . $view . $print . $cancel . $Payment;

        $data[] = $row;
    }

    $json_data = array(
        "draw" => intval($params['draw']),
        "recordsTotal" => intval($totalRecords),
        "recordsFiltered" => intval($totalRecords),
        "data" => $data
    );

    // Output JSON safely
    $this->output->set_content_type('application/json')->set_output(json_encode($json_data));
    
}
    
}


	public function checkindatatable()
	{
		$params = $columns = $totalRecords = $data = array();
		$params = $_REQUEST;
		$columns = array(
			0 => 'booked_info.bookedid',
			1 => 'booking_number',
			2 => 'roomtype',
			3 => 'booked_info.room_no',
			4 => 'customerinfo.firstname',
			5 => 'customerinfo.cust_phone',
			6 => 'checkindate',
			7 => 'checkoutdate',
			8 => 'bookingstatus',
			9 => 'paid_amount',
		);

		$where = $sqlTot = $sqlRec = "";
		// check search value exist
		if (!empty($params['search']['value'])) {
			$where .= " WHERE ";
			$where .= " ( booked_info.booking_number LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR roomdetails.roomtype LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR booked_info.room_no LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR customerinfo.firstname LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR customerinfo.cust_phone LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR booked_info.checkindate LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR booked_info.checkoutdate LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR booked_info.bookingstatus LIKE '" . $params['search']['value'] . "%' )";
		}
		// getting total number records without any search
		// CRITICAL: Join booked_details to get discountamount for correct calculation
		$sql = "SELECT booked_info.*,customerinfo.firstname,customerinfo.cust_phone,booked_details.discountamount FROM booked_info Left join customerinfo ON customerinfo.customerid=booked_info.cutomerid Left join booked_details ON booked_details.bookedid=booked_info.bookedid where booked_info.bookingstatus=4";


		$sqlTot .= $sql;
		$sqlRec .= $sql;
		//concatenate search sql if value exist
		if (isset($where) && ($where != '')) {
			$sqlTot .= $where;
			$sqlRec .= $where;
		}
		$SQLtotal = $this->db->query($sqlTot);
		$totalRecords = $SQLtotal->num_rows();
		if ($params['length'] == '-1') {
			$params['length'] = intval($totalRecords);
		}
		$sqlRec .=  " ORDER BY " . $columns[$params['order'][0]['column']] . "   " . $params['order'][0]['dir'] . " LIMIT " . $params['start'] . " ," . $params['length'] . " ";

		$SQLoffer = $this->db->query($sqlRec);
		$queryRecords = $SQLoffer->result();
		$i = 0;
		foreach ($queryRecords as  $value) {
			$i++;
			$row = array();
			$update = '';
			$delete = '';
			if ($this->permission->method('room_reservation', 'update')->access()):
				$update = '<input name="url" type="hidden" id="url_' . $value->bookedid . '"/><a onclick="editresrvation(' . $value->bookedid . ')" class="btn btn-warning btn-sm margin_right_5px" data-toggle="tooltip" data-placement="top" data-original-title="Update" title="Update Reservation"><i class="ti-pencil-alt text-white" aria-hidden="true"></i></a>';
			endif;
			if ($this->permission->method('room_reservation', 'update')->access()):
				$checkin = '<input name="url" type="hidden" id="url_' . $value->bookedid . '"/><a onclick="checkoutresrvation(' . $value->bookedid . ')" class="btn btn-dark btn-sm margin_right_5px" data-toggle="tooltip" data-placement="top" data-original-title="Checkout" title="Check-Out"><i class="ti-thumb-up text-white" aria-hidden="true"></i></a>';
			endif;
			if ($this->permission->method('room_reservation', 'read')->access()):
				$view = '<a href="' . base_url() . 'room_reservation/booking-information/' . $value->bookedid . '" class="btn btn-info btn-sm margin_right_5px" data-toggle="tooltip" data-placement="top" data-original-title="View" title="View"><i class="ti-eye"></i></a>';
			endif;
			if ($this->permission->method('room_reservation', 'read')->access()):
				$print = '<input name="url" type="hidden" id="url_' . $value->bookedid . '"/><a onclick="printresrvation(' . $value->bookedid . ')" class="btn btn-primary btn-sm margin_right_5px" data-toggle="tooltip" data-placement="top" data-original-title="Print" title="Print"><i class="fa fa-print text-white" aria-hidden="true"></i></a>';
			endif;
			if ($this->permission->method('room_reservation', 'create')->access()):
				$Payment = '<a href="' . base_url() . 'room_reservation/payment-information/' . $value->bookedid . '" class="btn btn-success btn-sm margin_right_5px" data-toggle="tooltip" data-placement="top" data-original-title="Payment" title="Payment"><i class="ti-wallet"></i></a>';
			endif;
			if ($this->permission->method('room_reservation', 'update')->access()):
				$cancel = '<input name="url" type="hidden" id="url_' . $value->bookedid . '"/><a onclick="cancelreservation(' . $value->bookedid . ')" class="btn btn-danger btn-sm margin_right_5px" data-toggle="tooltip" data-placement="top" data-original-title="Cancel" title="Cancel Reservation"><i class="ti-close text-white" aria-hidden="true"></i></a>';
			endif;
			$datediff = strtotime($value->checkoutdate) - strtotime($value->checkindate);
			$datediff = ceil($datediff / (60 * 60 * 24));
			$totalPrice = $value->total_price * $datediff;
			if ($value->bookingstatus == 0) {
				$status = "Pending";
			} else if ($value->bookingstatus == 1) {
				$status = "Cancel";
			} else if ($value->bookingstatus == 2) {
				$status = "Success";
			} else if ($value->bookingstatus == 3) {
				$status = "Completed";
			} else if ($value->bookingstatus == 4) {
				$status = "Check In";
			} else if ($value->bookingstatus == 5) {
				$status = "Checkout";
			}
			// Calculate payment status accounting for all charges (taxes, service charges, parking)
			$paymentStatus = $this->roomreservation_model->calculatePaymentStatus($value->bookedid);
			if ($paymentStatus === false) {
				// Fallback to simple calculation if method fails
				$paymentStatus = ($value->paid_amount < $totalPrice) ? "Pending" : "Success";
			}
			$allroomname = "";
			$row[] = $i;
			$row[] = $value->booking_number;
			$rname = explode(",", $value->roomid);
			for ($l = 0; $l < count($rname); $l++) {
				$roomname = $this->db->select("roomtype")->from("roomdetails")->where("roomid", $rname[$l])->get()->row();
				$allroomname .= $roomname->roomtype . ", ";
			}
			$row[] = trim($allroomname, ", ");
			$row[] = $value->room_no;
			$row[] = $value->firstname;
			$row[] = $value->cust_phone;
			$row[] = $value->date_time;
			$row[] = $value->checkindate;
			$row[] = $value->checkoutdate;
			$row[] = $value->paid_amount;
			// Get taxes using centralized method
			$totalTax = $this->roomreservation_model->calculateTax($totalPrice, false);
			$scharge = $this->db->select("servicecharge")->from("setting")->get()->row();
			$car_parking = $this->db->where('directory', 'car_parking')->where('status', 1)->get('module')->num_rows();
			if ($car_parking == 1) {
				$car_parking = $this->db->select("total_price")->from("tbl_bookParking")->where("bookedid", $value->bookedid)->get()->result();
			}
			$totalScharge = 0;
			$totalParking = 0;
			if ($scharge->servicecharge) {
				$totalScharge = ($totalPrice * $scharge->servicecharge) / 100;
			}
			if (!empty($car_parking)) {
				foreach ($car_parking as $cp) {
					$totalParking += $cp->total_price;
				}
			}
			// Get discount amount from booking
			$discountAmount = !empty($value->discountamount) ? $value->discountamount : 0;
			// Calculate subtotal (rent + tax + service charge + parking)
			$subtotal = $totalPrice + $totalTax + $totalScharge + $totalParking;
			// Apply discount
			$totalAfterDiscount = $subtotal - $discountAmount;
			// Calculate due amount (total after discount - paid amount)
			$due = $totalAfterDiscount - $value->paid_amount;
			$row[] = $due < 0 ? 0 : number_format($due, 2);
			$row[] = $status;
			$row[] = $paymentStatus;
			$row[] = $update . $checkin . $view . $print . $cancel;
			$data[] = $row;
		}

		$json_data = array(
			"draw"            => intval($params['draw']),
			"recordsTotal"    => intval($totalRecords),
			"recordsFiltered" => intval($totalRecords),
			"data"            => $data   // total data array
		);

		echo json_encode($json_data);
	}
	public function pending($id=null){
	    $this->permission->method('room_reservation', 'read')->redirect();
		$sc = array('isSeen'         =>  1);
		$this->db->update('booked_info', $sc);

		$data['title']    = display('room_reservation');
		$data["bookingtype"] = $this->roomreservation_model->get_all('*', 'bookingtype', 'booktypeid');
		$data["roomdetails"] = $this->roomreservation_model->get_all('roomid,roomtype', 'roomdetails', 'roomid');
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		#pagination ends
		#
		$data['module'] = "room_reservation";
		$data['page']   = "reservationpending";
		 $output = Modules::run('template/layout', $data);
         $this->output->set_output($output);
	}
	public function index($id = null)
	{
		
		$this->permission->method('room_reservation', 'read')->redirect();
		$sc = array('isSeen'         =>  1);
		$this->db->update('booked_info', $sc);

		$data['title']    = display('room_reservation');
		$data["bookingtype"] = $this->roomreservation_model->get_all('*', 'bookingtype', 'booktypeid');
		$data["roomdetails"] = $this->roomreservation_model->get_all('roomid,roomtype', 'roomdetails', 'roomid');
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		

		#pagination ends
		#
	
		$data['module'] = "room_reservation";
		$data['page']   = "reservationlist";
		 $output = Modules::run('template/layout', $data);
         $this->output->set_output($output);
		
		//echo Modules::run('template/layout', $data);
	}

	private function nations(){
		$json_path = APPPATH . 'data/countries.json';
		 $nations ="";
		
	//load countries
	if (file_exists($json_path)) {
            // Read the file
            $json_string = file_get_contents($json_path);
            
            // Decode to associative array
            $nations = json_decode($json_string, true);

            // Pass to view
            
	}
	return $nations;
	}
	
	public function existcustomer()
	{
		$mobile = $this->input->post("existmobile", TRUE);
		$search = $this->input->post("search", TRUE);
		$type = $this->input->post("type", TRUE);
		if ($type != 1) {
			$user = $this->db->select("customerid, concat_ws(' ', firstname, lastname) as firstname")->from("customerinfo")->where("cust_phone", $mobile)->get()->row();
			if (empty($user)) {
				$data = array(
					'user' => "No User Found",
					'existuser' => "0"
				);
			} else {
				$data = array(
					'user' => $user->firstname,
					'userid' => $user->customerid,
					'existuser' => "1"
				);
			}
			echo json_encode($data);
		} else {
			// Search by either mobile number or name
			$this->db->select('customerid,firstname,cust_phone');
			$this->db->from('customerinfo');
			$this->db->group_start();
			$this->db->like('cust_phone', $search);
			$this->db->or_like('firstname', $search);
			$this->db->or_like('lastname', $search);
			$this->db->group_end();
			$query = $this->db->get();
			$user = $query->result_array();

			if ($user) {
				$data = array(
					'user' => $user,
				);
				echo json_encode($data);
			} else {
				$data = array(
					"user" => "Not found"
				);
				echo json_encode($data);
			}
		}
	}
	public function mobilenocheck()
	{
		$mobile = $this->input->post("mobileno", TRUE);
		$user = $this->db->select("COUNT(customerid) as customer")->from("customerinfo")->where("cust_phone", $mobile)->get()->row();
		if ($user->customer < 1) {
			$data = array(
				'user' => "Number not used before",
				'existuser' => "0"
			);
		} else {
			$data = array(
				'user' => "Number already used",
				'existuser' => "1"
			);
		}
		echo json_encode($data);
	}
	public function booking($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();
		$data['title']    = display('room_reservation');
		$data["bookingtype"] = $this->roomreservation_model->get_all('*', 'bookingtype', 'booktypeid');
		$data["roomdetails"] = $this->roomreservation_model->get_all('roomid,roomtype', 'roomdetails', 'roomid');
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["banklist"] = $this->db->query("SELECT HeadCode,HeadName FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%'")->result();
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		$data["inouttime"] = $this->db->select("checkintime,checkouttime")->from("setting")->where("id", 2)->get()->row();
		$data['currency']    = getCurrency();
		$data["taxsetting"] = $this->roomreservation_model->getActiveTaxRates();
		$data["setting"] = $this->db->select("servicecharge")->from("setting")->get()->row();
		$data['nations'] = $this->nations();
		#
		#pagination ends
		#
		$data['module'] = "room_reservation";
		$data['page']   = "addereservation";
		$this->load->view("room_reservation/addreservation", $data);
	}
	public function bookingedit($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();
		$data['title']    = display('room_reservation');
		$data["bookingdata"] = $this->roomreservation_model->editbooking($id);
		$data["guestdata"] = $this->db->select("customerid,guestname,mobile")->from("tbl_otherguest")->where("tbl_otherguest.bookedid", $id)->get()->result();
		$data["custdata"] = $this->db->select("cutomerid as customerid,firstname,cust_phone")->from("booked_info")->join("customerinfo", "customerinfo.customerid=booked_info.cutomerid", "left")->where("booked_info.bookedid", $id)->get()->result();
		$data["bookingtype"] = $this->roomreservation_model->get_all('*', 'bookingtype', 'booktypeid');
		$data["bookingsource"] = $this->roomreservation_model->get_all('*', 'tbl_booking_type_info', 'btypeinfoid');
		$data["roomdetails"] = $this->roomreservation_model->get_all('roomid,roomtype', 'roomdetails', 'roomid');
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["banklist"] = $this->db->query("SELECT HeadCode,HeadName FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%'")->result();
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		$data["inouttime"] = $this->db->select("checkintime,checkouttime")->from("setting")->where("id", 2)->get()->row();
		$data['currency']    = getCurrency();
		$data["taxsetting"] = $this->roomreservation_model->getActiveTaxRates();
		$data["setting"] = $this->db->select("servicecharge")->from("setting")->get()->row();

		#
		#pagination ends
		#
		$data['module'] = "room_reservation";
		$data['page']   = "editreservation";
		$this->load->view("room_reservation/editreservation", $data);
	}
	public function checkin($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();

		$data['title']    = display('checkin');
		#
		#pagination ends
		#
		$data['module'] = "room_reservation";
		$data['page']   = "checkin";
		echo Modules::run('template/layout', $data);
	}
	public function checkout($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();

		$data['title']    = display('checkout');
		$data["checkinrooms"] = $this->db->select('b.bookedid,b.room_no,c.firstname')->from("booked_info b")->join("customerinfo c", "c.customerid=b.cutomerid", "left")->where("b.bookingstatus", 4)->get()->result();
		$data['module'] = "room_reservation";
		$data['page']   = "checkout";
		echo Modules::run('template/layout', $data);
	}
	public function directcheckin($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();
		$data["bookingtype"] = $this->roomreservation_model->get_all('*', 'bookingtype', 'booktypeid');
		$data["roomdetails"] = $this->roomreservation_model->get_all('roomid,roomtype', 'roomdetails', 'roomid');
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["banklist"] = $this->db->query("SELECT HeadCode,HeadName FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%'")->result();
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		$data['title']    = display('checkin');
		$data["inouttime"] = $this->db->select("checkintime,checkouttime")->from("setting")->where("id", 2)->get()->row();
		$data['currency']    = getCurrency();
		$data["taxsetting"] = $this->roomreservation_model->getActiveTaxRates();
		$data["setting"] = $this->db->select("servicecharge")->from("setting")->get()->row();
		$data['nations'] = $this->nations();
		#
		#pagination ends
		#
		$data['module'] = "room_reservation";
		$data['page']   = "addcheckin";
		$this->load->view("room_reservation/addcheckin", $data);
	}
	public function bookingcheckin($id = null)
	{
	    
		$this->permission->method('room_reservation', 'read')->redirect();
		$data['title']    = display('room_reservation');
		$data["bookingdata"] = $this->roomreservation_model->editbooking($id);
	
		$data["guestdata"] = $this->db->select("customerid,guestname,mobile")->from("tbl_otherguest")->where("tbl_otherguest.bookedid", $id)->get()->result();
		$data["custdata"] = $this->db->select("cutomerid as customerid,firstname,cust_phone")->from("booked_info")->join("customerinfo", "customerinfo.customerid=booked_info.cutomerid", "left")->where("booked_info.bookedid", $id)->get()->result();
		$data["bookingtype"] = $this->roomreservation_model->get_all('*', 'bookingtype', 'booktypeid');
		$data["bookingsource"] = $this->roomreservation_model->get_all('*', 'tbl_booking_type_info', 'btypeinfoid');
		$data["roomdetails"] = $this->roomreservation_model->get_all('roomid,roomtype', 'roomdetails', 'roomid');
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["banklist"] = $this->db->query("SELECT HeadCode,HeadName FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%'")->result();
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		$data["inouttime"] = $this->db->select("checkintime,checkouttime")->from("setting")->where("id", 2)->get()->row();
		$data['currency']    = getCurrency();
		$data["taxsetting"] = $this->roomreservation_model->getActiveTaxRates();
		$data["setting"] = $this->db->select("servicecharge")->from("setting")->get()->row();

		#
		#pagination ends
		#
		$data['module'] = "room_reservation";
		$data['page']   = "checkinreservation";
		$this->load->view("room_reservation/checkinreservation", $data);
	}
	public function bookingcheckout($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();
		$data['title']    = display('room_reservation');
		$bid = explode(",", $id);
		for ($i = 0; $i < count($bid); $i++) {
			$bdetails[$i] = $this->roomreservation_model->detailbooking($bid[$i]);
			if ($this->db->table_exists('tbl_pool_booking')) {
				$allpoolbill[$i] = $this->db->select('p.*,c.firstname')->from("tbl_pool_booking p")->join("customerinfo c", "c.customerid=p.custid", "left")->where("p.entrydate>=", $bdetails[$i]->checkindate)->where("p.entrydate<=", $bdetails[$i]->checkoutdate)->where("custid", $bdetails[$i]->cutomerid)->where("p.status!=", 3)->get()->result();
			} else {
				$allpoolbill = "";
			}
			if ($this->db->table_exists('customer_order')) {
				$allrestaurant[$i] = $this->db->select('b.bill_amount,co.order_id,c.firstname')->from("customer_order co")->join("customerinfo c", "c.customerid=co.customer_id", "left")->join("bill b", "b.order_id=co.order_id", "left")->where("CONCAT_WS(' ',co.order_date,co.order_time)>=", date('Y-m-d H:i:s', strtotime($bdetails[$i]->checkindate)))->where("CONCAT_WS(' ',co.order_date,co.order_time)<=", date('Y-m-d H:i:s', strtotime($bdetails[$i]->checkoutdate)))->where("co.customer_id", $bdetails[$i]->cutomerid)->where("co.order_status=", 6)->where("b.bill_status=", 0)->get()->result();
			} else {
				$allrestaurant = "";
			}
			if ($this->db->table_exists('tbl_hallroom_booking')) {
				$allhallroom[$i] = $this->db->select('hb.totalamount,hb.hbid,c.firstname')->from("tbl_hallroom_booking hb")->join("customerinfo c", "c.customerid=hb.customerid", "left")->where("hb.booked_id", $bdetails[$i]->bookedid)->where("hb.customerid", $bdetails[$i]->cutomerid)->where("hb.status", 1)->where("hb.payment_status", 0)->get()->result();
			} else {
				$allhallroom = "";
			}
			if ($this->db->table_exists('tbl_bookParking')) {
				$allcarParking[$i] = $this->db->select('bp.total_price,bp.bookParking_id,c.firstname')->from("tbl_bookParking bp")->join("booked_info bi", "bi.bookedid=bp.bookedid", "left")->join("customerinfo c", "c.customerid=bi.cutomerid", "left")->where("bp.bookedid", $bdetails[$i]->bookedid)->where("c.customerid", $bdetails[$i]->cutomerid)->where("bp.status", 1)->where("bp.paymentStatus", 0)->get()->result();
			} else {
				$allcarParking = "";
			}
		}
		$data["poolbill"] = $allpoolbill;
		$data["restaurantbill"] = $allrestaurant;
		$data["hallroombill"] = $allhallroom;
		$data["carParkingBill"] = $allcarParking;
		$data["bookingdata"] = $bdetails;
		$data["setting"] = $this->db->select("title,address,email,phone")->from("setting")->where("id", 2)->get()->row();
		$data["taxsetting"] = $this->roomreservation_model->getActiveTaxRates();
		$data["setting"] = $this->db->select("title,address,email,phone,servicecharge")->from("setting")->get()->row();
		$data["invoicelogo"] = $this->db->select("invoice_logo")->from("common_setting")->where("id", 1)->get()->row();
		$data["checkinrooms"] = $this->db->select('b.bookedid,b.room_no,c.firstname')->from("booked_info b")->join("customerinfo c", "c.customerid=b.cutomerid", "left")->where("b.bookingstatus", 4)->get()->result();
		$data["bookingtype"] = $this->roomreservation_model->get_all('*', 'bookingtype', 'booktypeid');
		$data["bookingsource"] = $this->roomreservation_model->get_all('*', 'tbl_booking_type_info', 'btypeinfoid');
		$data["roomdetails"] = $this->roomreservation_model->get_all('roomid,roomtype', 'roomdetails', 'roomid');
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["banklist"] = $this->db->query("SELECT HeadCode,HeadName FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%'")->result();
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		$data["inouttime"] = $this->db->select("checkintime,checkouttime")->from("setting")->where("id", 2)->get()->row();
		$data['currency']    = getCurrency();

		#
		#pagination ends
		#
		$data['module'] = "room_reservation";
		$data['page']   = "checkoutreservation";
		$this->load->view("room_reservation/checkoutreservation", $data);
	}
	public function checkoutall($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();
		$data['title']    = display('room_reservation');
		$bid = explode(",", $id);
		for ($i = 0; $i < count($bid); $i++) {
			$bdetails[$i] = $this->roomreservation_model->detailbooking($bid[$i]);
			if ($this->db->table_exists('tbl_pool_booking')) {
				$allpoolbill[$i] = $this->db->select('p.*,c.firstname')->from("tbl_pool_booking p")->join("customerinfo c", "c.customerid=p.custid", "left")->where("p.entrydate>=", $bdetails[$i]->checkindate)->where("p.entrydate<=", $bdetails[$i]->checkoutdate)->where("custid", $bdetails[$i]->cutomerid)->where("p.status!=", 3)->get()->result();
			} else {
				$allpoolbill = "";
			}
			if ($this->db->table_exists('customer_order')) {
				$allrestaurant[$i] = $this->db->select('b.bill_amount,co.order_id,c.firstname')->from("customer_order co")->join("customerinfo c", "c.customerid=co.customer_id", "left")->join("bill b", "b.order_id=co.order_id", "left")->where("CONCAT_WS(' ',co.order_date,co.order_time)>=", date('Y-m-d H:i:s', strtotime($bdetails[$i]->checkindate)))->where("CONCAT_WS(' ',co.order_date,co.order_time)<=", date('Y-m-d H:i:s', strtotime($bdetails[$i]->checkoutdate)))->where("co.customer_id", $bdetails[$i]->cutomerid)->where("co.order_status=", 6)->where("b.bill_status=", 0)->get()->result();
			} else {
				$allrestaurant = "";
			}
			if ($this->db->table_exists('tbl_hallroom_booking')) {
				$allhallroom[$i] = $this->db->select('hb.totalamount,hb.hbid,c.firstname')->from("tbl_hallroom_booking hb")->join("customerinfo c", "c.customerid=hb.customerid", "left")->where("hb.booked_id", $bdetails[$i]->bookedid)->where("hb.customerid", $bdetails[$i]->cutomerid)->where("hb.status", 1)->where("hb.payment_status", 0)->get()->result();
			} else {
				$allhallroom = "";
			}
			if ($this->db->table_exists('tbl_bookParking')) {
				$allcarParking[$i] = $this->db->select('bp.total_price,bp.bookParking_id,c.firstname')->from("tbl_bookParking bp")->join("booked_info bi", "bi.bookedid=bp.bookedid", "left")->join("customerinfo c", "c.customerid=bi.cutomerid", "left")->where("bp.bookedid", $bdetails[$i]->bookedid)->where("c.customerid", $bdetails[$i]->cutomerid)->where("bp.status", 1)->where("bp.paymentStatus", 0)->get()->result();
			} else {
				$allcarParking = "";
			}
		}
		$data["poolbill"] = $allpoolbill;
		$data["restaurantbill"] = $allrestaurant;
		$data["hallroombill"] = $allhallroom;
		$data["carParkingBill"] = $allcarParking;
		$data["bookingdata"] = $bdetails;
		$data["setting"] = $this->db->select("title,address,email,phone,servicecharge")->from("setting")->get()->row();
		$data["taxsetting"] = $this->roomreservation_model->getActiveTaxRates();
		$data["invoicelogo"] = $this->db->select("invoice_logo")->from("common_setting")->where("id", 1)->get()->row();
		$data["checkinrooms"] = $this->roomreservation_model->get_all('room_no,cutomerid', 'booked_info', 'bookedid');
		$data["bookingtype"] = $this->roomreservation_model->get_all('*', 'bookingtype', 'booktypeid');
		$data["bookingsource"] = $this->roomreservation_model->get_all('*', 'tbl_booking_type_info', 'btypeinfoid');
		$data["roomdetails"] = $this->roomreservation_model->get_all('roomid,roomtype', 'roomdetails', 'roomid');
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["banklist"] = $this->db->query("SELECT HeadCode,HeadName FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%'")->result();
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		$data["inouttime"] = $this->db->select("checkintime,checkouttime")->from("setting")->where("id", 2)->get()->row();
		$data['currency']    = getCurrency();

		#
		#pagination ends
		#
		$data['module'] = "room_reservation";
		$data['page']   = "checkoutall";
		$this->load->view("room_reservation/checkoutall", $data);
	}
	public function customerpay($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();
		$data['title']    = display('room_reservation');
		$data["bookingdata"] = $this->roomreservation_model->detailbooking($id);
		$data['module'] = "room_reservation";
		$data['page']   = "custdetails";
		$this->load->view("room_reservation/customerdetails", $data);
	}
	public function submitcheckout($bookedid)
	{
		$bid = explode(",", $bookedid);
		$creditamount = $this->input->post("creditamount", true);
		$refunddamt = $this->input->post("refunddamt", true);
		$disamount = $this->input->post("disamount", true);
		$allcomplementarycharge = $this->input->post("allcomplementarycharge", true);
		$allbpccharge = $this->input->post("allbpccharge", true);
		$additionalcharge = $this->input->post("additionalcharge", true);
		$specialdis = $this->input->post("specialdis", true);
		$poolbill = $this->input->post("poolbill", true);
		$restbill = $this->input->post("restbill", true);
		$poolid = $this->input->post("poolid", true);
		$orderid = $this->input->post("orderid", true);
		$hallid = $this->input->post("hallid", true);
		$parking_id = $this->input->post("parking_id", true);
		$parkingbill = $this->input->post("parkingbill", true);
		// CRITICAL FIX: Ensure parkingbill is numeric and not null (column doesn't allow null)
		$parkingbill = floatval($parkingbill ?? 0);
		$rid = $orderid ?? '';
		$mrid = explode(",,", $rid);
		$netamount = 0;

		if (!empty($orderid)) {
			for ($i = 0; $i < count($mrid); $i++) {
				$srid = explode(",", $mrid[$i]);
				for ($j = 0; $j < count($srid); $j++) {
					$ritems[$i][$j]     = $this->roomreservation_model->ritemdatasingle($srid[$j]);
				}
			}
			for ($i = 0; $i < count($ritems); $i++) {
				for ($j = 0; $j < count($ritems[$i]); $j++) {
					$netbill = $ritems[$i][$j];
				}
			}
			foreach ($netbill->details as $value) {
				$netamount += floatval($value->subtotal ?? 0);
			}
		}


			$nod = $this->input->post("nod", true);
		$taxamount = floatval($this->input->post("taxamount", true) ?? 0);
		$scharge = floatval($this->input->post("scharge", true) ?? 0);
		$payableamt = floatval($this->input->post("payableamt", true) ?? 0);
		$paymentmode = $this->input->post("paymentmode", true);
		$paymentamount = $this->input->post("paymentamount", true);
		$bankname = $this->input->post("bankname", true);
		$cardno = $this->input->post("cardno", true);
		// Get tax details using centralized method
		$taxdetail = $this->roomreservation_model->getActiveTaxRates();
		$taxname = "";
		$rate = "";

		if (!empty($taxdetail)) {
			foreach ($taxdetail as $taxinfo) {
				$taxname .= $taxinfo->taxname . ",";
				$rate .= $taxinfo->rate . ",";
			}
		}
		$mspoolid = explode(",,", $poolid ?? '');
		$msorderid = explode(",,", $orderid ?? '');
		$mshallid = explode(",,", $hallid ?? '');
		$allorderbill = 0;
		$restscharge = 0;
		$allpoolbill = 0;
		$allhallbill = 0;
		for ($i = 0; $i < count($bid); $i++) {
			$totalbill = $this->db->select("total_price,booking_source,commissionamount,cutomerid,booked_details.advance_amount")->from("booked_info")->join("booked_details", "booked_details.bookedid=booked_info.bookedid", "left")->where("booked_info.bookedid", $bid[$i])->get()->row();
			// CRITICAL FIX: Cast to float to prevent "string * string" TypeError in PHP 8+
			$totalPrice = floatval($totalbill->total_price ?? 0);
			$nodays = floatval(trim($nod ?? '') == 0 ? 1 : trim($nod ?? ''));
			$bill = $totalPrice * $nodays;
			$checkoutdata = array(
				'bookedid' => $bid[$i],
				'paid_amount' => $bill,
				'bookingstatus' => 5,
			);

			$result = $this->db->where("bookedid", $bid[$i])->update("booked_info", $checkoutdata);

			if ($result && $totalbill->booking_source) {
				$balance = $this->db->select("balance")->from("tbl_booking_type_info")->where("btypeinfoid", $totalbill->booking_source)->get()->row();
				$newbalance = floatval($balance->balance ?? 0) + floatval($totalbill->commissionamount ?? 0);
				$bl = array(
					'balance' => $newbalance
				);
				$this->db->where("btypeinfoid", $totalbill->booking_source)->update("tbl_booking_type_info", $bl);
			}
			if ($result && ($creditamount || $totalbill->advance_amount)) {
				//customer balance reduction for credit amount
				$credit = $this->db->select("balance")->from("customerinfo")->where("customerid", $totalbill->cutomerid)->get()->row();
				$newcredit = floatval($credit->balance ?? 0) - floatval($creditamount ?? 0) - floatval($totalbill->advance_amount ?? 0);
				$cramount = array(
					'balance' => $newcredit
				);
				$this->db->where("customerid", $totalbill->cutomerid)->update("customerinfo", $cramount);
			}
			if (!empty($poolid)) {
				$spoolid = explode(",", $mspoolid[$i]);
				for ($j = 0; $j < count($spoolid); $j++) {
					$spoollbill = $this->db->select("total_amount")->from("tbl_pool_booking")->where("pbookingid", $spoolid[$j])->get()->row();
					$allpoolbill += floatval($spoollbill->total_amount ?? 0);
				}
			}
			if (!empty($orderid)) {
				$sorder = explode(",", $msorderid[$i]);
				for ($k = 0; $k < count($sorder); $k++) {
					$sorderlbill = $this->db->select("totalamount")->from("customer_order")->where("order_id", $sorder[$k])->get()->row();
					$sbill = $this->db->select("service_charge")->from("bill")->where("order_id", $sorder[$k])->get()->row();
					$allorderbill += floatval($sorderlbill->totalamount ?? 0);
					$restscharge += floatval($sbill->service_charge ?? 0);
				}
			}
			if (!empty($hallid)) {
				$shallid = explode(",", $mshallid[$i]);
				for ($j = 0; $j < count($shallid); $j++) {
					$shallbill = $this->db->select("totalamount")->from("tbl_hallroom_booking")->where("hbid", $shallid[$j])->get()->row();
					$allhallbill += floatval($shallbill->totalamount ?? 0);
				}
			}

			$btax = array(
				'bookedid' => $bid[$i],
				'taskname' => trim($taxname, ","),
				'rate' => trim($rate, ","),
				'scharge' => $scharge,
				'credit' => $creditamount,
				'complementary' => $allcomplementarycharge,
				'additional_charges' => $additionalcharge,
			'extrabpc' => $allbpccharge,
			'ex_discount' => $disamount,
			'swimming_pool' => $allpoolbill,
			'restaurant' => $allorderbill,
			'hallroom' => $allhallbill,
			'car_parking' => $parkingbill,  // Already ensured to be numeric above
			'special_discount' => $specialdis,
			'checkoutdate' => date("Y-m-d H:i:s"),
		);

			if ($result) {
				$this->db->insert("tbl_postedbills", $btax);
				if (!empty($poolid)) {
					for ($j = 0; $j < count($spoolid); $j++) {
						$paid = $this->db->select("total_amount")->from("tbl_pool_booking")->where("pbookingid", $spoolid[$j])->get()->row();
						$this->db->where("pbookingid", $spoolid[$j])->update("tbl_pool_booking", array('paid_amount' => $paid->total_amount, 'status' => 1));
					}
				}
				if (!empty($orderid)) {
					for ($k = 0; $k < count($sorder); $k++) {
						$paidbill = $this->db->select("totalamount")->from("customer_order")->where("order_id", $sorder[$k])->get()->row();
						$this->db->where("order_id", $sorder[$k])->update("customer_order", array('customerpaid' => $paidbill->totalamount, 'order_status' => 4));
						$this->db->where("order_id", $sorder[$k])->update("bill", array('payment_method_id' => 0, 'bill_status' => 1));
					}
				}
				if (!empty($hallid)) {
					for ($j = 0; $j < count($shallid); $j++) {
						$paidhall = $this->db->select("totalamount")->from("tbl_hallroom_booking")->where("hbid", $shallid[$j])->get()->row();
						$this->db->where("hbid", $shallid[$j])->update("tbl_hallroom_booking", array('paid_amount' => $paidhall->totalamount, 'payment_status' => 1));
					}
				}
				if (!empty($parking_id)) {
					$sparking_id = explode(",", $parking_id);
					$this->db->where_in("bookParking_id", $sparking_id)->update("tbl_bookParking", array('status' => 0));
				}
				$roomno = $this->db->select("room_no")->from("booked_info")->where("bookedid", $bid[$i])->get()->row();
				$singleroom = explode(",", $roomno->room_no);

				for ($l = 0; $l < count($singleroom); $l++) {
					$this->db->where("roomno", $singleroom[$l])->update("tbl_roomnofloorassign", array("status" => 1));
				}
			}
		}
		if ($result) {
			if ($paymentamount > 0) {
				// Start database transaction for payment processing
				$this->db->trans_start();

				try {
					// Validate payment amounts
					$validation = $this->validatePaymentAmounts($paymentamount, $payableamt);
					if (!$validation['valid']) {
						throw new Exception($validation['message']);
					}

					// Generate invoice number with race condition protection
					$invoice_no = $this->roomreservation_model->generateInvoiceNumber();
					$newdate = date("Y-m-d H:i:s");
				$saveid = $this->session->userdata('id');
				$singlepayment = explode(",", $paymentmode);
				$singleamount = explode(",", $paymentamount);
				$singlebankname = explode(",", $bankname);
				$singlecardno = explode(",", $cardno);
				for ($i = 0, $j = 0; $i < count($singlepayment); $i++) {
					if ($i == (count($singlepayment) - 1)) {
						//change ammount refunded to customer
						$singleamount[$i] -= (!empty($refunddamt) ? $refunddamt : 0);
					}
					$postData = array(
						'bookedid' 	         	 => $bid[0],
						'invoice' 	             => $invoice_no,
						'paydate' 	             => $newdate,
						'paymenttype' 	         => $singlepayment[$i],
						'paymentamount' 	     => $singleamount[$i],
						'details' 	     		 => "Card/Account No: " . $singlecardno[$i] . " Bank Name: " . $singlebankname[$i],
						'book_type' 	     	 => 0,
					);
					$this->db->insert('tbl_guestpayments', $postData);
					//Payment method Debit for paid value
					if ($singlepayment[$i] == "Bank Payment") {
						$query = $this->db->query("SELECT HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%' And HeadName LIKE '$singlebankname[$j]'");
						$row = $query->row();
						$headcode = (!empty($row->HeadCode) ? $row->HeadCode : null);
						if (empty($headcode)) {
							$coa = $this->roomreservation_model->headcode(4, 1020102);
							if ($coa->HeadCode != NULL) {
								$headcode = $coa->HeadCode + 1;
							} else {
								$headcode = "102010201";
							}
							//insert Coa for Customer Receivable
							$postData1['HeadCode']   	= $headcode;
							$postData1['HeadName']   	= $singlebankname[$j];
							$postData1['PHeadName']   	= 'Cash At Bank';
							$postData1['HeadLevel']   	= '4';
							$postData1['IsActive']  	= '1';
							$postData1['IsTransaction'] = '1';
							$postData1['IsGL']   		= '0';
							$postData1['HeadType']  	= 'A';
							$postData1['IsBudget'] 		= '0';
							$postData1['IsDepreciation'] = '0';
							$postData1['DepreciationRate'] = '0';
							$postData1['CreateBy'] 		= $saveid;
							$postData1['CreateDate'] 	= $newdate;
							$this->db->insert('acc_coa', $postData1);
							//end
						}
						$narration = 'Cash in Bank Debited For ' . $singlebankname[$j] . ' Invoice#' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, $headcode, $narration, $singleamount[$i], 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
						$j++;
					} else if ($singlepayment[$i] == "SSLCommerz") {
						$narration = 'Cash in SSLCOMMERZ Debited For Invoice#' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 102010302, $narration, $singleamount[$i], 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
					} else if ($singlepayment[$i] == "Cash Payment") {
						$narration = 'Cash in Hand Debited For Invoice#' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 1020101, $narration, $singleamount[$i], 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
					} else if ($singlepayment[$i] == "Paypal") {
						$narration = 'Cash in Paypal Debited For Invoice#' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 102010301, $narration, $singleamount[$i], 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
					} else if ($singlepayment[$i] == "Card Payment") {
						$narration = 'Cash in Card Debited For Invoice#' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 102010304, $narration, $singleamount[$i], 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
					} else {
						$path = 'application/modules/';
						$map  = directory_map($path);
						$HmvcMenu   = array();
						if (is_array($map) && sizeof($map) > 0)
							foreach ($map as $key => $value) {
								$env = str_replace("\\", '/', $path . $key . 'assets/data/env');
								$transaction = str_replace("\\", '/', $path . $key . 'controllers/transaction.php');
								if (file_exists($env)) {
									if (file_exists($transaction)) {
										@include($transaction);
										if ($singlepayment[$i] == $paymentMethod) {
											$narration = 'Cash in ' . $paymentMethod . ' Debited For Invoice#' . $invoice_no;
											transaction($invoice_no, 'CIV', $newdate, $headCode, $narration, $singleamount[$i], 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
										}
									}
								}
							}
						$query = $this->db->query("SELECT HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020103%' And HeadName LIKE '$singlepayment[$i]'");
						$row = $query->row();
						$headcode = (!empty($row->HeadCode) ? $row->HeadCode : null);
					}
				}

				//Customer debit for Rent Value
				$narration = 'Customer debit for Rent Invoice# ' . $invoice_no;
				transaction($invoice_no, 'CIV', $newdate, 102030101, $narration, $payableamt, 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
				if ($payableamt > 0) {
					//Hotel Owner credit for Hotel Rent Value
					$narration = 'Hotel Credited for Hotel Rent Invoice# ' . $invoice_no;
					// CRITICAL FIX: Cast all values to float to prevent "non-numeric value encountered" warnings
					$s_amount = floatval($payableamt) - floatval($allpoolbill) - floatval($netamount) - floatval($taxamount) - (floatval($allorderbill) - floatval($netamount)) - floatval($scharge);
					transaction($invoice_no, 'CIV', $newdate, 30301, $narration, 0, $s_amount, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
					//Hotel Owner credit for Hotel Service Charge
					$narration = 'Hotel Credited for Hotel Service Charge Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 30304, $narration, 0, $scharge, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
				}
				//Hotel Owner credit for Swimming Pool Rent Value
				if ($allpoolbill > 0) {
					$narration = 'Hotel Credited for Swimming Pool Rent Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 30302, $narration, 0, $allpoolbill, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
				}
				//Hotel Owner credit for Restauramt food Value
				if ($netamount > 0) {
					$narration = 'Hotel Credited for Restaurant Food Invoice# ' . $invoice_no;
					$n_amount = $netamount - $restscharge;
					transaction($invoice_no, 'CIV', $newdate, 30303, $narration, 0, $n_amount, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
					//restaurant s charge
					$narration = 'Hotel Credited for Restaurant Food Service Charge Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 30304, $narration, 0, $restscharge, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
				}
				// Customer Credit for paid amount.
				$narration = 'Customer Credit for Rent Invoice# ' . $invoice_no;
				transaction($invoice_no, 'CIV', $newdate, 102030101, $narration, 0, $payableamt, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);

				//Debited tax in tax recievable
				$narration = 'Hotel Debited For Hotel Room TAX Invoice# ' . $invoice_no;
				transaction($invoice_no, 'CIV', $newdate, 1020204, $narration, $taxamount, 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);

				//Credited tax in tax payable
				$narration = 'Hotel Credited For Hotel Room TAX Invoice# ' . $invoice_no;
				transaction($invoice_no, 'CIV', $newdate, 5020303, $narration, 0, $taxamount, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
				$resttax = 0;
				if ($netamount > 0) {
					$resttax = $allorderbill - $netamount;
					//Debited tax in tax recievable for restaurant
					$narration = 'Hotel Debited For Restaurant TAX Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 1020204, $narration, $resttax, 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);

					//Credited tax in tax payable for restaurant
					$narration = 'Hotel Credited For Restaurant TAX Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 5020303, $narration, 0, $resttax, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
				}

					// Complete transaction
					$this->db->trans_complete();

					if ($this->db->trans_status() === FALSE) {
						throw new Exception('Payment processing failed');
					}

				} catch (Exception $e) {
					$this->db->trans_rollback();
					log_message('error', 'Checkout payment failed: ' . $e->getMessage());
					echo '<h5>Failed</h5>Payment processing failed. Please try again.';
					exit;
				}
			} else {
				$creditedRent = $this->db->select("at.Credit,at.ID,VNo")->from("acc_transaction at")->join("tbl_guestpayments tg", "tg.invoice=at.VNo", "left")->where("COAID", 30301)->where("tg.bookedid", $bid[0])->get()->row();
				if (!empty($creditedRent)) {
					$invoice_no = $creditedRent->VNo;
					$newdate = date("Y-m-d H:i:s");
					$saveid = $this->session->userdata('id');
					$amount = $creditedRent->Credit - $taxamount - $scharge;
					if ($amount > 0) {
						//Debited tax in tax recievable
						$narration = 'Hotel Debited For Hotel Room TAX Invoice# ' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 1020204, $narration, $taxamount, 0, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);

						//Credited tax in tax payable
						$narration = 'Hotel Credited For Hotel Room TAX Invoice# ' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 5020303, $narration, 0, $taxamount, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
						//Hotel Owner credit for Hotel Service Charge
						$narration = 'Hotel Credited for Hotel Service Charge Invoice# ' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 30304, $narration, 0, $scharge, 0, 1, $saveid, $newdate, 1, $bid[0], $invoice_no);
						//removing tax and service charge from rent
						$this->db->where("ID", $creditedRent->ID)->update("acc_transaction", array('Credit' => $amount));
					}
				}
			}
			//generate pdf


			$this->load->library('pdfgenerator');
			$file = $this->viewdetailsprint($bid[0], 'pdf');
			$file_path = $this->pdfgenerator->generate_pdf($bid[0], $file);
			//sending email to customer
			$binfo = $this->db->select("b.booking_number,b.room_no,b.total_price,c.firstname,c.email,b.cutomerid")->from("booked_info b")->join("customerinfo c", "c.customerid=b.cutomerid", "left")->where("bookedid", $bid[0])->get()->row();
			$this->email_send($binfo, 5, $file_path);
			//end
			//sending SMS to customer
			if (ENVIRONMENT == "production" && !empty($binfo)) {
				$type = "checkout";
				$response = $this->lsoft_setting->send_sms($binfo->booking_number, $binfo->cutomerid, $type, $bid[0]);
				$data = json_decode($response);
				// SMS response is logged but not displayed to avoid cluttering the UI
			}
			//end

			echo '<h5>Success</h5>Checkout Successfully';
			exit;
		} else {
			echo '<h5>Failed</h5>Please Try Again';
		}
	}
	public function cancelreservation($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();
		$data['title']    = display('room_reservation');
		$data['module'] = "room_reservation";
		$data["paymentdetails"] = $this->roomreservation_model->get_all('*', 'payment_method', 'payment_method_id');
		$data["banklist"] = $this->db->query("SELECT HeadCode,HeadName FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%'")->result();
		$data['bookedid']   = $id;
		$data['page']   = "cancelreservation";
		$this->load->view("room_reservation/cancelreservation", $data);
	}
	public function bookingSource()
	{
		$booking_type = $this->input->post('booking_type', TRUE);
		$bSource = $this->roomreservation_model->readall('*', 'tbl_booking_type_info', 'btypeinfoid', array('booking_type' => trim($booking_type ?? '')));
		$data = array(
			'soruce' => $bSource,
		);
		echo json_encode($data);
	}
	public function bsourcerate()
	{
		$booking_source = $this->input->post('booking_source', TRUE);
		$bSource = $this->roomreservation_model->readone('commissionrate', 'tbl_booking_type_info', array('btypeinfoid' => trim($booking_source ?? '')));
		$data = array(
			'commissionrate' => $bSource->commissionrate,
		);
		echo json_encode($data);
	}
	public function getroomno()
	{
		$room_type = $this->input->post('room_type', TRUE);
		$allroom = $this->roomreservation_model->read2('roomno', 'tbl_roomnofloorassign', 'roomno', array('roomid' => $room_type), array('status' => 1));
		$typename = $this->db->select("roomtype")->from("roomdetails")->where("roomid", $room_type)->get()->row();
		$complementary = $this->roomreservation_model->read2('complementaryname,rate', 'tbl_complementary', 'complementary_id', array('roomtype' => $typename->roomtype), null);
		$data = array(
			'roomno' => $allroom,
			'complementary' => $complementary,
		);
		echo json_encode($data);
	}
	public function checknewroom()
	{
		$room_type = $this->input->post('room_type', TRUE);
		$bookingid = $this->input->post('bookingid', TRUE);
		$checkin = $this->input->post('datefilter1', true);
		$checkout = $this->input->post('datefilter2', true);
		$status = "bookingstatus!=1 AND bookingstatus!=5";
		$croom = "FIND_IN_SET(" . $room_type . ",roomid)";
		$exits = $this->db->select("*")->from('booked_info')->where('checkindate<=', $checkin)->where('checkoutdate>', $checkin)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
		$exit = $this->db->select("*")->from('booked_info')->where('checkindate<', $checkout)->where('checkoutdate>=', $checkout)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
		$check = $this->db->select("*")->from('booked_info')->where('checkindate>', $checkin)->where('checkoutdate<=', $checkout)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
		$totalroom1 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate<=', $checkin)->where('checkoutdate>', $checkin)->where($status)->where("$croom !=", 0)->get()->row();
		$totalroom2 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate<', $checkout)->where('checkoutdate>=', $checkout)->where($status)->where("$croom !=", 0)->get()->row();
		$totalroom3 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate>=', $checkin)->where('checkoutdate<=', $checkout)->where($status)->where("$croom !=", 0)->group_by('checkindate')->get()->result();
		$allbokedroom3 = (!empty($allbokedroom3) ? max(array_column($totalroom3, 'allroom')) : 0);
		$totalroomfound = $this->db->select("count(roomid) as totalroom")->from('tbl_roomnofloorassign')->where('roomid', $room_type)->get()->row();
		$roomdetails = $this->db->select("*")->from('roomdetails')->where('roomid', $room_type)->get()->row();
		$numberlist = $this->db->select("*")->from('tbl_roomnofloorassign')->where('roomid', $room_type)->get()->result();
		$roomlist = '';
		foreach ($numberlist as $singleno) {
			$roomlist .= $singleno->roomno . ',';
		}
		$gtroomno = rtrim($roomlist, ',');
		if (empty($exits) && empty($exit) && empty($check)) {
			$allroom = $gtroomno;
			$data['isfound'] = 0;
		} else {
			$bookedroom = "";
			if (!empty($exits)) {
				foreach ($exits as $booked) {
					$bookedroom .= $booked->room_no . ',';
				}
			}
			if (!empty($exit)) {
				foreach ($exit as $ex) {
					$bookedroom .= $ex->room_no . ',';
				}
			}
			if (!empty($check)) {
				foreach ($check as $ch) {
					$bookedroom .= $ch->room_no . ',';
				}
			}
			$getbookedall = rtrim($bookedroom, ',');
			$allbokedroom1 = $totalroom1->allroom;
			$allbokedroom2 = $totalroom2->allroom;
			$allbokedroom = max((int)$allbokedroom1, (int)$allbokedroom2, (int)$allbokedroom3);
			$allfreeroom = $totalroomfound->totalroom;
			if ($allfreeroom > $allbokedroom) {
				$output = $this->Differences($getbookedall, $gtroomno);
				if (!empty($output)) {
					$allroom = $output;
					$data['isfound'] = '1';
				} else {
					$allroom = '';
					$data['isfound'] = '2';
				}
			} else {
				$allroom = '';
				$data['isfound'] = '2';
			}
		}
		$typename = $this->db->select("roomtype")->from("roomdetails")->where("roomid", $room_type)->get()->row();
		$complementary = $this->roomreservation_model->read2('complementaryname,rate', 'tbl_complementary', 'complementary_id', array('roomtype' => $typename->roomtype), null);

		$data['chargeinfo'] = $this->roomreservation_model->chargeinfo();
		$availableroom = explode(",", $allroom);
		$room_list = explode(",", $gtroomno);
		$free_room = array_intersect($room_list, $availableroom);
		$data = array(
			'roomno' => array_values($free_room),
			'complementary' => $complementary,
		);
		echo json_encode($data);
	}
	public function getcapacity()
	{
		$start = $this->input->post('start', TRUE);
		$end = $this->input->post('end', TRUE);
		$start_date = strtotime($start);
		$end_date = strtotime($end);
		$difference = $end_date - $start_date;
		$days =  ceil($difference / (60 * 60 * 24));
		$roomno = $this->input->post('roomno', TRUE);
		$roomid = $this->db->select("roomid")->from("tbl_roomnofloorassign")->where("roomno", $roomno)->get()->row();
		$capacity = $this->roomreservation_model->readone('capacity,rate,exbedcapability', 'roomdetails', array('roomid' => $roomid->roomid));
		$newDate = date('Y-m-d', strtotime($start . ' -1 day'));
		$totalOffer = 0;
		for ($i = 0; $i < $days; $i++) {
			$newDate = date('Y-m-d', strtotime($newDate . ' +1 day'));
			$offer_amount = $this->roomreservation_model->readone('offer', 'tbl_room_offer', array('roomid' => $roomid->roomid), array('offer_date' => $newDate));
			if (empty($offer_amount)) {
				$offer = 0;
			} else {
				$offer = $offer_amount->offer;
			}
			$totalOffer += $offer;
		}
		$data = array(
			'capacity' => $capacity->capacity,
			'price' => $capacity->rate * $days,
			'offer_amount' => $totalOffer,
			'excapacity' => $capacity->exbedcapability,
		);
		echo json_encode($data);
	}
	public function bedprice()
	{
		$room_type = $this->input->post('room_type', TRUE);
		$bed = $this->input->post('bed', TRUE);
		$bedprice = $this->db->select("bedcharge")->from("roomdetails")->where("roomid", $room_type)->get()->row();
		$data = array(
			'bedrate' => $bedprice->bedcharge * $bed,
		);
		echo json_encode($data);
	}
	public function personprice()
	{
		$room_type = $this->input->post('room_type', TRUE);
		$person = $this->input->post('person', TRUE);
		$personprice = $this->db->select("personcharge")->from("roomdetails")->where("roomid", $room_type)->get()->row();
		$data = array(
			'personrate' => $personprice->personcharge * $person,
		);
		echo json_encode($data);
	}
	public function childprice()
	{
		$room_type = $this->input->post('room_type', TRUE);
		$child = $this->input->post('child', TRUE);
		$childprice = $this->db->select("personcharge")->from("roomdetails")->where("roomid", $room_type)->get()->row();
		$data = array(
			'childrate' => ($childprice->personcharge / 2) * $child,
		);
		echo json_encode($data);
	}
	public function imageupload()
	{
		$image = $this->fileupload->do_upload(
			'assets/img/customer/',
			'img'
		);

		// if image is uploaded then resize the image
		if ($image !== false && $image != null) {
			$this->fileupload->do_resize(
				$image,
				500,
				500
			);
		}
		//if image is not uploaded
		if ($image === false) {
			echo "<h5>Failed</h5>Invalid Image Format";
			exit;
		}
		echo $image;
	}
	public function newBooking()
	{
	    
		//reservation details
		$bookingid = $this->input->post('bookingid', TRUE);
		$datefilter1 = $this->input->post('datefilter1', TRUE);
		$datefilter2 = $this->input->post('datefilter2', TRUE);
		$booking_type = $this->input->post('booking_type', TRUE);
		$booking_source = $this->input->post('booking_source', TRUE);
		$bsorurce_no = $this->input->post('bsorurce_no', TRUE);
		$arrival_from = $this->input->post('arrival_from', TRUE);
		$pof_visit = $this->input->post('pof_visit', TRUE);
		$booking_remarks = $this->input->post('booking_remarks', TRUE);
		//room details
		$room_type = $this->input->post('room_type', TRUE);
		$roomno = $this->input->post('roomno', TRUE);
		$adults = $this->input->post('adults', TRUE);
		$children = $this->input->post('children', TRUE);
		$bed = $this->input->post('bed', TRUE);
		$amount1 = $this->input->post('amount1', TRUE);
		$person = $this->input->post('person', TRUE);
		$amount2 = $this->input->post('amount2', TRUE);
		$child = $this->input->post('child', TRUE);
		$amount3 = $this->input->post('amount3', TRUE);
		$extrastart = $this->input->post('extrastart', TRUE);
		$extraend = $this->input->post('extraend', TRUE);
		$rent = $this->input->post('rent', TRUE);
		$discount_price = $this->input->post('discount_price', TRUE);
		$complementary = $this->input->post('complementary', TRUE);
		$complementaryprice = $this->input->post('complementaryprice', TRUE);

		//payment details
		$discountreason = $this->input->post('discountreason', TRUE);
		$discountamount = $this->input->post('discountamount', TRUE);
		$commissionrate = $this->input->post('commissionrate', TRUE);
		$commissionamount = $this->input->post('commissionamount', TRUE);
		$paymentmode = $this->input->post('paymentmode', TRUE);
		$bankname = $this->input->post('bankname', TRUE);
		$cardno = $this->input->post('cardno', TRUE);
		$advanceamount = $this->input->post('advanceamount', TRUE);
		$advanceremarks = $this->input->post('advanceremarks', TRUE);
		//user details
		$userid = $this->input->post('userid', TRUE);
		$alluserid = explode(",", trim($userid ?? ''));
		$name = $this->input->post('name', TRUE);
		$allname = explode(",", trim($name ?? ''));
		$mobile = $this->input->post('mobile', TRUE);
		$allmobile = explode(",", trim($mobile ?? ''));
		$email = $this->input->post('email', TRUE);
		$allemail = explode(",", trim($email ?? ''));
		$lastname = $this->input->post('lastname', TRUE);
		$alllastname = explode(",", trim($lastname ?? ''));
		$gender = $this->input->post('gender', TRUE);
		$allgender = explode(",", trim($gender ?? ''));
		$father = $this->input->post('father', TRUE);
		$occupation = $this->input->post('occupation', TRUE);
		$dob = $this->input->post('dob', TRUE);
		$anniversary = $this->input->post('anniversary', TRUE);
		$pitype = $this->input->post('pitype', TRUE);
		$allpitype = explode(",", trim($pitype ?? ''));
		$pid = $this->input->post('pid', TRUE);
		$allpid = explode(",", trim($pid ?? ''));
		$imgfront = $this->input->post('imgfront', TRUE);
		$allimgfront = explode(",", trim($imgfront ?? ''));
		$imgback = $this->input->post('imgback', TRUE);
		$allimgback = explode(",", trim($imgback ?? ''));
		$imgguest = $this->input->post('imgguest', TRUE);
		$allimgguest = explode(",", trim($imgguest ?? ''));
		$contacttype = $this->input->post('contacttype', TRUE);
		$state = $this->input->post('state', TRUE);
		$city = $this->input->post('city', TRUE);
		$zipcode = $this->input->post('zipcode', TRUE);
		$address = $this->input->post('address', TRUE);
		$country =$this->input->post('country', TRUE);
		$nationality =$this->input->post('nationality', TRUE);
	
		$allroom = explode(",", trim($roomno ?? ''));
		$price = explode(",", trim($rent ?? ''));
		// Calculate base rent (sum of all room rents per day) - WITHOUT discount or tax
		$totalprice = 0;
		for ($i = 0; $i < count($price); $i++) {
			$totalprice += $price[$i];
		}
		// Store base rent (discount and tax will be calculated later in booking list)
		$baseRent = $totalprice;
	

		$extradays = "";
		$exstart = explode(",", $extrastart);
		$exend = explode(",", $extraend);
		for ($r = 0; $r < count($allroom); $r++) {
			$start_date = strtotime($exstart[$r]);
			$end_date = strtotime($exend[$r]);
			$difference = $end_date - $start_date;
			$extradays .=  ceil($difference / (60 * 60 * 24)) . ",";
		}
		$allextradays = trim($extradays, ",");
		$paystackSecret = null;
		if($paymentmode=='Card Payment'){
		$this->config->load('paystack', TRUE);
		$paystackSecret = $this->config->item('paystack_secret', 'paystack');
		
		}

		//user details insert
		if ($bookingid) {
			$bookedid = $this->db->select("full_guest_name,cutomerid,room_no,booked_info.bookedid,advance_amount,promocode,checkindate,checkoutdate")->from("booked_info")->join("booked_details", "booked_details.bookedid=booked_info.bookedid", "left")->where("booked_info.bookedid", $bookingid)->get()->row();
			$customer = explode(",", $bookedid->full_guest_name);
			$room_no = $bookedid->room_no;
			$roomnum = explode(",", $room_no);
			$roomstatus = array(
				'status' => 1
			);
			for ($i = 0; $i < count($roomnum); $i++) {
				$this->db->where("roomno", $roomnum[$i])->update("tbl_roomnofloorassign", $roomstatus);
			}
			$oldbid = $bookedid->bookedid;
			$promocode = 0;
			if (!empty($bookedid->promocode)) {
				$pdiscount = $this->db->select("discount")->from("promocode")->where("promocode", $bookedid->promocode)->get()->row();
				$promocode = $pdiscount->discount;
			}
			// Update customer balance with new payment (add to existing balance)
			if ($advanceamount > 0) {
				$credit = $this->db->select("balance")->from("customerinfo")->where("customerid", $bookedid->cutomerid)->get()->row();
				$newcredit = $credit->balance + $advanceamount;
				$cramount = array(
					'balance' => $newcredit
				);
				$this->db->where("customerid", $bookedid->cutomerid)->update("customerinfo", $cramount);
			}
		}
		if (empty($alluserid[0])) {
			if ((!empty($customer[0]) ? $customer[0] : null) != $allname[0] && !empty($allname[0])) {
				$lastid = $this->db->select("*")->from('customerinfo')->order_by('customerid', 'desc')->get()->row();
				if (!empty($lastid)) {
					$sl = (int)$lastid->customerid;
				} else {
					$sl = "0001";
				}
				$nextno = $sl + 1;
				$si_length = strlen((int)$nextno);

				$str = '0000';
				$cutstr = substr($str, $si_length);
				$sino = $cutstr . $nextno;
				$userdata = array(
					'firstname'   => $allname[0],
					'lastname' 	  => $alllastname[0],
					'customernumber'   => $sino,
					'cust_phone'  => $allmobile[0],
					'email' 	  => $allemail[0],
					'gender' 	  => $allgender[0],
					'fathername'  => $this->input->post('father', TRUE),
					'profession'  => $this->input->post('occupation', TRUE),
					'dob' 	  	  => $this->input->post('dob', TRUE),
					'pass' 	      => md5('123456'),
					'anniversary' => $this->input->post('anniversary', TRUE),
					'pitype' 	  => $allpitype[0],
					'pid' 	  	  => $allpid[0],
					'imgfront' 	  => $allimgfront[0],
					'imgback' 	  => $allimgback[0],
					'imgguest' 	  => (!empty($allimgguest[0]) ? $allimgguest[0] : ""),
					'contacttype' => $this->input->post('contacttype', TRUE),
					'country' 	  => $this->input->post('country', TRUE),
					'nationality'=> $this->input->post('nationality', TRUE),
					'city' 		  =>$this->input->post('state', TRUE) .', '.$this->input->post('city', TRUE),
					'zipcode' 	  => $this->input->post('zipcode', TRUE),
					'address' 	  => $this->input->post('address', TRUE),
					'signupdate'  => date('Y-m-d')
				);
			

				$this->db->insert('customerinfo', $userdata);
				//end
				$customerid = $this->db->insert_id();

				//insert Coa for Customer Receivable
				//end
			} else {
				$customerid = $bookedid->cutomerid;
			}
		} else {
			$customerid = $alluserid[0];
		}
		//booking info insert
		if (empty($this->input->post('bookingid', TRUE))) {
			$bookinginfo = $this->db->select("*")->from('booked_info')->order_by('bookedid', 'desc')->get()->row();
			if (!empty($bookinginfo)) {
				$bookno = $bookinginfo->bookedid;
			} else {
				$bookno = "00000000";
			}

			$nextno = $bookno + 1;
			$bk_length = strlen((int)$nextno);

			$bkstr = '00000000';
			$bknumber = substr($bkstr, $bk_length);
			$bookingnumber = $bknumber . $nextno;
			// Store base rent per day in total_price (discount and tax are calculated in booking list)
			// total_price should be base rent per day, not including discount or tax
			$postData = array(
				'booking_number' 	     => $bookingnumber,
				'date_time' 	             => date('Y-m-d H:i:s'),
				'roomid' 	             => $room_type,
				'nuofpeople'              => $adults,
				'children'              	 => $children,
				'total_room'              => count($allroom),
				'room_no'              	 => trim($roomno ?? ''),
				'roomrate'                => $rent,
				'offer_discount'          => trim($discount_price ?? '', ","),
				'total_price'             => $baseRent,
				'paid_amount'             => $advanceamount,
				'coments'                 => 'Booking from admin',
				'checkindate'             => $datefilter1,
				'checkoutdate'            => $datefilter2,
				'cutomerid' 	             => $customerid,
				'full_guest_name' 	     => trim($name ?? ''),
				'bookingstatus' 	         => 0
			);

			if ($advanceamount) {
				$credit = $this->db->select("balance")->from("customerinfo")->where("customerid", $customerid)->get()->row();
				$newcredit = $credit->balance + $advanceamount;
				$cramount = array(
					'balance' => $newcredit
				);
				$this->db->where("customerid", $customerid)->update("customerinfo", $cramount);
			}
			for ($ch = 0; $ch < count($allroom); $ch++) {
				$status = "bookingstatus!=1 AND bookingstatus!=5";
				$croom = "FIND_IN_SET(" . $allroom[$ch] . ",room_no)";
				$exits = $this->db->select("*")->from('booked_info')->where('checkindate<=', $datefilter1)->where('checkoutdate>', $datefilter1)->where($status)->where("$croom !=", 0)->get()->result();
				$exit = $this->db->select("*")->from('booked_info')->where('checkindate<', $datefilter2)->where('checkoutdate>=', $datefilter2)->where($status)->where("$croom !=", 0)->get()->result();
				$check = $this->db->select("*")->from('booked_info')->where('checkindate>', $datefilter1)->where('checkoutdate<=', $datefilter2)->where($status)->where("$croom !=", 0)->get()->result();
				if (!empty($exits) || !empty($exit) || !empty($check)) {
					echo '<h5>Failed</h5>Room No ' . $allroom[$ch] . ' is not available';
					exit;
				}
			}
			$this->permission->method('room_reservation', 'create')->redirect();
			if ($this->roomreservation_model->create($postData)) {
				//end
				$bookedid = $this->db->insert_id();
				//Save bank details to tbl_guestpayments even if advance amount is 0
				$this->save_initial_payment_details($bookedid, $paymentmode, $bankname, $cardno, $advanceamount);
				//Customer Advance account transaction
				if ($advanceamount > 0) {
					$this->advance_payment($bookedid, $paymentmode, $advanceamount, null);
				}
				//insert into booking details
				$bdetails_data = array(
					'bookedid'   => $bookedid,
					'booking_type'   => $booking_type,
					'booking_source'   => $booking_source,
					'booking_source_no'   => $bsorurce_no,
					'extracheckin'   => $extrastart,
					'extracheckout'   => $extraend,
					'arival_from'   => $arrival_from,
					'purpose'   => $pof_visit,
					'extra_facility_days'   => $allextradays,
					'extrabed'   => trim($bed ?? '', ","),
					'extraperson'   => trim($person ?? '', ","),
					'extrachild'   => trim($child ?? '', ","),
					'complementary'   => trim($complementary ?? '', ","),
					'complementaryprice'   => trim($complementaryprice ?? '', ","),
					'discountreason'   => $discountreason,
					'discountamount'   => $discountamount,
					'commissionpersent'   => $commissionrate,
					'commissionamount'   => $commissionamount,
					'payment_method'   => $paymentmode,
					'advance_amount'   => $advanceamount,
					'advance_remarks'   => $advanceremarks,
					'remarks'   => $booking_remarks
				);
			
				$this->db->insert('booked_details', $bdetails_data);
				//end
				//insert other guest
				if ($customerid) {
					for ($l = 1; $l < count($allname); $l++) {
						if (empty($alluserid[$l])) {
							$guestdata = array(
								'bookedid'   => $bookedid,
								'guestname'   => $allname[$l],
								'mobile' 	  => (!empty($allmobile[$l]) ? $allmobile[$l] : null),
								'email'   => (!empty($allemail[$l]) ? $allemail[$l] : null),
								'gender'   => (!empty($allgender[$l]) ? $allgender[$l] : null),
								'photo_id_type'  => (!empty($allpitype[$l]) ? $allpitype[$l] : null),
								'photo_id' 	  => (!empty($allpid[$l]) ? $allpid[$l] : null),
								'front_image' 	  => (!empty($allimgfront[$l]) ? $allimgfront[$l] : null),
								'back_image'  => (!empty($allimgback[$l]) ? $allimgback[$l] : null),
								'occupant_image'  => (!empty($allimgguest[$l]) ? $allimgguest[$l] : null),
							);
						} else {
							$guestdata = array(
								'bookedid'   => $bookedid,
								'customerid'   => $alluserid[$l],
							);
						}
						$this->db->insert("tbl_otherguest", $guestdata);
					}
				}
				//end
				//sending email to customer
				$binfo = $this->db->select("b.booking_number,b.room_no,b.total_price, b.checkindate,b.checkoutdate,c.firstname,c.email")->from("booked_info b")->join("customerinfo c", "c.customerid=b.cutomerid", "left")->where("bookedid", $bookedid)->get()->row();
				
		if ($binfo && !empty($paystackSecret)) {
    $binfo->paystack = $paystackSecret;
}

				$this->email_send($binfo);
				//end
				if (ENVIRONMENT == "production") {
					$msg = "";
					$type = "processing";
					$response = $this->lsoft_setting->send_sms($bookingnumber, $customerid, $type, $bookedid);
					$data = json_decode($response);
					$msg = $data->message;
					if ($msg)
						echo '<h5>Success</h5>';
				}
				if (empty($msg)) {
					echo '<h5>Success</h5>Saved Successfully';
				} else {
					echo 'Saved Successfully<br>' . $msg;
				}
			} else {
				echo '<h5>Failed</h5>Please Try Again';
			}
		} else {
			// Store base rent per day in total_price (discount and tax are calculated in booking list)
			// total_price should be base rent per day, not including discount or tax
			$this->permission->method('room_reservation', 'update')->redirect();
			$updateData = array(
				'bookedid' 	             => $bookingid,
				'roomid' 	             => $room_type,
				'nuofpeople'              => $adults,
				'children'              => $children,
				'total_room'              => count($allroom),
				'room_no'              	 => trim($roomno ?? ''),
				'roomrate'                => $rent,
				'offer_discount'          => trim($discount_price ?? '', ","),
				'total_price'             => $baseRent,
				'paid_amount'             => $advanceamount,
				'coments'                 => 'Booking from admin',
				'checkindate'             => $datefilter1,
				'checkoutdate'            => $datefilter2,
				'cutomerid' 	             => $customerid,
				'full_guest_name' 	     => trim($name ?? ''),
			);
			for ($ch = 0; $ch < count($allroom); $ch++) {
				if ($oldbid != $bookingid | $bookedid->checkindate != $datefilter1 | $bookedid->checkoutdate != $datefilter2) {
					$status = "bookingstatus!=1 AND bookingstatus!=5";
					$croom = "FIND_IN_SET(" . $allroom[$ch] . ",room_no)";
					$exits = $this->db->select("*")->from('booked_info')->where('checkindate<=', $datefilter1)->where('checkoutdate>', $datefilter1)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
					$exit = $this->db->select("*")->from('booked_info')->where('checkindate<', $datefilter2)->where('checkoutdate>=', $datefilter2)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
					$check = $this->db->select("*")->from('booked_info')->where('checkindate>', $datefilter1)->where('checkoutdate<=', $datefilter2)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
					if (!empty($exits) || !empty($exit) || !empty($check)) {
						echo '<h5>Failed</h5>Room No ' . $allroom[$ch] . ' is not available';
						exit;
					}
				}
			}
			if ($this->roomreservation_model->update($updateData)) {
				if ($advanceamount > 0) {
					$this->advance_payment($bookingid, $paymentmode, $advanceamount, 1);
				}
				//insert into booking details
				$bdetails_data = array(
					'booking_type'   => $booking_type,
					'booking_source'   => $booking_source,
					'booking_source_no'   => $bsorurce_no,
					'extracheckin'   => $extrastart,
					'extracheckout'   => $extraend,
					'arival_from'   => $arrival_from,
					'purpose'   => $pof_visit,
					'extra_facility_days'   => $allextradays,
					'extrabed'   => trim($bed ?? '', ","),
					'extraperson'   => trim($person ?? '', ","),
					'extrachild'   => trim($child ?? '', ","),
					'complementary'   => trim($complementary ?? '', ","),
					'complementaryprice'   => trim($complementaryprice ?? '', ","),
					'discountreason'   => $discountreason,
					'discountamount'   => $discountamount,
					'commissionpersent'   => $commissionrate,
					'commissionamount'   => $commissionamount,
					'payment_method'   => $paymentmode,
					'advance_amount'   => $advanceamount,
					'advance_remarks'   => $advanceremarks,
					'remarks'   => $booking_remarks
				);
				$this->db->where("bookedid", $bookingid)->update('booked_details', $bdetails_data);
				//end
				//other guest update and insert
				$gid = $this->db->select("otherguest_id")->from("tbl_otherguest")->where("bookedid", $bookingid)->get()->result();
				for ($l = 1; $l < count($allname); $l++) {
					if (empty($alluserid[$l])) {
						$guestdata = array(
							'bookedid'   => $bookingid,
							'guestname'   => $allname[$l],
							'mobile' 	  => (!empty($allmobile[$l]) ? $allmobile[$l] : null),
							'email'   => (!empty($allemail[$l]) ? $allemail[$l] : null),
							'gender'   => (!empty($allgender[$l]) ? $allgender[$l] : null),
							'photo_id_type'  => (!empty($allpitype[$l]) ? $allpitype[$l] : null),
							'photo_id' 	  => (!empty($allpid[$l]) ? $allpid[$l] : null),
							'front_image' 	  => (!empty($allimgfront[$l]) ? $allimgfront[$l] : null),
							'back_image'  => (!empty($allimgback[$l]) ? $allimgback[$l] : null),
							'occupant_image'  => (!empty($allimgguest[$l]) ? $allimgguest[$l] : null),
						);
					} else {
						$guestdata = array(
							'bookedid'   => $bookingid,
							'customerid'   => $alluserid[$l],
						);
					}
					if (empty($gid[$l - 1]->otherguest_id)) {
						$this->db->insert("tbl_otherguest", $guestdata);
					} else {
						$this->db->where("otherguest_id", $gid[$l - 1]->otherguest_id)->update('tbl_otherguest', $guestdata);
					}
				}
				if (count($gid) > (count($allname) - 1)) {
					for ($gl = count($allname) - 1; $gl < count($gid); $gl++) {
						$this->db->where("otherguest_id", $gid[$gl]->otherguest_id)->delete('tbl_otherguest');
					}
				}
				//end
				echo '<h5>Success</h5>Updated Successfully';
			} else {
				echo '<h5>Failed</h5>Please Try Again';
			}
		}
	}
	public function checkinBooking()
	{
		
		//reservation details
		$bookingid = $this->input->post('bookingid', TRUE);
		$datefilter1 = $this->input->post('datefilter1', TRUE);
		$datefilter2 = $this->input->post('datefilter2', TRUE);
		$booking_type = $this->input->post('booking_type', TRUE);
		$booking_source = $this->input->post('booking_source', TRUE);
		$bsorurce_no = $this->input->post('bsorurce_no', TRUE);
		$arrival_from = $this->input->post('arrival_from', TRUE);
		$pof_visit = $this->input->post('pof_visit', TRUE);
		$booking_remarks = $this->input->post('booking_remarks', TRUE);
		//room details
		$room_type = $this->input->post('room_type', TRUE);
		$roomno = $this->input->post('roomno', TRUE);
		$adults = $this->input->post('adults', TRUE);
		$children = $this->input->post('children', TRUE);
		$bed = $this->input->post('bed', TRUE);
		$amount1 = $this->input->post('amount1', TRUE);
		$person = $this->input->post('person', TRUE);
		$amount2 = $this->input->post('amount2', TRUE);
		$child = $this->input->post('child', TRUE);
		$amount3 = $this->input->post('amount3', TRUE);
		$extrastart = $this->input->post('extrastart', TRUE);
		$extraend = $this->input->post('extraend', TRUE);
		$rent = $this->input->post('rent', TRUE);
		$discount_price = $this->input->post('discount_price', TRUE);
		$complementary = $this->input->post('complementary', TRUE);
		$complementaryprice = $this->input->post('complementaryprice', TRUE);

		//payment details
		$discountreason = $this->input->post('discountreason', TRUE);
		$discountamount = $this->input->post('discountamount', TRUE);
		$commissionrate = $this->input->post('commissionrate', TRUE);
		$commissionamount = $this->input->post('commissionamount', TRUE);
		$paymentmode = $this->input->post('paymentmode', TRUE);
		$bankname = $this->input->post('bankname', TRUE);
		$cardno = $this->input->post('cardno', TRUE);
		$advanceamount = $this->input->post('advanceamount', TRUE);
		$advanceremarks = $this->input->post('advanceremarks', TRUE);
		//user details
		$userid = $this->input->post('userid', TRUE);
		$alluserid = explode(",", trim($userid ?? ''));
		$name = $this->input->post('name', TRUE);
		$allname = explode(",", trim($name ?? ''));
		$mobile = $this->input->post('mobile', TRUE);
		$lastname = $this->input->post('lastname', TRUE);
		$gender = $this->input->post('gender', TRUE);
		$father = $this->input->post('father', TRUE);
		$occupation = $this->input->post('occupation', TRUE);
		$dob = $this->input->post('dob', TRUE);
		$anniversary = $this->input->post('anniversary', TRUE);
		$pitype = $this->input->post('pitype', TRUE);
		$imgfront = $this->input->post('imgfront', TRUE);
		$imgback = $this->input->post('imgback', TRUE);
		$imgguest = $this->input->post('imgguest', TRUE);
		$contacttype = $this->input->post('contacttype', TRUE);
		$state = $this->input->post('state', TRUE);
		$city = $this->input->post('city', TRUE);
		$zipcode = $this->input->post('zipcode', TRUE);
		$address = $this->input->post('address', TRUE);
		$paystackSecret ='';
		//end
		$allroom = explode(",", trim($roomno ?? ''));
		$price = explode(",", trim($rent ?? ''));
		// Calculate base rent (sum of all room rents per day) - WITHOUT discount or tax
		$totalprice = 0;
	
		for ($i = 0; $i < count($price); $i++) {
			$totalprice += $price[$i];
		}
		// Store base rent (discount and tax will be calculated later in booking list)
		//use paystack for card payment;
		if($paymentmode=='Card Payment'){
		$this->config->load('paystack', TRUE);
		$paystackSecret = $this->config->item('paystack_secret', 'paystack');
		
		}
	
		$baseRent = $totalprice;
		$extradays = "";
		$exstart = explode(",", $extrastart);
		$exend = explode(",", $extraend);
		for ($r = 0; $r < count($allroom); $r++) {
			$start_date = strtotime($exstart[$r]);
			$end_date = strtotime($exend[$r]);
			$difference = $end_date - $start_date;
			$extradays .=  ceil($difference / (60 * 60 * 24)) . ",";
		}
		$allextradays = trim($extradays, ",");

		//user details insert
		if ($bookingid) 
			{
			$bookedid = $this->db->select("full_guest_name,cutomerid,room_no,booked_info.bookedid,advance_amount,promocode,checkindate,checkoutdate")->from("booked_info")->join("booked_details", "booked_details.bookedid=booked_info.bookedid", "left")->where("booked_info.bookedid", $bookingid)->get()->row();
			$customer = explode(",", $bookedid->full_guest_name);
			$room_no = $bookedid->room_no;
			$roomnum = explode(",", $room_no);
			$roomstatus = array(
				'status' => 1
			);
			for ($i = 0; $i < count($roomnum); $i++) {
				$this->db->where("roomno", $roomnum[$i])->update("tbl_roomnofloorassign", $roomstatus);
			}
			$oldbid = $bookedid->bookedid;
			$promocode = 0;
			if (!empty($bookedid->promocode)) {
				$pdiscount = $this->db->select("discount")->from("promocode")->where("promocode", $bookedid->promocode)->get()->row();
				$promocode = $pdiscount->discount;
			}
			// Update customer balance with new payment (add to existing balance)
			if ($advanceamount > 0) {
				$credit = $this->db->select("balance")->from("customerinfo")->where("customerid", $bookedid->cutomerid)->get()->row();
				$newcredit = $credit->balance + $advanceamount;
				$cramount = array(
					'balance' => $newcredit
				);
				$this->db->where("customerid", $bookedid->cutomerid)->update("customerinfo", $cramount);
			}
		}
		
		if (empty($alluserid[0])) {
			if ((!empty($customer[0]) ? $customer[0] : null) != $allname[0] && !empty($allname[0])) {
				$lastid = $this->db->select("*")->from('customerinfo')->order_by('customerid', 'desc')->get()->row();
				if (!empty($lastid)) {
					$sl = (int)$lastid->customerid;
				} else {
					$sl = "0001";
				}
				$nextno = $sl + 1;
				$si_length = strlen((int)$nextno);

				$str = '0000';
				$cutstr = substr($str, $si_length);
				$sino = $cutstr . $nextno;
				$userdata = array(
					'firstname'   => $allname[0],
					'customernumber'   => $sino,
					'lastname' 	  => $this->input->post('lastname', TRUE),
					'cust_phone'  => $this->input->post('mobile', TRUE),
					'email' 	  => $this->input->post('email', TRUE),
					'gender' 	  => $this->input->post('gender', TRUE),
					'fathername'  => $this->input->post('father', TRUE),
					'profession'  => $this->input->post('occupation', TRUE),
					'dob' 	  	  => $this->input->post('dob', TRUE),
					'pass' 	      => md5('123456'),
					'anniversary' => $this->input->post('anniversary', TRUE),
					'pitype' 	  => $this->input->post('pitype', TRUE),
					'imgfront' 	  => $this->input->post('imgfront', TRUE),
					'imgback' 	  => $this->input->post('imgback', TRUE),
					'imgguest' 	  => $this->input->post('imgguest', TRUE),
					'contacttype' => $this->input->post('contacttype', TRUE),
					'country' 	  => $this->input->post('country', TRUE),
					'city' 		  => $this->input->post('city', TRUE),
					'zipcode' 	  => $this->input->post('zipcode', TRUE),
					'address' 	  => $this->input->post('address', TRUE),
					'nationality'=>$this->input->post('nationality', TRUE),
					'signupdate'  => date('Y-m-d')
				);

				$this->db->insert('customerinfo', $userdata);
				//end
				$customerid = $this->db->insert_id();
			} else {
				$customerid = $bookedid->cutomerid;
			}
		} else {
			$customerid = $alluserid[0];
		}
		//booking info insert
		if (empty($this->input->post('bookingid', TRUE))) {
			$bookinginfo = $this->db->select("*")->from('booked_info')->order_by('bookedid', 'desc')->get()->row();
			if (!empty($bookinginfo)) {
				$bookno = $bookinginfo->bookedid;
			} else {
				$bookno = "00000000";
			}

			$nextno = $bookno + 1;
			$bk_length = strlen((int)$nextno);

			$bkstr = '00000000';
			$bknumber = substr($bkstr, $bk_length);
			$bookingnumber = $bknumber . $nextno;
			// Store base rent per day in total_price (discount and tax are calculated in booking list)
			// total_price should be base rent per day, not including discount or tax
			$postData = array(
				'booking_number' 	     => $bookingnumber,
				'date_time' 	             => date('Y-m-d H:i:s'),
				'roomid' 	             => $room_type,
				'nuofpeople'              => $adults,
				'children'              => $children,
				'total_room'              => count($allroom),
				'room_no'              	 => trim($roomno ?? ''),
				'roomrate'                => $rent,
				'offer_discount'          => trim($discount_price ?? '', ","),
				'total_price'             => $baseRent,
				'paid_amount'             => $advanceamount,
				'coments'                 => 'Booking from admin',
				'checkindate'             => $datefilter1,
				'checkoutdate'            => $datefilter2,
				'cutomerid' 	             => $customerid,
				'full_guest_name' 	     => trim($name ?? ''),
				'bookingstatus' 	         => 4
			);

			if ($advanceamount) {
				$credit = $this->db->select("balance")->from("customerinfo")->where("customerid", $customerid)->get()->row();
				$newcredit = $credit->balance + $advanceamount;
				$cramount = array(
					'balance' => $newcredit
				);
				$this->db->where("customerid", $customerid)->update("customerinfo", $cramount);
			}
			for ($ch = 0; $ch < count($allroom); $ch++) {
				$status = "bookingstatus!=1 AND bookingstatus!=5";
				$croom = "FIND_IN_SET(" . $allroom[$ch] . ",room_no)";
				$exits = $this->db->select("*")->from('booked_info')->where('checkindate<=', $datefilter1)->where('checkoutdate>', $datefilter1)->where($status)->where("$croom !=", 0)->get()->result();
				$exit = $this->db->select("*")->from('booked_info')->where('checkindate<', $datefilter2)->where('checkoutdate>=', $datefilter2)->where($status)->where("$croom !=", 0)->get()->result();
				$check = $this->db->select("*")->from('booked_info')->where('checkindate>', $datefilter1)->where('checkoutdate<=', $datefilter2)->where($status)->where("$croom !=", 0)->get()->result();
				if (!empty($exits) || !empty($exit) || !empty($check)) {
					echo '<h5>Failed</h5>Room No ' . $allroom[$ch] . ' is not available';
					exit;
				}
			}
			$this->permission->method('room_reservation', 'create')->redirect();
			if ($this->roomreservation_model->create($postData)) {
				//end
				$bookedid = $this->db->insert_id();
				//Save bank details to tbl_guestpayments even if advance amount is 0
				$this->save_initial_payment_details($bookedid, $paymentmode, $bankname, $cardno, $advanceamount);
				if ($advanceamount > 0) {
					$this->advance_payment($bookedid, $paymentmode, $advanceamount, null);
				}
				//insert into booking details
				$bdetails_data = array(
					'bookedid'   => $bookedid,
					'booking_type'   => $booking_type,
					'booking_source'   => $booking_source,
					'booking_source_no'   => $bsorurce_no,
					'extracheckin'   => $extrastart,
					'extracheckout'   => $extraend,
					'arival_from'   => $arrival_from,
					'purpose'   => $pof_visit,
					'extra_facility_days'   => $allextradays,
					'extrabed'   => trim($bed ?? '', ","),
					'extraperson'   => trim($person ?? '', ","),
					'extrachild'   => trim($child ?? '', ","),
					'complementary'   => trim($complementary ?? '', ","),
					'complementaryprice'   => trim($complementaryprice ?? '', ","),
					'discountreason'   => $discountreason,
					'discountamount'   => $discountamount,
					'commissionpersent'   => $commissionrate,
					'commissionamount'   => $commissionamount,
					'commissionpersent'   => $commissionrate,
					'commissionamount'   => $commissionamount,
					'payment_method'   => $paymentmode,
					'advance_amount'   => $advanceamount,
					'advance_remarks'   => $advanceremarks,
					'remarks'   => $booking_remarks
				);
				$this->db->insert('booked_details', $bdetails_data);
				//end
				//insert other guest
				if ($customerid) {
					for ($l = 1; $l < count($allname); $l++) {
						if (empty($alluserid[$l])) {
							$guestdata = array(
								'bookedid'   => $bookedid,
								'guestname'   => $allname[$l],
								'mobile' 	  => (!empty($allmobile[$l]) ? $allmobile[$l] : null),
								'email'   => (!empty($allemail[$l]) ? $allemail[$l] : null),
								'gender'   => (!empty($allgender[$l]) ? $allgender[$l] : null),
								'photo_id_type'  => (!empty($allpitype[$l]) ? $allpitype[$l] : null),
								'photo_id' 	  => (!empty($allpid[$l]) ? $allpid[$l] : null),
								'front_image' 	  => (!empty($allimgfront[$l]) ? $allimgfront[$l] : null),
								'back_image'  => (!empty($allimgback[$l]) ? $allimgback[$l] : null),
								'occupant_image'  => (!empty($allimgguest[$l]) ? $allimgguest[$l] : null),
							);
						} else {
							$guestdata = array(
								'bookedid'   => $bookedid,
								'customerid'   => $alluserid[$l],
							);
						}
						$this->db->insert("tbl_otherguest", $guestdata);
					}
				}
				//end
				//sending email to customer
				$binfo = $this->db->select("b.booking_number,b.room_no,b.total_price,c.firstname,c.email")->from("booked_info b")->join("customerinfo c", "c.customerid=b.cutomerid", "left")->where("bookedid", $bookedid)->get()->row();
				
				if ($binfo) {
    if($paystackSecret){
        $binfo->paystack = $paystackSecret;
    }
   
}
				$this->email_send($binfo, 4);
				//end
				echo '<h5>Success</h5>Checkin Successfully';
			} else {
				echo '<h5>Failed</h5>Please Try Again';
			}
		} else {
			// Store base rent per day in total_price (discount and tax are calculated in booking list)
			// total_price should be base rent per day, not including discount or tax
			$this->permission->method('room_reservation', 'update')->redirect();

			// Get current paid amount from database to add new payment to it
			$current_booking = $this->db->select("paid_amount")->from("booked_info")->where("bookedid", $bookingid)->get()->row();
			$current_paid = !empty($current_booking->paid_amount) ? floatval($current_booking->paid_amount) : 0;
			$new_total_paid = $current_paid + floatval($advanceamount);

			$updateData = array(
				'bookedid' 	             => $bookingid,
				'roomid' 	             => $room_type,
				'nuofpeople'              => $adults,
				'children'              => $children,
				'total_room'              => count($allroom),
				'room_no'              	 => trim($roomno ?? ''),
				'roomrate'                => $rent,
				'offer_discount'          => trim($discount_price ?? '', ","),
				'total_price'             => $baseRent,
				'paid_amount'             => $new_total_paid,
				'coments'                 => 'Booking from admin',
				'checkindate'             => $datefilter1,
				'checkoutdate'            => $datefilter2,
				'cutomerid' 	             => $customerid,
				'full_guest_name' 	     => trim($name ?? ''),
				'bookingstatus' 	         => 4
			);
			for ($ch = 0; $ch < count($allroom); $ch++) {
				if ($oldbid != $bookingid | $bookedid->checkindate != $datefilter1 | $bookedid->checkoutdate != $datefilter2) {
					$status = "bookingstatus!=1 AND bookingstatus!=5";
					$croom = "FIND_IN_SET(" . $allroom[$ch] . ",room_no)";
					$exits = $this->db->select("*")->from('booked_info')->where('checkindate<=', $datefilter1)->where('checkoutdate>', $datefilter1)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
					$exit = $this->db->select("*")->from('booked_info')->where('checkindate<', $datefilter2)->where('checkoutdate>=', $datefilter2)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
					$check = $this->db->select("*")->from('booked_info')->where('checkindate>', $datefilter1)->where('checkoutdate<=', $datefilter2)->where($status)->where("$croom !=", 0)->where('bookedid!=', $bookingid)->get()->result();
					if (!empty($exits) || !empty($exit) || !empty($check)) {
						echo '<h5>Failed</h5>Room No ' . $allroom[$ch] . ' is not available';
						exit;
					}
				}
			}
			if ($this->roomreservation_model->update($updateData)) {
				if ($advanceamount > 0) {
					$this->advance_payment($bookingid, $paymentmode, $advanceamount, 1);
				}

				// Get current advance amount to add new payment to it
				$current_details = $this->db->select("advance_amount")->from("booked_details")->where("bookedid", $bookingid)->get()->row();
				$current_advance = !empty($current_details->advance_amount) ? floatval($current_details->advance_amount) : 0;
				$new_total_advance = $current_advance + floatval($advanceamount);

				//insert into booking details
				$bdetails_data = array(
					'booking_type'   => $booking_type,
					'booking_source'   => $booking_source,
					'booking_source_no'   => $bsorurce_no,
					'extracheckin'   => $extrastart,
					'extracheckout'   => $extraend,
					'arival_from'   => $arrival_from,
					'purpose'   => $pof_visit,
					'extra_facility_days'   => $allextradays,
					'extrabed'   => trim($bed ?? '', ","),
					'extraperson'   => trim($person ?? '', ","),
					'extrachild'   => trim($child ?? '', ","),
					'complementary'   => trim($complementary ?? '', ","),
					'complementaryprice'   => trim($complementaryprice ?? '', ","),
					'discountreason'   => $discountreason,
					'discountamount'   => $discountamount,
					'commissionpersent'   => $commissionrate,
					'commissionamount'   => $commissionamount,
					'commissionpersent'   => $commissionrate,
					'commissionamount'   => $commissionamount,
					'payment_method'   => $paymentmode,
					'advance_amount'   => $new_total_advance,
					'advance_remarks'   => $advanceremarks,
					'remarks'   => $booking_remarks
				);
				$this->db->where("bookedid", $bookingid)->update('booked_details', $bdetails_data);
				//end

				//other guest update and insert
				$gid = $this->db->select("otherguest_id")->from("tbl_otherguest")->where("bookedid", $bookingid)->get()->result();
				for ($l = 1; $l < count($allname); $l++) {
					if (empty($alluserid[$l])) {
						$guestdata = array(
							'bookedid'   => $bookingid,
							'guestname'   => $allname[$l],
							'mobile' 	  => (!empty($allmobile[$l]) ? $allmobile[$l] : null),
							'email'   => (!empty($allemail[$l]) ? $allemail[$l] : null),
							'gender'   => (!empty($allgender[$l]) ? $allgender[$l] : null),
							'photo_id_type'  => (!empty($allpitype[$l]) ? $allpitype[$l] : null),
							'photo_id' 	  => (!empty($allpid[$l]) ? $allpid[$l] : null),
							'front_image' 	  => (!empty($allimgfront[$l]) ? $allimgfront[$l] : null),
							'back_image'  => (!empty($allimgback[$l]) ? $allimgback[$l] : null),
							'occupant_image'  => (!empty($allimgguest[$l]) ? $allimgguest[$l] : null),
						);
					} else {
						$guestdata = array(
							'bookedid'   => $bookingid,
							'customerid'   => $alluserid[$l],
						);
					}
					if (empty($gid[$l - 1]->otherguest_id)) {
						$this->db->insert("tbl_otherguest", $guestdata);
					} else {
						$this->db->where("otherguest_id", $gid[$l - 1]->otherguest_id)->update('tbl_otherguest', $guestdata);
					}
				}
				if (count($gid) > (count($allname) - 1)) {
					for ($gl = count($allname) - 1; $gl < count($gid); $gl++) {
						$this->db->where("otherguest_id", $gid[$gl]->otherguest_id)->delete('tbl_otherguest');
					}
				}
				//end
				//sending email to customer
			
					
				
				$binfo = $this->db->select("b.booking_number,b.room_no,b.total_price,c.firstname,c.email")->from("booked_info b")->join("customerinfo c", "c.customerid=b.cutomerid", "left")->where("bookedid", $bookingid)->get()->row();
				
	if($paystackSecret){
				    
				       $binfo->paystack =$paystackSecret;
				
				        
				    }
				 
				$this->email_send($binfo, 4);
				//end
				if (ENVIRONMENT == "production") {
					$msg = "";
					$type = "completeorder";
					$response = $this->lsoft_setting->send_sms($bookingnumber, $customerid, $type, $bookingid);
					$data = json_decode($response);
					$msg = $data->message;
					if ($msg)
						echo '<h5>Success</h5>';
				}
				if (empty($msg)) {
					echo '<h5>Success</h5>Checkin Successfully';
				} else {
					echo 'Checkin Successfully<br>' . $msg;
				}
			} else {
				echo '<h5>Failed</h5>Please Try Again';
			}
		}
	}
	public function cancelbooking()
	{
		$this->form_validation->set_rules('cancelreason', "Cancel Reason", 'required|xss_clean');
		$pmethod = $this->input->post('pmethod', TRUE);
		if ($pmethod == "Bank Payment") {
			$this->form_validation->set_rules('bankName', "Bank Name", 'required|xss_clean');
		}
		$bookingid = $this->input->post('bookedid', TRUE);
		$cancelreason = $this->input->post('cancelreason', TRUE);
		$cancelationcharge = $this->input->post('cancelationcharge', TRUE);
		if ($this->form_validation->run()) {
			$cancel =  array(
				'coments' => $cancelreason,
				'paid_amount' => $cancelationcharge,
				'bookingstatus' => 1
			);
			$method = array(
				'payment_method' => $pmethod
			);
			$cancelbooking = $this->db->where("bookedid", $bookingid)->update("booked_info", $cancel);
			if ($cancelbooking && $pmethod) {
				$this->db->where("bookedid", $bookingid)->update("booked_details", $method);
			}
			if ($cancelbooking) {
				$allroom = $this->db->select("room_no")->from("booked_info")->where("bookedid", $bookingid)->get()->row();
				$roomno = explode(",", $allroom->room_no);
				$roomstatus = array(
					'status' => 1
				);
				for ($i = 0; $i < count($roomno); $i++) {
					$this->db->where("roomno", $roomno[$i])->update("tbl_roomnofloorassign", $roomstatus);
				}
				if ($cancelationcharge > 0) {
					// Start database transaction for cancellation payment
					$this->db->trans_start();

					try {
						$cardNumber = $this->input->post('cardNumber', TRUE);
						$bankName = $this->input->post('bankName', TRUE);
						// Generate invoice number with race condition protection
						$invoice_no = $this->roomreservation_model->generateInvoiceNumber();
						$newdate = date("Y-m-d H:i:s");
					    $saveid = $this->session->userdata('id');
					    $postData = array(
						'bookedid' 	         	 => $bookingid,
						'invoice' 	             => $invoice_no,
						'paydate' 	             => $newdate,
						'paymenttype' 	         => $pmethod,
						'paymentamount' 	     => $cancelationcharge,
						'details' 	     		 => "Card/Account No: " . $cardNumber . " Bank Name: " . $bankName,
						'book_type' 	     	 => 0,
					);
					$this->db->insert('tbl_guestpayments', $postData);
					//Payment method Debit for paid value
					if ($pmethod == "Bank Payment") {
						$query = $this->db->query("SELECT HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%' And HeadName LIKE '$bankName'");
						$row = $query->row();
						$headcode = (!empty($row->HeadCode) ? $row->HeadCode : null);
						if (empty($headcode)) {
							$coa = $this->roomreservation_model->headcode(4, 1020102);
							if ($coa->HeadCode != NULL) {
								$headcode = $coa->HeadCode + 1;
							} else {
								$headcode = "102010201";
							}
							//insert Coa for Customer Receivable
							$postData1['HeadCode']   	= $headcode;
							$postData1['HeadName']   	= $bankName;
							$postData1['PHeadName']   	= 'Cash At Bank';
							$postData1['HeadLevel']   	= '4';
							$postData1['IsActive']  	= '1';
							$postData1['IsTransaction'] = '1';
							$postData1['IsGL']   		= '0';
							$postData1['HeadType']  	= 'A';
							$postData1['IsBudget'] 		= '0';
							$postData1['IsDepreciation'] = '0';
							$postData1['DepreciationRate'] = '0';
							$postData1['CreateBy'] 		= $saveid;
							$postData1['CreateDate'] 	= $newdate;
							$this->db->insert('acc_coa', $postData1);
							//end
						}
						$narration = 'Cash in Bank Debited For ' . $bankName . ' Invoice# ' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, $headcode, $narration, $cancelationcharge, 0, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);
					} else if ($pmethod == "SSLCommerz") {
						$narration = 'Cash in SSLCOMMERZ Debited For Invoice# ' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 102010302, $narration, $cancelationcharge, 0, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);
					} else if ($pmethod == "Cash Payment") {
						$narration = 'Cash in Hand Debited For Invoice# ' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 1020101, $narration, $cancelationcharge, 0, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);
					} else if ($pmethod == "Paypal") {
						$narration = 'Cash in Paypal Debited For Invoice# ' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 102010301, $narration, $cancelationcharge, 0, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);
					} else if ($pmethod == "Card Payment") {
						$narration = 'Cash in Card Debited For Invoice# ' . $invoice_no;
						transaction($invoice_no, 'CIV', $newdate, 102010304, $narration, $cancelationcharge, 0, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);
					} else {
						$path = 'application/modules/';
						$map  = directory_map($path);
						$HmvcMenu   = array();
						if (is_array($map) && sizeof($map) > 0)
							foreach ($map as $key => $value) {
								$env = str_replace("\\", '/', $path . $key . 'assets/data/env');
								$transaction = str_replace("\\", '/', $path . $key . 'controllers/transaction.php');
								if (file_exists($env)) {
									if (file_exists($transaction)) {
										@include($transaction);
										if ($pmethod == $paymentMethod) {
											$narration = 'Cash in Paystack Debited For Invoice# ' . $invoice_no;
											transaction($invoice_no, 'CIV', $newdate, $headCode, $narration, $cancelationcharge, 0, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);
										}
									}
								}
							}
						$query = $this->db->query("SELECT HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020103%' And HeadName LIKE '$pmethod'");
						$row = $query->row();
						$headcode = (!empty($row->HeadCode) ? $row->HeadCode : null);
					}

					//Customer debit for Rent Value
					$narration = 'Customer debited for Rent Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 102030101, $narration, $cancelationcharge, 0, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);
					//Hotel Owner credit for Hotel Rent Value
					$narration = 'Hotel Credited for Hotel Rent Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 30301, $narration, 0, $cancelationcharge, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);
					// Customer Credit for paid amount.
					$narration = 'Customer Credited for Rent Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 102030101, $narration, 0, $cancelationcharge, 0, 1, $saveid, $newdate, 1, $bookingid, $invoice_no);

					// Complete transaction
					$this->db->trans_complete();

					if ($this->db->trans_status() === FALSE) {
						throw new Exception('Cancellation payment processing failed');
					}

				} catch (Exception $e) {
					$this->db->trans_rollback();
					log_message('error', 'Cancellation payment failed: ' . $e->getMessage());
					$this->session->set_flashdata('exception', 'Payment processing failed. Please try again.');
					redirect($_SERVER['HTTP_REFERER']);
					return;
				}
				}
			}
			$this->session->set_flashdata('message', "Reservation Canceled Successfully");
			redirect($_SERVER['HTTP_REFERER']);
		} else {
			$data['module'] = "room_reservation";
			$data['page']   = "reservationlist";
			echo Modules::run('template/layout', $data);
		}
	}
	/**
	 * Auto-cancel pending bookings older than specified hours
	 *
	 * This method finds all pending bookings (status = 0) that:
	 * - Were created more than X hours ago
	 * - Have no advance payment (or payment below threshold)
	 * - Have not passed their check-in date
	 *
	 * @param int $hours Hours threshold before auto-cancellation (default: 24)
	 * @param float $minPaymentThreshold Minimum payment to prevent auto-cancel (default: 0)
	 * @return array Results summary with success status and counts
	 */
	public function autoCancelPendingBookings($hours = 24, $minPaymentThreshold = 0)
	{
		// Start transaction for data integrity
		$this->db->trans_start();

		try {
			// Calculate cutoff time (X hours ago from now)
			$cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

			// Find pending bookings that meet cancellation criteria
			$pendingBookings = $this->db
				->select('bookedid, booking_number, room_no, date_time, paid_amount, cutomerid, checkindate, total_price')
				->from('booked_info')
				->where('bookingstatus', 0) // Only pending bookings
				->where('date_time <', $cutoffTime) // Older than threshold
				->where('paid_amount <=', floatval($minPaymentThreshold)) // No significant payment
				->where('checkindate >=', date('Y-m-d')) // Check-in date hasn't passed
				->get()
				->result();

			$cancelledCount = 0;
			$errors = array();
			$cancelledBookings = array();

			foreach ($pendingBookings as $booking) {
				try {
					// Calculate hours pending for logging
					$bookingTime = strtotime($booking->date_time);
					$currentTime = time();
					$hoursPending = round(($currentTime - $bookingTime) / 3600, 1);

					// Cancel the booking
					$cancelReason = "Auto-cancelled - No payment received within {$hours} hours of booking";
					$this->autoCancelSingleBooking($booking, $cancelReason);

					$cancelledCount++;
					$cancelledBookings[] = array(
						'booking_number' => $booking->booking_number,
						'bookedid' => $booking->bookedid,
						'hours_pending' => $hoursPending
					);

					// Log the auto-cancellation
					log_message('info', "Auto-cancelled booking #{$booking->booking_number} (ID: {$booking->bookedid}) - Pending for {$hoursPending} hours");
				} catch (Exception $e) {
					$errors[] = "Booking #{$booking->booking_number}: " . $e->getMessage();
					log_message('error', "Auto-cancel failed for booking #{$booking->booking_number} (ID: {$booking->bookedid}): " . $e->getMessage());
				}
			}

			// Complete transaction
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				throw new Exception('Transaction failed during auto-cancellation');
			}

			$result = array(
				'success' => true,
				'cancelled_count' => $cancelledCount,
				'total_checked' => count($pendingBookings),
				'errors' => $errors,
				'cutoff_time' => $cutoffTime,
				'cancelled_bookings' => $cancelledBookings,
				'execution_time' => date('Y-m-d H:i:s')
			);

			if ($cancelledCount > 0) {
				log_message('info', "Auto-cancel batch completed: {$cancelledCount} bookings cancelled out of " . count($pendingBookings) . " checked");
			}

			return $result;
		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Auto-cancel batch failed: ' . $e->getMessage());

			return array(
				'success' => false,
				'error' => $e->getMessage(),
				'cancelled_count' => 0,
				'execution_time' => date('Y-m-d H:i:s')
			);
		}
	}

	/**
	 * Cancel a single booking (used by auto-cancel)
	 *
	 * This is a simplified version of cancelbooking() that:
	 * - Updates booking status to cancelled (1)
	 * - Frees up associated rooms
	 * - Optionally sends notification email
	 *
	 * @param object $booking Booking record from database
	 * @param string $reason Cancellation reason for logging
	 * @param bool $sendEmail Whether to send cancellation email (default: false)
	 */
	private function autoCancelSingleBooking($booking, $reason = "Auto-cancelled - No payment after 24 hours", $sendEmail = false)
	{
		// Update booking status to cancelled
		$cancelData = array(
			'bookingstatus' => 1, // Cancelled
			'coments' => $reason,
			// Keep paid_amount as is (in case there was partial payment)
		);

		$updateResult = $this->db->where('bookedid', $booking->bookedid)
			->update('booked_info', $cancelData);

		if (!$updateResult) {
			throw new Exception('Failed to update booking status');
		}

		// Free up rooms - set status back to available (1)
		if (!empty($booking->room_no)) {
			$roomNumbers = explode(',', $booking->room_no);
			$roomstatus = array('status' => 1); // Available

			foreach ($roomNumbers as $roomno) {
				$roomno = trim($roomno);
				if (!empty($roomno)) {
					$roomUpdate = $this->db->where('roomno', $roomno)
						->update('tbl_roomnofloorassign', $roomstatus);

					if (!$roomUpdate) {
						log_message('warning', "Failed to free up room {$roomno} for booking #{$booking->booking_number}");
					}
				}
			}
		}

		// Optional: Send cancellation email to customer
		if ($sendEmail && !empty($booking->cutomerid)) {
			try {
				$customerInfo = $this->db->select('email, firstname, lastname')
					->from('customerinfo')
					->where('customerid', $booking->cutomerid)
					->get()
					->row();

				if (!empty($customerInfo) && !empty($customerInfo->email)) {
					$this->sendAutoCancellationEmail($customerInfo, $booking, $reason);
				}
			} catch (Exception $e) {
				// Don't fail the cancellation if email fails
				log_message('error', "Failed to send cancellation email for booking #{$booking->booking_number}: " . $e->getMessage());
			}
		}
	}

	/**
	 * Send cancellation email notification to customer
	 *
	 * @param object $customer Customer information
	 * @param object $booking Booking information
	 * @param string $reason Cancellation reason
	 */
	private function sendAutoCancellationEmail($customer, $booking, $reason)
	{
		// Get hotel settings
		$hotelSettings = $this->db->select('title, email, phone, address')
			->from('setting')
			->where('id', 2)
			->get()
			->row();

		$hotelName = !empty($hotelSettings->title) ? $hotelSettings->title : 'Hotel';
		$hotelEmail = !empty($hotelSettings->email) ? $hotelSettings->email : 'info@hotel.com';
		$hotelPhone = !empty($hotelSettings->phone) ? $hotelSettings->phone : '';
		$hotelAddress = !empty($hotelSettings->address) ? $hotelSettings->address : '';

		$subject = "Booking #{$booking->booking_number} - Automatic Cancellation Notice";
		$guestName = ucwords(trim($customer->firstname . ' ' . $customer->lastname));

		// Build email HTML content
		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($subject); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #1f6f54;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .footer {
            background: #333;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 5px 5px;
            font-size: 12px;
        }
        .notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= html_escape($hotelName); ?></h1>
        <p>Booking Cancellation Notice</p>
    </div>
    <div class="content">
        <p>Dear <?= html_escape($guestName); ?>,</p>

        <p>We regret to inform you that your booking <strong>#<?= html_escape($booking->booking_number); ?></strong> has been automatically cancelled.</p>

        <div class="notice">
            <strong>Reason:</strong> <?= html_escape($reason); ?>
        </div>

        <p>This cancellation occurred because no payment was received within the required timeframe after booking.</p>

        <p>If you believe this is an error or if you still wish to make a reservation, please contact us immediately:</p>

        <ul>
            <li><strong>Phone:</strong> <?= html_escape($hotelPhone); ?></li>
            <li><strong>Email:</strong> <?= html_escape($hotelEmail); ?></li>
        </ul>

        <p>We apologize for any inconvenience and hope to serve you in the future.</p>

        <p>Best regards,<br>
        <?= html_escape($hotelName); ?> Team</p>
    </div>
    <div class="footer">
        <p><?= html_escape($hotelAddress); ?></p>
        <p>&copy; <?= date('Y'); ?> <?= html_escape($hotelName); ?>. All rights reserved.</p>
    </div>
</body>
</html>
		<?php
		$htmlContent = ob_get_clean();

		// Send email using existing email functionality
		$this->roomreservation_model->send_email(
			strtolower($customer->email),
			$subject,
			$hotelName,
			$htmlContent
		);
	}

	/**
	 * Cron job endpoint for auto-cancelling pending bookings
	 *
	 * This endpoint can be called via HTTP request (cron job) or directly
	 *
	 * Access URL: /room_reservation/auto-cancel-pending
	 *
	 * Optional GET parameters:
	 * - key: Security key for authentication (recommended)
	 * - hours: Hours threshold before cancellation (default: 24)
	 * - min_payment: Minimum payment threshold (default: 0)
	 *
	 * Example:
	 * /room_reservation/auto-cancel-pending?key=your-secret-key&hours=24&min_payment=0
	 *
	 * Returns JSON response with execution results
	 */
	public function autoCancelPending()
	{
		// Optional: Add security key check to prevent unauthorized access
		$securityKey = $this->input->get('key', TRUE);
		$expectedKey = $this->config->item('auto_cancel_secret_key'); // Add to config/config.php

		// If key is configured, require it
		if (!empty($expectedKey) && $securityKey !== $expectedKey) {
			header('HTTP/1.1 403 Forbidden');
			header('Content-Type: application/json');
			echo json_encode(array(
				'success' => false,
				'error' => 'Unauthorized access. Invalid or missing security key.',
				'timestamp' => date('Y-m-d H:i:s')
			), JSON_PRETTY_PRINT);
			exit;
		}

		// Get optional parameters
		$hours = (int)$this->input->get('hours', TRUE);
		if ($hours <= 0) {
			$hours = 24; // Default to 24 hours
		}

		$minPayment = (float)$this->input->get('min_payment', TRUE);
		if ($minPayment < 0) {
			$minPayment = 0; // Default to 0
		}

		// Execute auto-cancel
		$result = $this->autoCancelPendingBookings($hours, $minPayment);

		// Return JSON response for cron monitoring
		header('Content-Type: application/json');
		echo json_encode($result, JSON_PRETTY_PRINT);
	}

	/**
	 * Manual trigger for auto-cancel (for admin use)
	 *
	 * Access: /room_reservation/manual-auto-cancel
	 * Requires admin authentication
	 */
	public function manualAutoCancel()
	{
		// Require admin authentication
		$this->permission->method('room_reservation', 'update')->redirect();

		// Get parameters from POST or use defaults
		$hours = (int)$this->input->post('hours', TRUE) ?: 24;
		$minPayment = (float)$this->input->post('min_payment', TRUE) ?: 0;

		// Execute auto-cancel
		$result = $this->autoCancelPendingBookings($hours, $minPayment);

		// Return result
		if ($result['success']) {
			$this->session->set_flashdata('message', "Auto-cancel completed: {$result['cancelled_count']} booking(s) cancelled");
		} else {
			$this->session->set_flashdata('exception', "Auto-cancel failed: " . (isset($result['error']) ? $result['error'] : 'Unknown error'));
		}

		redirect('room_reservation/booking-list');
	}

	/**
	 * AUTO-CHECKOUT SYSTEM
	 * =====================================================
	 * Automatically checks out guests when their checkout time is due
	 * and sends them a thank you email with feedback request
	 * =====================================================
	 */

	/**
	 * Find and auto-checkout guests whose checkout time has passed
	 *
	 * This method finds all checked-in guests (status = 4) whose:
	 * - Scheduled checkout date and time has passed
	 * - Are currently checked in
	 *
	 * @return array Results with success status, checkout count, and details
	 */
	public function autoCheckoutOverdueGuests()
	{
		// Get current date and time
		$currentDateTime = date('Y-m-d H:i:s');

		$result = array(
			'success' => true,
			'checked_out_count' => 0,
			'total_checked' => 0,
			'errors' => array(),
			'checked_out_bookings' => array(),
			'execution_time' => $currentDateTime
		);

		try {
			// Find all checked-in guests (status = 4) whose checkout time has passed
			$overdueGuests = $this->db->select('
				b.bookedid,
				b.booking_number,
				b.room_no,
				b.checkoutdate,
				b.cutomerid,
				c.firstname,
				c.lastname,
				c.email,
				c.cust_phone
			')
			->from('booked_info b')
			->join('customerinfo c', 'c.customerid = b.cutomerid', 'left')
			->where('b.bookingstatus', 4) // Status 4 = Checked In
			->where('b.checkoutdate <=', $currentDateTime) // Checkout time has passed
			->get()
			->result();

			$result['total_checked'] = count($overdueGuests);

			// Process each overdue guest
			foreach ($overdueGuests as $guest) {
				try {
					// Perform auto-checkout
					$checkoutSuccess = $this->performAutoCheckout($guest);

					if ($checkoutSuccess) {
						$result['checked_out_count']++;
						$result['checked_out_bookings'][] = array(
							'booking_number' => $guest->booking_number,
							'room_no' => $guest->room_no,
							'guest_name' => $guest->firstname . ' ' . $guest->lastname,
							'email' => $guest->email,
							'scheduled_checkout' => $guest->checkoutdate,
							'actual_checkout' => $currentDateTime
						);

						// Send thank you email
						$this->sendAutoCheckoutEmail($guest);
					} else {
						$result['errors'][] = "Failed to checkout booking: {$guest->booking_number}";
					}
				} catch (Exception $e) {
					$result['errors'][] = "Error checking out {$guest->booking_number}: " . $e->getMessage();
					log_message('error', 'Auto-checkout error for booking ' . $guest->booking_number . ': ' . $e->getMessage());
				}
			}

			// Log execution
			log_message('info', 'Auto-checkout completed: ' . $result['checked_out_count'] . ' guests checked out from ' . $result['total_checked'] . ' overdue bookings');

		} catch (Exception $e) {
			$result['success'] = false;
			$result['errors'][] = $e->getMessage();
			log_message('error', 'Auto-checkout system error: ' . $e->getMessage());
		}

		return $result;
	}

	/**
	 * Perform automatic checkout for a single guest
	 *
	 * @param object $guest Guest information
	 * @return bool Success status
	 */
	private function performAutoCheckout($guest)
	{
		try {
			// Start transaction
			$this->db->trans_start();

			// Update booking status to checked out (status = 5)
			$checkoutData = array(
				'bookingstatus' => 5
			);

			$this->db->where('bookedid', $guest->bookedid);
			$updateResult = $this->db->update('booked_info', $checkoutData);

			if (!$updateResult) {
				$this->db->trans_rollback();
				return false;
			}

			// Create posted bill record for the checkout
			$totalBill = $this->db->select('total_price, paid_amount')
				->from('booked_info')
				->where('bookedid', $guest->bookedid)
				->get()
				->row();

			if ($totalBill) {
				$postedBillData = array(
					'bookedid' => $guest->bookedid,
					'room_no' => $guest->room_no,
					'booking_number' => $guest->booking_number,
					'total_bill' => $totalBill->total_price,
					'paid_amount' => $totalBill->paid_amount,
					'checkoutdate' => date('Y-m-d H:i:s'),
					'note' => 'Auto-checkout - Scheduled checkout time reached'
				);

				// Only insert if not already exists
				$existingBill = $this->db->select('bookedid')
					->from('tbl_postedbills')
					->where('bookedid', $guest->bookedid)
					->get()
					->row();

				if (!$existingBill) {
					$this->db->insert('tbl_postedbills', $postedBillData);
				}
			}

			// Free up the rooms
			$roomNumbers = explode(',', $guest->room_no);
			foreach ($roomNumbers as $roomNo) {
				$roomNo = trim($roomNo);
				if (!empty($roomNo)) {
					$this->db->where('roomno', $roomNo);
					$this->db->update('tblroom', array('status' => 1)); // Status 1 = Available
				}
			}

			// Complete transaction
			$this->db->trans_complete();

			return $this->db->trans_status();

		} catch (Exception $e) {
			$this->db->trans_rollback();
			log_message('error', 'Auto-checkout failed for booking ' . $guest->booking_number . ': ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Send thank you email to guest after auto-checkout
	 *
	 * @param object $guest Guest information
	 */
	private function sendAutoCheckoutEmail($guest)
	{
		try {
			// Get hotel settings
			$hotel_settings = $this->db->select("*")->from("common_setting")->where("id", 1)->get()->row();
			$appName = $this->db->select("title")->from("setting")->where("id", 2)->get()->row();

			// Safely map hotel data
			$hotelName = (!empty($appName) && !empty($appName->title)) ? $appName->title : 'Our Hotel';
			$hotelEmail = (!empty($hotel_settings) && !empty($hotel_settings->email)) ? $hotel_settings->email : 'info@hotel.com';
			$hotelPhone = (!empty($hotel_settings) && !empty($hotel_settings->phone)) ? $hotel_settings->phone : 'Contact Number';
			$hotelAddress = (!empty($hotel_settings) && !empty($hotel_settings->address)) ? $hotel_settings->address : 'Hotel Address';

			$guestName = trim($guest->firstname . ' ' . $guest->lastname);
			$subject = "Thank You for Staying With Us - " . $guest->booking_number;

			// Create beautiful HTML email
			ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($subject); ?></title>
    <style>
        :root {
            --brand: #1f6f54;
            --brand-soft: #ecf8f3;
            --text: #1f2933;
            --muted: #6b7280;
            --border: #e4e9f2;
            --surface: #ffffff;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 24px;
            background: #f4f6fb;
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            line-height: 1.6;
        }
        .email-shell {
            max-width: 660px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 30px 70px rgba(15, 23, 42, 0.13);
        }
        .header {
            padding: 34px 34px 26px;
            background: linear-gradient(135deg, #0f3d2c 0%, #1f6f54 100%);
            color: #fff;
            text-align: center;
        }
        .header h1 {
            margin: 8px 0 0;
            font-size: 30px;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
        }
        .section {
            padding: 26px 34px;
            border-top: 1px solid var(--border);
        }
        .section:first-of-type {
            border-top: none;
        }
        .hello {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text);
        }
        .message {
            font-size: 15px;
            line-height: 1.7;
            color: var(--text);
            margin: 12px 0;
        }
        .highlight-box {
            background: var(--brand-soft);
            border-left: 4px solid var(--brand);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .highlight-box p {
            margin: 0;
            font-size: 15px;
            line-height: 1.7;
        }
        .highlight-box strong {
            color: var(--brand);
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .cta-button {
            display: inline-block;
            padding: 14px 28px;
            background: var(--brand);
            color: white !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            margin: 16px 0;
            transition: all 0.3s ease;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 16px 0;
        }
        .detail-item {
            padding: 14px;
            background: #f8f9fb;
            border-radius: 10px;
        }
        .detail-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
        }
        .footer {
            background: #f3f5f9;
            padding: 24px 34px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            border-top: 1px solid var(--border);
        }
        .footer strong {
            display: block;
            color: var(--text);
            margin-bottom: 8px;
            font-size: 15px;
        }
        .divider {
            height: 1px;
            background: var(--border);
            margin: 20px 0;
        }
        @media (max-width: 600px) {
            body {
                padding: 12px;
            }
            .section {
                padding: 22px;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="email-shell">
        <div class="header">
            <p><?= html_escape($hotelName); ?></p>
            <h1>🎉 Thank You for Staying With Us!</h1>
        </div>

        <div class="section">
            <p class="hello">Dear <?= html_escape($guestName); ?>,</p>
            <p class="message">
                Thank you for choosing <?= html_escape($hotelName); ?>. We hope you enjoyed your stay with us and that we met your expectations in every way.
            </p>
            <p class="message">
                Your checkout has been completed successfully, and we look forward to welcoming you back in the future.
            </p>
        </div>

        <div class="section">
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Booking Number</div>
                    <div class="detail-value">#<?= html_escape($guest->booking_number); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Room Number</div>
                    <div class="detail-value"><?= html_escape($guest->room_no); ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="highlight-box">
                <strong>💬 We Value Your Feedback</strong>
                <p>
                    We would be grateful if you could share your experience with us. Your feedback helps us improve our services and serve you better on your next visit.
                </p>
                <p style="margin-top: 12px;">
                    Please feel free to reply to this email with any observations, suggestions, or comments about your stay. We genuinely appreciate your input and would love to know how we can enhance your experience when you visit us again.
                </p>
            </div>
        </div>

        <div class="section">
            <p class="message">
                <strong>Looking forward to welcoming you again!</strong><br>
                Until then, safe travels and warm regards from all of us at <?= html_escape($hotelName); ?>.
            </p>
        </div>

        <div class="section" style="border-bottom: 1px solid var(--border); background: #fbfcfd;">
            <p style="margin: 0; font-size: 14px; text-align: center; color: var(--muted);">
                📞 Need assistance? We're here 24/7<br>
                <strong style="color: var(--text);"><?= html_escape($hotelPhone); ?></strong> •
                <strong style="color: var(--text);"><?= html_escape($hotelEmail); ?></strong>
            </p>
        </div>

        <div class="footer">
            <strong><?= html_escape($hotelName); ?></strong>
            <div><?= html_escape($hotelEmail); ?></div>
            <div><?= html_escape($hotelPhone); ?></div>
            <div><?= html_escape($hotelAddress); ?></div>
            <div style="margin-top: 12px; font-size: 12px;">
                © <?= date('Y'); ?> <?= html_escape($hotelName); ?>. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
<?php
			$htmlContent = ob_get_clean();

			// Send email
			$this->roomreservation_model->send_email(
				strtolower($guest->email),
				$subject,
				$hotelName,
				$htmlContent
			);

			log_message('info', 'Auto-checkout thank you email sent to ' . $guest->email . ' for booking ' . $guest->booking_number);

		} catch (Exception $e) {
			log_message('error', 'Failed to send auto-checkout email for booking ' . $guest->booking_number . ': ' . $e->getMessage());
		}
	}

	/**
	 * Cron job endpoint for automatic checkout
	 *
	 * This endpoint should be called by a cron job every hour (or as desired)
	 *
	 * Access URL: /room_reservation/auto-checkout-cron
	 *
	 * Optional GET parameters:
	 * - key: Security key for authentication (recommended)
	 *
	 * Example:
	 * /room_reservation/auto-checkout-cron?key=your-secret-key
	 *
	 * Returns JSON response with execution results
	 */
	public function autoCheckoutCron()
	{
		// Security key check
		$securityKey = $this->input->get('key', TRUE);
		$expectedKey = $this->config->item('auto_checkout_secret_key'); // Add to config/config.php

		// If key is configured, require it
		if (!empty($expectedKey) && $securityKey !== $expectedKey) {
			header('HTTP/1.1 403 Forbidden');
			header('Content-Type: application/json');
			echo json_encode(array(
				'success' => false,
				'error' => 'Unauthorized access - Invalid security key'
			));
			exit;
		}

		// Execute auto-checkout
		$result = $this->autoCheckoutOverdueGuests();

		// Return JSON response
		header('Content-Type: application/json');
		echo json_encode($result, JSON_PRETTY_PRINT);
		exit;
	}

	/**
	 * Manual trigger for auto-checkout (Admin only)
	 *
	 * This can be accessed by admin to manually trigger auto-checkout
	 *
	 * Access URL: /room_reservation/manual-auto-checkout
	 */
	public function manualAutoCheckout()
	{
		// Require admin authentication
		$this->permission->method('room_reservation', 'update')->redirect();

		// Execute auto-checkout
		$result = $this->autoCheckoutOverdueGuests();

		// Set flash message
		if ($result['success']) {
			$this->session->set_flashdata(
				'message',
				"Auto-checkout completed: {$result['checked_out_count']} guest(s) checked out from {$result['total_checked']} overdue booking(s)"
			);
		} else {
			$this->session->set_flashdata(
				'exception',
				"Auto-checkout encountered errors: " . implode(', ', $result['errors'])
			);
		}

		redirect('room_reservation/booking-list');
	}

	public function create($id = null)
	{
		$data['title'] = display('room_reservation');
		$this->form_validation->set_rules('guest', display('guest'), 'required|xss_clean');
		$this->form_validation->set_rules('room_name', display('room_name'), 'required|xss_clean');
		$this->form_validation->set_rules('no_of_people', display('no_of_people'), 'required|xss_clean');
		$this->form_validation->set_rules('check_in', display('check_in'), 'required|xss_clean');
		$this->form_validation->set_rules('check_out', display('check_out'), 'required|xss_clean');
		$saveid = $this->session->userdata('id');
		$this->input->post('discount', true);
		$data['intinfo'] = "";
		if ($this->form_validation->run()) {
			if (empty($this->input->post('bookedid', TRUE))) {
				$bookinginfo = $this->db->select("*")->from('booked_info')->order_by('bookedid', 'desc')->get()->row();
				if (!empty($bookinginfo)) {
					$bookno = $bookinginfo->bookedid;
				} else {
					$bookno = "00000000";
				}

				$nextno = $bookno + 1;
				$bk_length = strlen((int)$nextno);

				$bkstr = '00000000';
				$bknumber = substr($bkstr, $bk_length);
				$bookingnumber = $bknumber . $nextno;
				$length = count($this->input->post('slroomno', TRUE));
				$room = $this->input->post('slroomno', TRUE);
				$roomnosel = '';
				$custID = $this->input->post('guest', TRUE);
				for ($i = 0; $i < $length; $i++) {
					$roomnosel .= $room[$i] . ',';
				}
				$roomnosel = rtrim($roomnosel, ',');
				$postData = array(
					'bookedid'     	     	 => $this->input->post('bookedid', TRUE),
					'booking_number' 	     => $bookingnumber,
					'date_time' 	             => date('Y-m-d H:i:s'),
					'roomid' 	             => $this->input->post('room_name', TRUE),
					'nuofpeople'              => $this->input->post('no_of_people', TRUE),
					'total_room'              => $this->input->post('numofroom', TRUE),
					'room_no'              	 => $roomnosel,
					'roomrate'                => $this->input->post('roomrate', TRUE),
					'total_price'             => $this->input->post('gramount', TRUE),
					'offer_discount'          => $this->input->post('discount', TRUE),
					'coments'                 => '',
					'checkindate'             => $this->input->post('check_in', TRUE),
					'checkoutdate'            => $this->input->post('check_out', TRUE),
					'cutomerid' 	             => $this->input->post('guest', TRUE),
					'bookingstatus' 	         => 0

				);
				$this->permission->method('room_reservation', 'create')->redirect();
				if ($this->roomreservation_model->create($postData)) {
					$type = "processing";
					$response = $this->lsoft_setting->send_sms($bookingnumber, $custID, $type);
					$data = json_decode($response);
					$msg = $data->message;
					$this->session->set_flashdata('message', display('save_successfully'));
					if ($msg)
						$this->session->set_userdata('msg', $msg);
					redirect('room_reservation/room-booking');
				} else {
					$this->session->set_flashdata('exception',  display('please_try_again'));
				}
				redirect('room_reservation/room-booking');
			} else {
				$this->permission->method('room_reservation', 'update')->redirect();
				$roomnosel = $this->input->post('room_no', TRUE);
				$status = $this->input->post('status', TRUE);
				$bookingnumber = $this->input->post('bookingnumber', TRUE);
				$custID = $this->input->post('guest', TRUE);
				if (empty($roomnosel)) {
					$length = count($this->input->post('slroomno', TRUE));
					$room = $this->input->post('slroomno', TRUE);
					$roomnosel = '';
					for ($i = 0; $i < $length; $i++) {
						$roomnosel .= $room[$i] . ',';
					}
					$roomnosel = rtrim($roomnosel, ',');
				}
				$data['room_reservation']   = (object) $updateData = array(
					'room_no'              	 => $roomnosel,
					'bookedid'     	     	 => $this->input->post('bookedid', TRUE),
					'bookingstatus' 	         => $this->input->post('status', TRUE)
				);
				if ($this->roomreservation_model->update($updateData)) {
					if (ENVIRONMENT == "production") {
						$msg = "";
						if ($status == 4) {
							$type = "completeorder";
							$response = $this->lsoft_setting->send_sms($bookingnumber, $customerid, $type);
							$data = json_decode($response);
							$msg = $data->message;
						}
						if ($status == 1) {
							$type = "cancel";
							$response = $this->lsoft_setting->send_sms($bookingnumber, $customerid, $type);
							$data = json_decode($response);
							$msg = $data->message;
						}
						if ($msg)
							echo '<h5>Success</h5>' . $msg;
					}
					$this->session->set_flashdata('message', display('update_successfully'));
					if ($msg)
						$this->session->set_userdata('msg', $msg);
				} else {
					$this->session->set_flashdata('exception',  display('please_try_again'));
				}
				redirect("room_reservation/booking-list");
			}
		} else {
			if (!empty($id)) {
				$data['title'] = display('reservation_edit');
				$data['intinfo']   = $this->roomreservation_model->findById($id);
			}
			$data["roomlist"] = $this->roomreservation_model->allrooms();
			$data["customerlist"] = $this->roomreservation_model->customerlist();
			$data['module'] = "room_reservation";
			$data['page']   = "addbooking";
			echo Modules::run('template/layout', $data);
		}
	}
	public function updateintfrm($id)
	{

		$this->permission->method('room_reservation', 'update')->redirect();
		$data['title'] = display('bed_edit');
		$data["roomlist"] = $this->roomreservation_model->allrooms();
		$data["customerlist"] = $this->roomreservation_model->customerlist();
		$data['intinfo']   = $this->roomreservation_model->findById($id);

		$roomname = $data['intinfo']->roomid;
		$checkin = $data['intinfo']->checkindate;
		$checkout = $data['intinfo']->checkoutdate;
		$status = 1;
		$exits = $this->db->select("*")->from('booked_info')->where('checkindate<=', $checkin)->where('checkoutdate>', $checkin)->where('bookingstatus!=', $status)->where('roomid', $roomname)->get()->result();
		$exit = $this->db->select("*")->from('booked_info')->where('checkindate<', $checkout)->where('checkoutdate>=', $checkout)->where('bookingstatus!=', $status)->get()->result();
		$check = $this->db->select("*")->from('booked_info')->where('checkindate>', $checkin)->where('checkoutdate<=', $checkout)->where('bookingstatus!=', $status)->get()->result();
		$totalroom1 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate<=', $checkin)->where('checkoutdate>', $checkin)->where('bookingstatus!=', $status)->where('roomid', $roomname)->get()->row();
		$totalroom2 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate<', $checkout)->where('checkoutdate>=', $checkout)->where('bookingstatus!=', $status)->where('roomid', $roomname)->get()->row();
		$totalroom3 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate>=', $checkin)->where('checkoutdate<=', $checkout)->where('bookingstatus!=', $status)->where('roomid', $roomname)->group_by('checkindate')->get()->result();
		$allbokedroom3 = (!empty($allbokedroom3) ? max(array_column($totalroom3, 'allroom')) : 0);
		$totalroomfound = $this->db->select("count(roomid) as totalroom")->from('tbl_roomnofloorassign')->where('roomid', $roomname)->get()->row();
		$roomdetails = $this->db->select("*")->from('roomdetails')->where('roomid', $roomname)->get()->row();
		$numberlist = $this->db->select("*")->from('tbl_roomnofloorassign')->where('roomid', $roomname)->get()->result();
		$roomlist = '';
		foreach ($numberlist as $singleno) {
			$roomlist .= $singleno->roomno . ',';
		}
		$gtroomno = rtrim($roomlist, ',');
		if (empty($exits) && empty($exit) && empty($check)) {
			$data['freeroom'] = $gtroomno;
			$data['isfound'] = 0;
		} else {
			$bookedroom = "";
			if (!empty($exits)) {
				foreach ($exits as $booked) {
					$bookedroom .= $booked->room_no . ',';
				}
			}
			if (!empty($exit)) {
				foreach ($exit as $ex) {
					$bookedroom .= $ex->room_no . ',';
				}
			}
			if (!empty($check)) {
				foreach ($check as $ch) {
					$bookedroom .= $ch->room_no . ',';
				}
			}
			$getbookedall = rtrim($bookedroom, ',');
			$allbokedroom1 = $totalroom1->allroom;
			$allbokedroom2 = $totalroom2->allroom;
			$allbokedroom = max((int)$allbokedroom1, (int)$allbokedroom2, (int)$allbokedroom3);
			$allfreeroom = $totalroomfound->totalroom;
			if ($allfreeroom > $allbokedroom) {
				$output = $this->Differences($getbookedall, $gtroomno);
				if (!empty($output)) {
					$data['freeroom'] = $output;
					$data['isfound'] = '1';
				} else {
					$data['freeroom'] = '';
					$data['isfound'] = '2';
				}
			} else {
				$data['freeroom'] = '';
				$data['isfound'] = '2';
			}
		}

		$data['module'] = "room_reservation";
		$data['page']   = "reservationedit";
		$this->load->view('room_reservation/reservationedit', $data);
	}

	public function delete($id = null)
	{
		$this->permission->module('room_reservation', 'delete')->redirect();

		if ($this->roomreservation_model->delete($id)) {
			#set success message
			$this->session->set_flashdata('message', display('delete_successfully'));
		} else {
			#set exception message
			$this->session->set_flashdata('exception', display('please_try_again'));
		}
		redirect('room_reservation/booking-list');
	}
	public function checkroom()
	{
		$guest = $this->input->post('guest', true);
		$roomname = $this->input->post('room_name', true);
		$checkin = $this->input->post('check_in', true);
		$checkout = $this->input->post('check_out', true);
		$status = 1;
		$exits = $this->db->select("*")->from('booked_info')->where('checkindate<=', $checkin)->where('checkoutdate>', $checkin)->where('bookingstatus!=', $status)->where('roomid', $roomname)->get()->result();
		$exit = $this->db->select("*")->from('booked_info')->where('checkindate<', $checkout)->where('checkoutdate>=', $checkout)->where('bookingstatus!=', $status)->where('roomid', $roomname)->get()->result();
		$check = $this->db->select("*")->from('booked_info')->where('checkindate>', $checkin)->where('checkoutdate<=', $checkout)->where('bookingstatus!=', $status)->where('roomid', $roomname)->get()->result();
		$totalroom1 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate<=', $checkin)->where('checkoutdate>', $checkin)->where('bookingstatus!=', $status)->where('roomid', $roomname)->get()->row();
		$totalroom2 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate<', $checkout)->where('checkoutdate>=', $checkout)->where('bookingstatus!=', $status)->where('roomid', $roomname)->get()->row();
		$totalroom3 = $this->db->select("SUM(total_room) as allroom")->from('booked_info')->where('checkindate>=', $checkin)->where('checkoutdate<=', $checkout)->where('bookingstatus!=', $status)->where('roomid', $roomname)->group_by('checkindate')->get()->result();
		$allbokedroom3 = (!empty($allbokedroom3) ? max(array_column($totalroom3, 'allroom')) : 0);
		$totalroomfound = $this->db->select("count(roomid) as totalroom")->from('tbl_roomnofloorassign')->where('roomid', $roomname)->get()->row();
		$roomdetails = $this->db->select("*")->from('roomdetails')->where('roomid', $roomname)->get()->row();
		$numberlist = $this->db->select("*")->from('tbl_roomnofloorassign')->where('roomid', $roomname)->get()->result();
		$roomlist = '';
		foreach ($numberlist as $singleno) {
			$roomlist .= $singleno->roomno . ',';
		}
		$gtroomno = rtrim($roomlist, ',');
		if (empty($exits) && empty($exit) && empty($check)) {
			$data['freeroom'] = $gtroomno;
			$data['isfound'] = 0;
		} else {
			$bookedroom = "";
			if (!empty($exit)) {
				foreach ($exits as $booked) {
					$bookedroom .= $booked->room_no . ',';
				}
			}
			if (!empty($exit)) {
				foreach ($exit as $ex) {
					$bookedroom .= $ex->room_no . ',';
				}
			}
			if (!empty($check)) {
				foreach ($check as $ch) {
					$bookedroom .= $ch->room_no . ',';
				}
			}

			$getbookedall = rtrim($bookedroom, ',');
			$allbokedroom1 = $totalroom1->allroom;
			$allbokedroom2 = $totalroom2->allroom;
			$allbokedroom = max((int)$allbokedroom1, (int)$allbokedroom2, (int)$allbokedroom3);
			$allfreeroom = $totalroomfound->totalroom;
			if ($allfreeroom > $allbokedroom) {
				$output = $this->Differences($getbookedall, $gtroomno);
				if (!empty($output)) {
					$data['freeroom'] = $output;
					$data['isfound'] = '1';
				} else {
					$data['freeroom'] = '';
					$data['isfound'] = '2';
				}
			} else {
				$data['freeroom'] = '';
				$data['isfound'] = '2';
			}
		}
		$data['checkin'] = $checkin;
		$data['checkout'] = $checkout;
		$data['guest'] = $guest;
		$data['roomno'] = $roomname;
		$data['roominfo'] = $roomdetails;
		$data['chargeinfo'] = $this->roomreservation_model->chargeinfo();
		$data['module'] = "room_reservation";
		$data['page']   = "bookinginfo";
		$this->load->view('room_reservation/bookinginfo', $data);
	}

	public function Differences($Arg1, $Arg2)
	{
		$Arg1 = explode(',', $Arg1);
		$Arg2 = explode(',', $Arg2);

		$Difference_1 = array_diff($Arg1, $Arg2);
		$Difference_2 = array_diff($Arg2, $Arg1);
		$Diff = array_merge($Difference_1, $Difference_2);
		$Difference = implode(',', $Diff);
		return $Difference;
	}
	public function detailView($id)
	{
		$data["bookinginfo"] = $this->roomreservation_model->findBookingDetail($id);
		$data["paymentmethod"] = $this->roomreservation_model->paymentlist();
		$data["paymentlist"] = $this->roomreservation_model->findBypayId($id);
		$data["taxinfo"] = $this->roomreservation_model->taxinfo();
		$data["btaxinfo"] = $this->roomreservation_model->btaxinfo($id);
		$data["setting"] = $this->db->select("servicecharge")->from("setting")->get()->row();
		$data['module'] = "room_reservation";
		$data['page']   = "reservationdetail";
		echo Modules::run('template/layout', $data);
	}
	public function paymentsdatatable($id)
	{
	    
		$params = $columns = $totalRecords = $data = array();
		$params = $_REQUEST;
		$columns = array(
			0 => 'tbl_guestpayments.invoice',
			1 => 'bookingnumber',
			2 => 'paydate',
			3 => 'paymenttype',
			4 => 'paymentamount',
		);

		$where = $sqlTot = $sqlRec = "";
		// check search value exist
		if (!empty($params['search']['value'])) {
			$where .= " WHERE ";
			$where .= " ( tbl_guestpayments.invoice LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR tbl_guestpayments.bookingnumber LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR tbl_guestpayments.paydate LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR tbl_guestpayments.paymentamount LIKE '" . $params['search']['value'] . "%' ";
			$where .= " OR payment_method.payment_method LIKE '" . $params['search']['value'] . "%' )";
		}
		// getting total number records without any search
		$sql = "SELECT tbl_guestpayments.*,booked_info.bookedid,payment_method.payment_method FROM tbl_guestpayments Left Join booked_info ON booked_info.booking_number=tbl_guestpayments.bookedid Left Join payment_method ON payment_method.payment_method_id=tbl_guestpayments.paymenttype where booked_info.bookedid='$id'";


		$sqlTot .= $sql;
		$sqlRec .= $sql;
		//concatenate search sql if value exist
		if (isset($where) && ($where != '')) {
			$sqlTot .= $where;
			$sqlRec .= $where;
		}

		$sqlRec .=  " ORDER BY " . $columns[$params['order'][0]['column']] . "   " . $params['order'][0]['dir'] . " LIMIT " . $params['start'] . " ," . $params['length'] . " ";
		$SQLtotal = $this->db->query($sqlTot);
		$SQLoffer = $this->db->query($sqlRec);
		$totalRecords = $SQLtotal->num_rows();
		$queryRecords = $SQLoffer->result();
		$i = 0;
		foreach ($queryRecords as  $value) {
			$i++;
			$row = array();
			$update = '';
			$delete = '';
			if ($this->permission->method('room_reservation', 'update')->access()):
				$update = '<a onclick="editpayment(\'' . $value->payid . '\',\'' . $value->bookedid . '\',\'' . $value->bookedid . '\',\'' . $value->invoice . '\',\'' . $value->paydate . '\',\'' . $value->paymenttype . '\',\'' . $value->paymentamount . '\')" class="btn btn-info btn-sm margin_right_5px" data-toggle="tooltip" data-placement="top" data-original-title="Update" title="Update"><i class="ti-pencil-alt text-white" aria-hidden="true"></i></a>';
			endif;
			if ($this->permission->method('room_reservation', 'create')->access()):
				$Payment = '<a href="' . base_url() . 'room_reservation/payment-information/' . $value->bookedid . '" class="btn btn-success btn-sm" data-toggle="tooltip" data-placement="top" data-original-title="Payment" title="Payment"><i class="ti-wallet"></i></a>';
			endif;

			$row[] = $value->invoice;
			$row[] = $value->bookedid;
			$row[] = $value->paydate;
			$row[] = $value->payment_method;
			$row[] = $value->paymentamount;
			$row[] = $update;
			$data[] = $row;
		}

		$json_data = array(
			"draw"            => intval($params['draw']),
			"recordsTotal"    => intval($totalRecords),
			"recordsFiltered" => intval($totalRecords),
			"data"            => $data   // total data array
		);

		echo json_encode($json_data);
	}

	public function payments($id)
{
    $data['bookinginfo'] = $this->roomreservation_model->findById($id);
$checkin  = new DateTime($data['bookinginfo']->checkindate);
    $checkout = new DateTime($data['bookinginfo']->checkoutdate);

    // 2. Calculate Base Nights (Date to Date, ignoring hours for a moment)
    // We create "clean" dates without time to get the exact calendar day difference
    $start = new DateTime($checkin->format('Y-m-d'));
    $end   = new DateTime($checkout->format('Y-m-d'));
    $nights = $start->diff($end)->days;


    $tax_amount       = 0;
    $service_charge   = 0;
    $discount_amount  = 0;
    $nights           = $nights+1;


    /* ---------------- TAX CALCULATION ---------------- */
    $tax_calculation = $this->roomreservation_model
        ->calculateTax($data['bookinginfo']->total_price, true);

    $total_tax_rate = $tax_calculation['rate'];
    $tax_amount     = (float) $tax_calculation['total'];
	

    /* ---------------- SERVICE CHARGE ---------------- */
    $setting = $this->db
        ->select('servicecharge')
        ->from('setting')
        ->limit(1)
        ->get()
        ->row();

    if (!empty($setting) && !empty($setting->servicecharge)) {
        $service_charge = ($data['bookinginfo']->total_price * $setting->servicecharge) / 100;
    }

    /* ---------------- TOTAL PRICE ---------------- */
    $base_price = (float) $data['bookinginfo']->total_price;

    // FIXED: tax_amount is already a value, not a multiplier
    $data['bookinginfo']->total_price =
        ($base_price + $tax_amount + $service_charge )* $nights;

    /* ---------------- PAYMENTS ---------------- */
    $data['paymentmethod'] = $this->roomreservation_model->paymentlist();
    $data['paymentlist']   = $this->roomreservation_model->findBypayId($id);

    /* ---------------- INVOICE ---------------- */
    $data['invoice'] = $this->roomreservation_model->generateInvoiceNumber();

    $data['title']  = 'payments';
    $data['module'] = 'room_reservation';
    $data['page']   = 'payments';

    echo Modules::run('template/layout', $data);
}

	public function addpayment($bid)
	{
		$data['title'] = "Add Payment";
		$this->form_validation->set_rules('booking_number', display('booking_number'), 'required|xss_clean');
		$this->form_validation->set_rules('invoice_no', display('invoice_no'), 'required|xss_clean');
		$this->form_validation->set_rules('pay_date', display('pay_date'), 'required|xss_clean');
		$this->form_validation->set_rules('payment_method', display('payment_method'), 'required|xss_clean');
		$this->form_validation->set_rules('amount', display('amount'), 'required|xss_clean');
		$saveid = $this->session->userdata('id');
		$id = $this->input->post('payid', TRUE);
		$data['intinfo'] = "";
		// Find the acc COAID for the Transaction
		$thisbookinfo = $this->db->select('cutomerid')->from('booked_info')->where('bookedid', $bid)->get()->row();
		$customerid = $thisbookinfo->cutomerid;
		$cusifo = $this->db->select('*')->from('customerinfo')->where('customerid', $customerid)->get()->row();
		$headn = $cusifo->customernumber . '-' . $cusifo->firstname . ' ' . $cusifo->lastname;
		$coainfo = $this->db->select('*')->from('acc_coa')->where('HeadName', $headn)->get()->row();
		$customer_headcode = 102030101;
		$invoice_no = $this->input->post('invoice_no', TRUE);
		$newdate = date('Y-m-d');

		if ($this->form_validation->run()) {
			if (empty($this->input->post('payid', TRUE))) {
				$total_amount = $this->input->post('total_amount', TRUE);
				$paid_amount = $this->input->post('amount', TRUE);
				if ($total_amount - $paid_amount >= 0) {
					$this->db->set('paid_amount', 'paid_amount+' . $paid_amount, FALSE);
					$this->db->where('bookedid', $bid);
					$this->db->update('booked_info');

					$data['room_reservation']   = (object) $postData = array(
						'payid'     	     		 => $this->input->post('payid', TRUE),
						'bookedid' 	         => $this->input->post('booking_number', TRUE),
						'invoice' 	             => $this->input->post('invoice_no', TRUE),
						'paydate' 	             => $this->input->post('pay_date', TRUE),
						'paymenttype' 	         => $this->input->post('payment_method', TRUE),
						'paymentamount' 	         => $this->input->post('amount', TRUE),
					);
				} else {
					$this->session->set_flashdata('exception',  display('pay_exact_amount'));
					redirect("room_reservation/payment-information/" . $bid);
				}
				$this->permission->method('room_reservation', 'create')->redirect();
				if ($this->roomreservation_model->createpayment($postData)) {

					//Customer debit for Rent Value
					$invoice_no = $this->input->post('invoice_no', TRUE);
					$newdate = date('Y-m-d');
					$narration = 'Customer debit for Rent Invoice# ' . $invoice_no;
					$amount = $this->input->post('amount', TRUE);
					transaction($invoice_no, 'CIV', $newdate, $customer_headcode, $narration, $amount, 0, 0, 1, $saveid, $newdate, 1, $bid, $invoice_no);
					//Hotel Owner credit for Rent Value
					$narration = 'Hotel Credit for Rent Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 10107, $narration, 0, $amount, 0, 1, $saveid, $newdate, 1, $bid, $invoice_no);

					// Customer Credit for paid amount.
					$narration = 'Customer Credit for Rent Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, $customer_headcode, $narration, 0, $amount, 0, 1, $saveid, $newdate, 1, $bid, $invoice_no);

					//Cash In hand Debit for paid value
					$narration = 'Cash in hand Debit For Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 1020101, $narration, $amount, 0, 0, 1, $saveid, $newdate, 1, $bid, $invoice_no);

					$this->session->set_flashdata('message', display('save_successfully'));
					redirect('room_reservation/payment-information/' . $bid);
				} else {
					$this->session->set_flashdata('exception',  display('please_try_again'));
				}
				redirect("room_reservation/payment-information/" . $bid);
			} else {
				$this->permission->method('room_reservation', 'update')->redirect();
				$data['room_reservation']   = (object) $postData = array(
					'payid'     	     		 => $this->input->post('payid', TRUE),
					'bookingnumber' 	         => $this->input->post('booking_number', TRUE),
					'invoice' 	             => $this->input->post('invoice_no', TRUE),
					'paydate' 	             => $this->input->post('pay_date', TRUE),
					'paymenttype' 	         => $this->input->post('payment_method', TRUE),
					'paymentamount' 	         => $this->input->post('amount', TRUE),
				);

				if ($this->roomreservation_model->updatepayment($postData)) {
					$crtransac = $this->db->select('*')->from('acc_transaction')->where('COAID', $customer_headcode)->where('VNo', $invoice_no)->where('Credit>', 0)->get()->row();
					$detransac = $this->db->select('*')->from('acc_transaction')->where('COAID', $customer_headcode)->where('VNo', $invoice_no)->where('Debit>', 0)->get()->row();
					$storetransac = $this->db->select('*')->from('acc_transaction')->where('COAID', '10107')->where('VNo', $invoice_no)->get()->row();
					$cashtransac = $this->db->select('*')->from('acc_transaction')->where('COAID', '1020101')->where('VNo', $invoice_no)->get()->row();

					//Customer debit for Product Value
					$saveddate = date("Y-m-d");
					$cosdr = array(
						'Debit'          =>  $this->input->post('amount', TRUE),
						'CreateBy'       => $saveid,
						'UpdateDate'     => $saveddate,
					);
					$this->db->where('ID', $detransac->ID);
					$this->db->update('acc_transaction', $cosdr);
					//Store credit for Product Value
					$sc = array(
						'Credit'         =>  $this->input->post('amount', TRUE),
						'CreateBy'       => $saveid,
						'UpdateDate'     => $saveddate,
					);
					$this->db->where('ID', $storetransac->ID);
					$this->db->update('acc_transaction', $sc);

					// Customer Credit for paid amount.
					$cc = array(
						'Credit'         =>  $this->input->post('amount', TRUE),
						'CreateBy'       => $saveid,
						'UpdateDate'     => $saveddate
					);
					$this->db->where('ID', $crtransac->ID);
					$this->db->update('acc_transaction', $cc);

					//Cash In hand Debit for paid value
					$cdv = array(
						'Debit'          =>  $this->input->post('amount', TRUE),
						'CreateBy'       => $saveid,
						'UpdateDate'     => $saveddate
					);
					$this->db->where('ID', $cashtransac->ID);
					$this->db->update('acc_transaction', $cdv);

					$this->session->set_flashdata('message', display('update_successfully'));
				} else {
					$this->session->set_flashdata('exception',  display('please_try_again'));
				}
				redirect("room_reservation/payment-information/" . $bid);
			}
		} else {
			if (!empty($id)) {
				$data['title'] = display('bed_edit');
				$data['intinfo']   = $this->roomreservation_model->findById($id);
			}

			$data["bookinginfo"] = $this->roomreservation_model->findById($bid);
			$data["paymentmethod"] = $this->roomreservation_model->paymentlist();
			// Generate invoice number with race condition protection
			$data['invoice'] = $this->roomreservation_model->generateInvoiceNumber();
			$data['module'] = "room_reservation";
			$data['page']   = "payments";
			echo Modules::run('template/layout', $data);
		}
	}
	public function smpooldetails()
	{
		$pid = $this->input->post('pid', TRUE);
		$mpool = explode(",,", $pid ?? '');
		$cid = explode(",", $mpool[0] ?? '');
		$cinfo = $this->roomreservation_model->poolcastinfodata($cid[0]);
		for ($i = 0; $i < count($mpool); $i++) {
			$spool = explode(",", $mpool[$i]);
			for ($j = 0; $j < count($spool); $j++) {
				$pitemlist[$i][$j]    = $this->roomreservation_model->pitemlistdata($spool[$j]);
				$pitemrow[$i][$j]     = $this->roomreservation_model->pitemdatarow($spool[$j]);
			}
		}
		$data['poolcastinfo']  = $cinfo;
		$data['pitemlist']     = $pitemlist;
		$data['pitemdata']     = $pitemrow;
		$data['currency']    = getCurrency();
		$this->load->view('room_reservation/poolprintview', $data);
	}
	public function restaurantDetails()
	{
		$rid = $this->input->post('order_id', TRUE);
		$mrid = explode(",,", $rid ?? '');
		$cid = explode(",", $mrid[0] ?? '');
		$cinfo = $this->roomreservation_model->restaurantCust($cid[0]);
		for ($i = 0; $i < count($mrid); $i++) {
			$srid = explode(",", $mrid[$i]);
			for ($j = 0; $j < count($srid); $j++) {
				$ritems[$i][$j]     = $this->roomreservation_model->ritemdatasingle($srid[$j]);
			}
		}
		$data['rcustomer']  = $cinfo;
		$data['ritems']     = $ritems;
		$data['currency']    = getCurrency();
		$this->load->view('room_reservation/restaurantbillprint', $data);
	}
	public function hallDetails()
	{
		$rid = $this->input->post('hall_id', TRUE);
		$mrid = explode(",,", $rid ?? '');
		$cid = explode(",", $mrid[0] ?? '');
		$cinfo = $this->roomreservation_model->hallRoomCust($cid[0]);
		for ($i = 0; $i < count($mrid); $i++) {
			$srid = explode(",", $mrid[$i]);
			for ($j = 0; $j < count($srid); $j++) {
				$ritems[$i][$j]     = $this->roomreservation_model->hallDetailsList($srid[$j]);
			}
		}
		$data['rcustomer']  = $cinfo;
		$data['ritems']     = $ritems;
		$data['currency']    = getCurrency();
		$this->load->view('room_reservation/hallroombillprint', $data);
	}
	public function parkingDetails()
	{
		$rid = $this->input->post('parking_id', TRUE);
		$mrid = explode(",,", $rid ?? '');
		$cid = explode(",", $mrid[0] ?? '');
		$cinfo = $this->roomreservation_model->carParkingCust($cid[0]);
		for ($i = 0; $i < count($mrid); $i++) {
			$srid = explode(",", $mrid[$i]);
			for ($j = 0; $j < count($srid); $j++) {
				$ritems[$i][$j]     = $this->roomreservation_model->parkingDetailsList($srid[$j]);
			}
		}
		$data['rcustomer']  = $cinfo;
		$data['ritems']     = $ritems;
		$data['currency']    = getCurrency();
		$this->load->view('room_reservation/carparkingbillprint', $data);
	}
	public function viewdetailsprint($id, $pdf = null)
	{
		$details = $this->roomreservation_model->details($id);
		$data['bookinfo']   = $details;
		$data['customerinfo']   = $this->roomreservation_model->customerinfo($details->cutomerid);
		$data['paymentinfo']   = $this->roomreservation_model->paymentinfo($details->bookedid);
		$data['storeinfo'] = $this->roomreservation_model->storeinfo();
		$data['taxinfo'] = $this->roomreservation_model->taxinfo();
		$data['btaxinfo'] = $this->roomreservation_model->btaxinfo($id);
		$data['commominfo'] = $this->roomreservation_model->commoninfo();
		$data['currency'] = $this->roomreservation_model->currencysetting($data['storeinfo']->currency);
		$data['setting'] = $this->db->select("servicecharge")->from("setting")->get()->row();
		if (empty($pdf)) {
			$this->load->view('room_reservation/bookindetailsprint', $data);
		} else {
			$pdfhtml = $this->load->view('room_reservation/bookindetailsprintpdf', $data, true);
			return $pdfhtml;
		}
	}
	/**
	 * Save initial payment details to tbl_guestpayments when booking is created
	 * This ensures bank details are saved even if advance amount is 0
	 */
	public function save_initial_payment_details($bookedid, $paymentmode, $bankname, $cardno, $advanceamount = 0)
	{
		// Check if a payment record already exists for this booking
		$existing_payment = $this->db->select("payid, invoice, paymentamount")
			->from("tbl_guestpayments")
			->where("bookedid", $bookedid)
			->where("book_type", 0)
			->get()->row();

		$cardNumber = !empty($cardno) ? $cardno : '';
		$bankName = !empty($bankname) ? $bankname : '';
		$newdate = date("Y-m-d H:i:s");

		if (empty($existing_payment)) {
			// No existing record, create a new one
			// Always save with amount = 0 initially, so advance_payment() can update it later
			// This prevents duplicate records when advance payment is made
			$invoice_no = $this->roomreservation_model->generateInvoiceNumber();
			$postData = array(
				'bookedid' => $bookedid,
				'invoice' => $invoice_no,
				'paydate' => $newdate,
				'paymenttype' => $paymentmode,
				'paymentamount' => 0, // Always 0 initially, will be updated by advance_payment() if needed
				'details' => "Card/Account No: " . $cardNumber . " Bank Name: " . $bankName,
				'book_type' => 0,
			);
			$this->db->insert('tbl_guestpayments', $postData);
		} else {
			// Record exists but might need bank details update
			// Only update if bank details are provided and not already set
			if (!empty($cardNumber) || !empty($bankName)) {
				$updateData = array(
					'paymenttype' => $paymentmode,
					'details' => "Card/Account No: " . $cardNumber . " Bank Name: " . $bankName,
				);
				// Don't update amount here - let advance_payment() handle it
				$this->db->where('payid', $existing_payment->payid)
					->update('tbl_guestpayments', $updateData);
			}
		}
	}

	public function advance_payment($bookedid, $paymentmode, $advanceamount, $id)
	{
		$payment = $this->db->select("invoice, paymentamount")->from("tbl_guestpayments")->where("bookedid", $bookedid)->where("book_type", 0)->get()->row();
		$invoice = $this->db->select("invoice")->from("tbl_guestpayments")->where("bookedid", $bookedid)->where("book_type", 0)->where("paymentamount<>", $advanceamount)->get()->row();
		if ((!empty($invoice->invoice) | $id != 1 | empty($payment->invoice))) {
			if ($id == 1 & !empty($invoice->invoice)) {
				//Payment record - Only update records with amount = 0, otherwise create new record
				$cardNumber = $this->input->post('cardno', TRUE);
				$bankName = $this->input->post('bankname', TRUE);
				$newdate = date("Y-m-d H:i:s");
				$saveid = $this->session->userdata('id');

				// Check if there's a record with amount = 0 to update
				$zero_payment = $this->db->select("payid, invoice, paymentamount")
					->from("tbl_guestpayments")
					->where('bookedid', $bookedid)
					->where("book_type", 0)
					->where("paymentamount", 0)
					->get()->row();

				$postData = array(
					'paydate' 	             => $newdate,
					'paymenttype' 	         => $paymentmode,
					'paymentamount' 	     => $advanceamount,
					'details' 	     		 => "Advance in Card/Account No: " . $cardNumber . " Bank Name: " . $bankName,
				);

				if (!empty($zero_payment)) {
					// Update only the record with amount = 0
					$invoice_no = $zero_payment->invoice;
					$this->db->where('payid', $zero_payment->payid)->update("tbl_guestpayments", $postData);
				} else {
					// Create new record for this payment
					$invoice_no = $this->roomreservation_model->generateInvoiceNumber();
					$postData['bookedid'] = $bookedid;
					$postData['invoice'] = $invoice_no;
					$postData['book_type'] = 0;
					$this->db->insert("tbl_guestpayments", $postData);
				}

				$old_mode = $this->db->select("paymenttype")->from("tbl_guestpayments")->where('bookedid', $bookedid)->order_by('payid', 'DESC')->limit(1)->get()->row();
				$old_code = null; // Initialize to prevent undefined variable warning
				if (!empty($old_mode)) {
					if ($old_mode->paymenttype == "SSLCommerz") {
						$old_code = 102010302;
					} else if ($old_mode->paymenttype == "Cash Payment") {
						$old_code = 1020101;
					} else if ($old_mode->paymenttype == "Paypal") {
						$old_code = 102010301;
					} else if ($old_mode->paymenttype == "Card Payment") {
						$old_code = 102010304;
					} else {
						$path = 'application/modules/';
						$map  = directory_map($path);
						$HmvcMenu   = array();
						if (is_array($map) && sizeof($map) > 0)
							foreach ($map as $key => $value) {
								$env = str_replace("\\", '/', $path . $key . 'assets/data/env');
								$transaction = str_replace("\\", '/', $path . $key . 'controllers/transaction.php');
								if (file_exists($env)) {
									if (file_exists($transaction)) {
										@include($transaction);
										if ($old_mode->paymenttype == $paymentMethod) {
											$old_code = $headCode;
										}
									}
								}
							}
					}
				}
				//Payment method Debit for paid value
				$acc_id = $this->db->select("ID")->from("acc_transaction")->where('VNo', $invoice->invoice)->order_by("ID", "ASC")->get()->result();
			
				if ($paymentmode == "Bank Payment") {
					$query = $this->db->query("SELECT HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%' And HeadName LIKE '$bankName'");
					$row = $query->row();
					$headcode = (!empty($row->HeadCode) ? $row->HeadCode : null);
					if (empty($headcode)) {
						$coa = $this->roomreservation_model->headcode(4, 1020102);
						if ($coa->HeadCode != NULL) {
							$headcode = $coa->HeadCode + 1;
						} else {
							$headcode = "102010201";
						}
						//insert Coa for Customer Receivable
						$postData1['HeadCode']   	= $headcode;
						$postData1['HeadName']   	= $bankName;
						$postData1['PHeadName']   	= 'Cash At Bank';
						$postData1['HeadLevel']   	= '4';
						$postData1['IsActive']  	= '1';
						$postData1['IsTransaction'] = '1';
						$postData1['IsGL']   		= '0';
						$postData1['HeadType']  	= 'A';
						$postData1['IsBudget'] 		= '0';
						$postData1['IsDepreciation'] = '0';
						$postData1['DepreciationRate'] = '0';
						$postData1['CreateBy'] 		= $saveid;
						$postData1['CreateDate'] 	= $newdate;
						$this->db->insert('acc_coa', $postData1);
						//end
					}
					$narration = 'Cash in Bank Debited For advance payment ' . $bankName . ' Invoice# ' . $invoice->invoice;
					transaction_update($acc_id[0]->ID, $invoice->invoice, $newdate, $advanceamount, 0, $saveid, $newdate, $headcode, $narration, $old_code);
				} else if ($paymentmode == "SSLCommerz") {
					$narration = 'Cash in SSLCOMMERZ Debited For advance payment Invoice# ' . $invoice->invoice;
					transaction_update($acc_id[0]->ID, $invoice->invoice, $newdate, $advanceamount, 0, $saveid, $newdate, 102010302, $narration, $old_code);
				} else if ($paymentmode == "Cash Payment") {
					$narration = 'Cash in Hand Debited For advance payment Invoice# ' . $invoice->invoice;
					transaction_update($acc_id[0]->ID, $invoice->invoice, $newdate, $advanceamount, 0, $saveid, $newdate, 1020101, $narration, $old_code);
				} else if ($paymentmode == "Paypal") {
					$narration = 'Cash in Paypal Debited For advance payment Invoice# ' . $invoice->invoice;
					transaction_update($acc_id[0]->ID, $invoice->invoice, $newdate, $advanceamount, 0, $saveid, $newdate, 102010301, $narration, $old_code);
				} else if ($paymentmode == "Card Payment") {
					$narration = 'Cash in Card Debited For advance payment Invoice# ' . $invoice->invoice;
					transaction_update($acc_id[0]->ID, $invoice->invoice, $newdate, $advanceamount, 0, $saveid, $newdate, 102010304, $narration, $old_code);
				} else {
					$path = 'application/modules/';
					$map  = directory_map($path);
					$HmvcMenu   = array();
					if (is_array($map) && sizeof($map) > 0)
						foreach ($map as $key => $value) {
							$env = str_replace("\\", '/', $path . $key . 'assets/data/env');
							$transaction = str_replace("\\", '/', $path . $key . 'controllers/transaction.php');
							if (file_exists($env)) {
								if (file_exists($transaction)) {
									@include($transaction);
									if ($paymentmode == $paymentMethod) {
										$narration = 'Cash in ' . $paymentMethod . ' Debited For advance payment Invoice# ' . $invoice->invoice;
										transaction_update($acc_id[0]->ID, $invoice->invoice, $newdate, $advanceamount, 0, $saveid, $newdate, $headCode, $narration, $old_code);
									}
								}
							}
						}
					$query = $this->db->query("SELECT HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020103%' And HeadName LIKE '$pmethod'");
					$row = $query->row();
					$headcode = (!empty($row->HeadCode) ? $row->HeadCode : null);
				}
				//hotel service credited for advance rent
				transaction_update($acc_id[1]->ID, $invoice->invoice, $newdate, 0, $advanceamount, $saveid, $newdate, 30301);
				//Customer debited for advance room booking
				transaction_update($acc_id[2]->ID, $invoice->invoice, $newdate, $advanceamount, 0, $saveid, $newdate, 102030101);
				//Customer credited for advance payment
				transaction_update($acc_id[3]->ID, $invoice->invoice, $newdate, 0, $advanceamount, $saveid, $newdate, 102030101);
			} else {
				// Start database transaction for advance payment
				$this->db->trans_start();

				try {
					$cardNumber = $this->input->post('cardno', TRUE);
					$bankName = $this->input->post('bankname', TRUE);
					$newdate = date("Y-m-d H:i:s");
					$saveid = $this->session->userdata('id');

					// Check if a payment record with 0 amount exists (from initial save)
					// Only update records with amount = 0 to prevent replacing existing payments
					// If record has amount > 0, create a new record for this payment transaction
					$existing_zero_payment = $this->db->select("payid, invoice, paymentamount")
						->from("tbl_guestpayments")
						->where("bookedid", $bookedid)
						->where("book_type", 0)
						->where("paymentamount", 0)
						->get()->row();

					if (!empty($existing_zero_payment)) {
						// Update existing record with 0 amount (first payment)
						$invoice_no = $existing_zero_payment->invoice;
						$postData = array(
							'paydate' => $newdate,
							'paymenttype' => $paymentmode,
							'paymentamount' => $advanceamount,
							'details' => "Advance in Card/Account No: " . $cardNumber . " Bank Name: " . $bankName,
						);
						$this->db->where('payid', $existing_zero_payment->payid)
							->update('tbl_guestpayments', $postData);
					} else {
						// No record with 0 amount exists, create a new record for this payment
						// This handles: subsequent payments, or bookings created without initial save
						$invoice_no = $this->roomreservation_model->generateInvoiceNumber();
						$postData = array(
							'bookedid' => $bookedid,
							'invoice' => $invoice_no,
							'paydate' => $newdate,
							'paymenttype' => $paymentmode,
							'paymentamount' => $advanceamount,
							'details' => "Advance in Card/Account No: " . $cardNumber . " Bank Name: " . $bankName,
							'book_type' => 0,
						);
						$this->db->insert('tbl_guestpayments', $postData);
					}
				//Payment method Debit for paid value
				if ($paymentmode == "Bank Payment") {
					$query = $this->db->query("SELECT HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020102%' And HeadName LIKE '$bankName'");
					$row = $query->row();
					$headcode = (!empty($row->HeadCode) ? $row->HeadCode : null);
					if (empty($headcode)) {
						$coa = $this->roomreservation_model->headcode(4, 1020102);
						if ($coa->HeadCode != NULL) {
							$headcode = $coa->HeadCode + 1;
						} else {
							$headcode = "102010201";
						}
						//insert Coa for Customer Receivable
						$postData1['HeadCode']   	= $headcode;
						$postData1['HeadName']   	= $bankName;
						$postData1['PHeadName']   	= 'Cash At Bank';
						$postData1['HeadLevel']   	= '4';
						$postData1['IsActive']  	= '1';
						$postData1['IsTransaction'] = '1';
						$postData1['IsGL']   		= '0';
						$postData1['HeadType']  	= 'A';
						$postData1['IsBudget'] 		= '0';
						$postData1['IsDepreciation'] = '0';
						$postData1['DepreciationRate'] = '0';
						$postData1['CreateBy'] 		= $saveid;
						$postData1['CreateDate'] 	= $newdate;
						$this->db->insert('acc_coa', $postData1);
						//end
					}
					$narration = 'Cash in Bank Debited For advance payment ' . $bankName . ' Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, $headcode, $narration, $advanceamount, 0, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
				} else if ($paymentmode == "SSLCommerz") {
					$narration = 'Cash in SSLCOMMERZ Debited For advance payment Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 102010302, $narration, $advanceamount, 0, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
				} else if ($paymentmode == "Cash Payment") {
					$narration = 'Cash in Hand Debited For advance payment Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 1020101, $narration, $advanceamount, 0, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
				} else if ($paymentmode == "Paypal") {
					$narration = 'Cash in Paypal Debited For advance payment Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 102010301, $narration, $advanceamount, 0, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
				} else if ($paymentmode == "Card Payment") {
					$narration = 'Cash in Card Debited For advance payment Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 102010304, $narration, $advanceamount, 0, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
				} else if ($paymentmode == "Paystack") {
					$narration = 'Cash in Paystack Debited For advance payment Invoice# ' . $invoice_no;
					transaction($invoice_no, 'CIV', $newdate, 102010303, $narration, $advanceamount, 0, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
				} else {
					$path = 'application/modules/';
					$map  = directory_map($path);
					$HmvcMenu   = array();
					if (is_array($map) && sizeof($map) > 0)
						foreach ($map as $key => $value) {
							$env = str_replace("\\", '/', $path . $key . 'assets/data/env');
							$transaction = str_replace("\\", '/', $path . $key . 'controllers/transaction.php');
							if (file_exists($env)) {
								if (file_exists($transaction)) {
									@include($transaction);
									if ($paymentmode == $paymentMethod) {
										$narration = 'Cash in ' . $paymentMethod . ' Debited For advance payment Invoice# ' . $invoice_no;
										transaction($invoice_no, 'CIV', $newdate, $headCode, $narration, $advanceamount, 0, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
									}
								}
							}
						}
					$query = $this->db->query("SELECT HeadCode FROM acc_coa WHERE HeadLevel='4' And HeadCode LIKE '1020103%' And HeadName LIKE '$paymentmode'");
					$row = $query->row();
					$headcode = (!empty($row->HeadCode) ? $row->HeadCode : null);
				}
				//hotel service credited for advance rent
				$narration = 'Hotel credited for room advance rent Invoice# ' . $invoice_no;
				transaction($invoice_no, 'CIV', $newdate, 30301, $narration, 0, $advanceamount, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
				//Customer debited for advance room booking
				$narration = 'Hotel customer debited for advance room booking Invoice# ' . $invoice_no;
				transaction($invoice_no, 'CIV', $newdate, 102030101, $narration, $advanceamount, 0, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);
				//Customer credited for advance payment
				$narration = 'Hotel customer credited for advance room booking Invoice# ' . $invoice_no;
				transaction($invoice_no, 'CIV', $newdate, 102030101, $narration, 0, $advanceamount, 0, 1, $saveid, $newdate, 1, $bookedid, $invoice_no);

					// Complete transaction
					$this->db->trans_complete();

					if ($this->db->trans_status() === FALSE) {
						throw new Exception('Advance payment processing failed');
					}

				} catch (Exception $e) {
					$this->db->trans_rollback();
					log_message('error', 'Advance payment failed: ' . $e->getMessage());
					$this->session->set_flashdata('exception', 'Payment processing failed. Please try again.');
					redirect("room_reservation/advance-payment/" . $bookedid);
					return;
				}
			}
		}
	}
	public function room_status($id = null)
	{
		$this->permission->method('room_reservation', 'read')->redirect();
		//update as available if time ended
		$this->db->set('tbl_roomnofloorassign.status', 1);
		$this->db->where('booked_info.checkoutdate<', date("Y-m-d H:i:s"));
		$this->db->where('tbl_roomnofloorassign.status<>', 1);
		$this->db->update('tbl_roomnofloorassign JOIN booked_info ON FIND_IN_SET(tbl_roomnofloorassign.roomno,booked_info.room_no)<>0');
		//update as booked if time is not ended
		$this->db->set('tbl_roomnofloorassign.status', 2);
		$this->db->where('booked_info.checkindate<', date("Y-m-d H:i:s"));
		$this->db->where('booked_info.checkoutdate>', date("Y-m-d H:i:s"));
		$this->db->where('tbl_roomnofloorassign.status<>', 2);
		$this->db->where_in('booked_info.bookingstatus', array(0, 4));
		$this->db->update('tbl_roomnofloorassign JOIN booked_info ON FIND_IN_SET(tbl_roomnofloorassign.roomno,booked_info.room_no)<>0');
		$data['title']    = display('room_reservation');
		$hall_room = $this->db->where('directory', 'hall_room')->where('status', 1)->get('module')->num_rows();
		if ($hall_room == 1) {
			$data["roomlist"] = $this->db->select('*')->from('tbl_roomnofloorassign')->where('roomid<>', NULL)->order_by('roomno', 'ASC')->get()->result();
		} else {
			$data["roomlist"] = $this->db->select('*')->from('tbl_roomnofloorassign')->order_by('roomno', 'ASC')->get()->result();
		}
		$data["floordetails"] = $this->roomreservation_model->floorwithRoom();
		$data["problemList"] = $this->roomreservation_model->read2('*', 'tbl_note', 'note_id', array("status" => 0));
		$data["solvedList"] = $this->roomreservation_model->read2('*', 'tbl_note', 'note_id', array("status" => 1));
		$data['module'] = "room_reservation";
		$data['page']   = "roomlist";
		echo Modules::run('template/layout', $data);
	}
// 	public function roomlistDetail()
// 	{
// 		$bookedid = $this->input->post("bookedid", true);
// 		$dateTime = $this->input->post("datetime", true);
// 		$roomno = $this->input->post("roomno", true);
// 		// CRITICAL: Join booked_details to get discountamount for correct calculation
// 		$details = $this->db->select("b.*,c.*,rd.roomtype,bd.discountamount")->from("booked_info b")->join("customerinfo c", "c.customerid=b.cutomerid", "left")->join("roomdetails rd", "rd.roomid=b.roomid", "left")->join("booked_details bd", "bd.bookedid=b.bookedid", "left")->where("date(checkindate)<=", $dateTime)->where("date(checkoutdate)>=", $dateTime)->where("bookedid", $bookedid)->where_in("bookingstatus", array(0, 4))->get()->row();
// 		$note = $this->db->select("*")->from("tbl_note")->where("b.bookedid", $bookedid)->where("roomno", $roomno)->get()->result();
// 		if (!empty($note)) {
// 			$details->note = $note;
// 		}
// 		// Get taxes using centralized method
// 		$scharge = $this->db->select("servicecharge")->from("setting")->get()->row();
// 		$car_parking = $this->db->where('directory', 'car_parking')->where('status', 1)->get('module')->num_rows();
// 		if ($car_parking == 1) {
// 			$car_parking = $this->db->select("total_price")->from("tbl_bookParking")->where("bookedid", $details->bookedid)->get()->result();
// 		}
// 		$details->p_status = "No";
// 		$datediff = strtotime($details->checkoutdate) - strtotime($details->checkindate);
// 		$datediff = ceil($datediff / (60 * 60 * 24));
// 		$totalPrice = $details->total_price * $datediff;
// 		$totalTax = $this->roomreservation_model->calculateTax($totalPrice, false);
// 		$totalScharge = 0;
// 		$totalParking = 0;
// 		if ($scharge->servicecharge) {
// 			$totalScharge = ($totalPrice * $scharge->servicecharge) / 100;
// 		}
// 		if (!empty($car_parking)) {
// 			foreach ($car_parking as $cp) {
// 				$totalParking += $cp->total_price;
// 			}
// 			$details->p_status = "Yes";
// 		}
// 		// Get discount amount from booking
// 		$discountAmount = !empty($details->discountamount) ? $details->discountamount : 0;
// 		// Calculate subtotal (rent + tax + service charge + parking)
// 		$subtotal = $totalPrice + $totalTax + $totalScharge + $totalParking;
// 		// Apply discount
// 		$totalAfterDiscount = $subtotal - $discountAmount;
// 		// Calculate due amount (total after discount - paid amount)
// 		$details->due_amount = $totalAfterDiscount - $details->paid_amount;
// 		if ($details->due_amount < 0) {
// 			$details->due_amount = 0;
// 		}
// 		echo json_encode($details);
// 	}
public function roomlistDetail()
{
    $bookedid = $this->input->post("bookedid", true);
    $dateTime = $this->input->post("datetime", true);
    $roomno = $this->input->post("roomno", true);

    // MAIN QUERY (FIXED bookedid ambiguity)
    $details = $this->db->select("b.*,c.*,rd.roomtype,bd.discountamount")
        ->from("booked_info b")
        ->join("customerinfo c", "c.customerid=b.cutomerid", "left")
        ->join("roomdetails rd", "rd.roomid=b.roomid", "left")
        ->join("booked_details bd", "bd.bookedid=b.bookedid", "left")
        ->where("date(b.checkindate) <=", $dateTime)
        ->where("date(b.checkoutdate) >=", $dateTime)
        ->where("b.bookedid", $bookedid)
        ->where_in("b.bookingstatus", array(0, 4))
        ->get()
        ->row();

    // SAFETY CHECK (VERY IMPORTANT)
    if (empty($details)) {
        echo json_encode(["error" => "No booking found"]);
        return;
    }

    // NOTES (FIXED alias issue)
    $note = $this->db->select("*")
        ->from("tbl_note")
        ->where("bookedid", $bookedid)
        ->where("roomno", $roomno)
        ->get()
        ->result();

    if (!empty($note)) {
        $details->note = $note;
    }

    $scharge = $this->db->select("servicecharge")->from("setting")->get()->row();

    $car_parking = $this->db->where('directory', 'car_parking')
        ->where('status', 1)
        ->get('module')
        ->num_rows();

    $parkingData = [];

    if ($car_parking == 1) {
        $parkingData = $this->db->select("total_price")
            ->from("tbl_bookParking")
            ->where("bookedid", $details->bookedid)
            ->get()
            ->result();
    }

    $details->p_status = "No";

    $datediff = strtotime($details->checkoutdate) - strtotime($details->checkindate);
    $datediff = ceil($datediff / (60 * 60 * 24));

    $totalPrice = ($details->total_price ?? 0) * $datediff;

    $totalTax = $this->roomreservation_model->calculateTax($totalPrice, false);

    $totalScharge = 0;
    if (!empty($scharge->servicecharge)) {
        $totalScharge = ($totalPrice * $scharge->servicecharge) / 100;
    }

    $totalParking = 0;
    if (!empty($parkingData)) {
        foreach ($parkingData as $cp) {
            $totalParking += $cp->total_price;
        }
        $details->p_status = "Yes";
    }

    $discountAmount = !empty($details->discountamount) ? $details->discountamount : 0;

    $subtotal = $totalPrice + $totalTax + $totalScharge + $totalParking;

    $totalAfterDiscount = $subtotal - $discountAmount;

    $details->due_amount = $totalAfterDiscount - ($details->paid_amount ?? 0);

    if ($details->due_amount < 0) {
        $details->due_amount = 0;
    }

    echo json_encode($details);
}
	public function roomNoDetail()
	{
		$roomno = $this->input->post("roomno", true);
		$details = $this->db->select("rd.*")->from("roomdetails rd")->join("tbl_roomnofloorassign rfa", "rfa.roomid=rd.roomid", "left")->where("rfa.roomno", $roomno)->get()->row();
		echo json_encode($details);
	}
	public function searchResult($search = null, $key = null, $key1 = null, $key2 = null)
	{
		$data['title']    = display('room_reservation');
		$data['module'] = "room_reservation";
		if ($search != "null") {
			$hall_room = $this->db->where('directory', 'hall_room')->where('status', 1)->get('module')->num_rows();
			if ($hall_room == 1) {
				$data["roomlist"] = $this->db->select('*')->from('tbl_roomnofloorassign')->where('roomid<>', NULL)->order_by('roomno', 'ASC')->get()->result();
			} else {
				if ($key == "") {
					$data["roomlist"] = $this->db->select('*')->from('tbl_roomnofloorassign')->order_by('roomno', 'ASC')->get()->result();
				} else {
					$data["roomlist"] = $this->roomreservation_model->matchedRooms($search);
				}
			}
		} else if ($key != "null") {
			$data["roomlist"] = $this->roomreservation_model->matchedRooms(null, $key, $key1, $key2);
		} else if ($key1 != "null") {
			$data["roomlist"] = $this->roomreservation_model->matchedRooms(null, null, $key1, $key2);
		} else if ($key2 != "null") {
			$data["roomlist"] = $this->roomreservation_model->matchedRooms(null, null, null, $key2);
		}
		$this->load->view("room_reservation/roomlistSearch", $data);
	}
	public function saveNote()
	{
		$note = $this->input->post("note", true);
		$bookedid = $this->input->post("bookedid", true);
		$roomno = $this->input->post("roomno", true);
		$insert = array(
			'note' => $note,
			'roomno' => $roomno,
			'bookedid' => $bookedid,
			'status' => 0
		);
		$result = $this->db->insert("tbl_note", $insert);
		$note = $this->db->select("*")->from("tbl_note")->where("bookedid", $bookedid)->where("roomno", $roomno)->get()->result();
		if ($result) {
			$msg = '<h5>Success</h5>Saved Successfully';
			$data["note"] = $note;
			$data["msg"] = $msg;
			echo json_encode($data);
		} else {
			$msg = '<h5>Failed</h5>Please Try Again';
			$data["note"] = $note;
			$data["msg"] = $msg;
			echo json_encode($data);
		}
	}
	public function solveNote()
	{
		$id = $this->input->post("id", true);
		$bookedid = $this->input->post("bookedid", true);
		$roomno = $this->input->post("roomno", true);
		$update = array(
			'status' => 1
		);
		$id = trim($id, ",");
		$sl = explode(",", $id);
		for ($i = 0; $i < count($sl); $i++) {
			$result = $this->db->where("note_id", $sl[$i])->update("tbl_note", $update);
		}
		$note = $this->db->select("*")->from("tbl_note")->where("bookedid", $bookedid)->where("roomno", $roomno)->get()->result();
		if ($result) {
			$msg = '<h5>Success</h5>Saved Successfully';
			$data["note"] = $note;
			$data["msg"] = $msg;
			echo json_encode($data);
		} else {
			$msg = '<h5>Failed</h5>Please Try Again';
			$data["note"] = $note;
			$data["msg"] = $msg;
			echo json_encode($data);
		}
	}
	public function email_send($binfo = null, $check = null, $path = null)
	{
			
		$payment_data=[];
		$pay_ref='';
		if (!empty($binfo)) {
			// Get additional booking details
			
			$booking_details = $this->db->select("
				b.*,
				b.roomrate,
				c.*,
				rd.roomtype,
				bd.booking_type,
				bd.booking_source,
				bd.arival_from,
				bd.purpose,
				bd.discountamount,
				bd.discountreason,
				bd.extra_facility_days,
				bd.advance_amount,
				bd.payment_method,
				bd.remarks
			")
			->from("booked_info b")
			->join("customerinfo c", "c.customerid=b.cutomerid", "left")
			->join("roomdetails rd", "rd.roomid=b.roomid", "left")
			->join("booked_details bd", "bd.bookedid=b.bookedid", "left")
			->where("b.booking_number", $binfo->booking_number)
			->get()->row();
			

			// Get hotel settings
			$hotel_settings = $this->db->select("*")->from("common_setting")->where("id", 1)->get()->row();
			$appName = $this->db->select("title")->from("setting")->where("id", 2)->get()->row();

			// Safely map hotel meta data with fallbacks to avoid undefined property warnings
			$hotelEmail = (!empty($hotel_settings) && !empty($hotel_settings->email)) ? $hotel_settings->email : 'info@hotel.com';
			$hotelPhone = (!empty($hotel_settings) && !empty($hotel_settings->phone)) ? $hotel_settings->phone : 'Contact Number';
			$hotelAddress = (!empty($hotel_settings) && !empty($hotel_settings->address)) ? $hotel_settings->address : 'Hotel Address';
			$hotelBankName = (!empty($hotel_settings) && !empty($hotel_settings->bank_name)) ? $hotel_settings->bank_name : 'Zenith Bank';
			$hotelAccountNumber = (!empty($hotel_settings) && !empty($hotel_settings->account_number)) ? $hotel_settings->account_number : '0033088864';
			$hotelAccountName = (!empty($hotel_settings) && !empty($hotel_settings->account_name)) ? $hotel_settings->account_name : null;
		

			if(!empty($binfo->paystack)){
				
		$pay_ref =$this->paystack_reference_model->generate_ref($appName->title ?? 'HotelApp', $booking_details->bookedid);
		log_message('error', 'Failed to retrieve booking info for ID: ' . $pay_ref);
		

			}
		// Check if payment method is "Bank Payment" (string) or 6 (numeric) and get the customer's bank details
		$displayBankName = $hotelBankName;
		$displayAccountNumber = $hotelAccountNumber;
		$displayAccountName = $hotelAccountName;


		// Check if payment method is "Bank Payment" (string) or 6 (numeric ID)
		if (!empty($booking_details->payment_method) && ($booking_details->payment_method == 'Bank Payment' || $booking_details->payment_method == 6 || $booking_details->payment_method == '6')) {
			// Retrieve bank details from payment information
			$payment_details = $this->db->select("details, paymenttype")
				->from("tbl_guestpayments")
				->where("bookedid", $booking_details->bookedid)
				->where("book_type", 0)
				->order_by("payid", "DESC")
				->limit(1)
				->get()->row();

			if (!empty($payment_details) && !empty($payment_details->details)) {
				// Extract account number and bank name from details field
				// Format: "Card/Account No: [number] Bank Name: [name]" or "Advance in Card/Account No: [number] Bank Name: [name]"
				$detailsText = $payment_details->details;

				// Extract account number - matches "Card/Account No: [account] Bank Name:"
				// The account number is everything between "Card/Account No: " and " Bank Name:"
				$accountMatches = array();
				if (preg_match('/Card\/Account No:\s*(.+?)\s+Bank Name:/i', $detailsText, $accountMatches)) {
					$customerAccountNumber = trim($accountMatches[1]);
				} else {
					$customerAccountNumber = null;
				}

				// Extract bank name - capture everything after "Bank Name:" until end of string
				$bankMatches = array();
				if (preg_match('/Bank Name:\s*(.+)$/i', $detailsText, $bankMatches)) {
					$customerBankName = trim($bankMatches[1]);
				} else {
					$customerBankName = null;
				}

			
				// Use the extracted values if available
				if (!empty($customerBankName)) {
					$displayBankName = $customerBankName;

					// Use account number from payment details if available, otherwise keep hotel default
					if (!empty($customerAccountNumber)) {
						$displayAccountNumber = $customerAccountNumber;
						//log_message('debug', 'Email Send - Using extracted bank details from booking form: ' . $displayBankName . ' / ' . $displayAccountNumber);
					} else {
						//log_message('debug', 'Email Send - Account number not found in payment details, using hotel default');
					}
				} else {
					//log_message('debug', 'Email Send - Bank name not found in payment details, using hotel default');
				}
			} else {
				//log_message('debug', 'Email Send - No payment details found');
			}
		} else {
			//log_message('debug', 'Email Send - Using default hotel bank');
		}

			// Get tax and service charge settings (using centralized method)
			$taxsetting = $this->roomreservation_model->getActiveTaxRates();
			$setting = $this->db->select("servicecharge")->from("setting")->get()->row();
			$currency = getCurrency();

			if ($check == 4) {
				$subject = "Check-in Successful - " . $booking_details->booking_number;
				$email_title = "Check-in Confirmed";
				$greeting_message = "We are pleased to confirm your successful check-in to our hotel.";
			} elseif ($check == 5) {
				$subject = "Check-out Successful - " . $booking_details->booking_number;
				$email_title = "Check-out Completed";
				$greeting_message = "Thank you for staying with us. Your check-out has been completed successfully.";
			} else {
				$subject = "Reservation Confirmed - " . $booking_details->booking_number;
				$email_title = "Reservation Confirmed";
				$greeting_message = "Thank you for choosing " . ($appName->title ?: 'our hotel') . ". We are pleased to confirm your reservation with us in room number " . $booking_details->room_no . ".";
			}

			// Calculate accurate payment status and dates for detailed templates
			// CRITICAL FIX: Initialize variables for all cases to prevent undefined variable errors
			$payment_status = 'Pending';
			$remaining_amount = 0;
			$advance_amount = 0;
			$final_total = 0;
			$subtotal = 0;
			$base_rent = 0;
			$tax_amount = 0;
			$service_charge = 0;
			$discount_amount = 0;
			$nights = 1;
			$checkin_formatted = '';
			$checkout_formatted = '';
			$total_tax_rate = 0;
			$checkin_date  = new DateTime($booking_details->checkindate);
$checkout_date = new DateTime($booking_details->checkoutdate);

// Step 3: Calculate the difference
$interval = $checkin_date->diff($checkout_date);

// Step 4: Get the total days and ensure it's at least 1
$nights = $interval->days;
if ($nights <= 0) {
    $nights = 1; // Handle same-day bookings or errors
}

			if ($check != 5) {
				$checkin = new DateTime($booking_details->checkindate);
				$checkout = new DateTime($booking_details->checkoutdate);

				$checkin_formatted = $checkin->format('jS F Y H:i');
				$checkout_formatted = $checkout->format('jS F Y H:i');

				// Calculate billing details step by step
				// Step 1: Base room rent calculation
				$room_rate = $booking_details->roomrate; // Base room rate per night from booked_info
				$base_rent = $room_rate * $nights; // Total rent for all nights

				// Step 2: Calculate VAT/Tax on base rent (using centralized method)
				$tax_calculation = $this->roomreservation_model->calculateTax($base_rent, true);
				$total_tax_rate = $tax_calculation['rate'];
				$tax_amount = $tax_calculation['total'];
			

				// Step 3: Calculate Service Charge on base rent
				$service_charge = 0;
				if (!empty($setting->servicecharge)) {
					$service_charge = ($base_rent * $setting->servicecharge) / 100;
				}

				// Step 4: Apply discount if any (ONLY to base rent, not to subtotal)
				$discount_amount = $booking_details->discountamount ?: 0;
			
				// Calculate discount for all nights
				$discount_amount = $discount_amount;
				// Calculate discount percentage based on base rent, not subtotal
				$discount_percentage = $discount_amount > 0 && $base_rent > 0 ? ($discount_amount / $base_rent) * 100 : 0;

				// Step 5: Apply discount to base rent only
				$base_rent_after_discount = $base_rent - $discount_amount;
				// Ensure base rent after discount is not negative
				$base_rent_after_discount = max(0, $base_rent_after_discount);

				// Step 6: Calculate final total = (base rent - discount) + tax + service charge
				$final_total = $base_rent_after_discount + $tax_amount + $service_charge;
			

				// Ensure final total is not negative
				$final_total = max(0, $final_total);

				// For display: subtotal (base rent + tax + service charge, before discount)
				$subtotal = $base_rent + $tax_amount + $service_charge;

				// Booking charge is the service charge for display purposes
				$booking_charge = $service_charge;
				$base_rent_after_discount;
				

				// Calculate payment status
				$advance_amount = $booking_details->advance_amount ?: 0;
				$remaining_amount = max(0, $final_total - $advance_amount); // Ensure non-negative
			

				if ($advance_amount >= $final_total) {
					$payment_status = 'Fully Paid';
					$remaining_amount = 0;
				} elseif ($advance_amount > 0) {
					$payment_status = 'Partial Payment';
				} else {
					$payment_status = 'Unpaid';
				}
			}

			$currencySymbol = !empty($currency->curr_icon) ? $currency->curr_icon : 'NGN';
			$formatMoney = function ($amount) use ($currencySymbol) {
				return trim($currencySymbol) . ' ' . number_format((float)$amount, 2);
			};

			$guestName = ucwords(trim($booking_details->firstname . ' ' . $booking_details->lastname));
			$hotelName = $appName->title ?: 'Hotel';
			$roomType = $booking_details->roomtype ?: 'Standard Room';
			$roomNo = $booking_details->room_no ?: 'N/A';
			$reservationStatus = $check == 4 ? 'Checked In' : ($check == 5 ? 'Checked Out' : 'Booked');

			if ($check == 5) {
				if (empty($checkin_formatted)) {
					$checkin_formatted = (new DateTime($booking_details->checkindate))->format('jS F Y H:i');
				}
				if (empty($checkout_formatted)) {
					$checkout_formatted = (new DateTime($booking_details->checkoutdate))->format('jS F Y H:i');
				}
			}
				//$data=['email'=>'uctn2@gmail.com', 'amount'=>300, 'reference'=>$pay_ref];

			if(!empty($pay_ref) && !empty($booking_details->email)){
    $payment_data = [
        'email' => $booking_details->email,
        'reference' => $pay_ref,
        'amount' => intval($remaining_amount * 100) // convert to kobo
    ];
    


    $payment_link = $this->generate_link($payment_data);
    if(isset($payment_link['data'])){
        $payment_link = $payment_link['data'];
    }else{
        echo($payment_link["message"]);
        exit;
    }
   ;

}

			

			switch ($payment_status) {
				case 'Fully Paid':
					$paymentStatusColor = '#1b4332';
					break;
				case 'Partial Payment':
					$paymentStatusColor = '#b7791f';
					break;
				default:
					$paymentStatusColor = '#c53030';
					break;
			}

			if ($check == 5) {
				ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($email_title); ?></title>
    <style>
        :root {
            --brand: #1f6f54;
            --brand-dark: #0f3d2c;
            --text: #1f2933;
            --muted: #6b7280;
            --surface: #ffffff;
            --soft: #f4f7fb;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 24px;
            background: var(--soft);
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            line-height: 1.6;
        }
        .email-shell {
            max-width: 640px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }
        .header {
            background: var(--brand-dark);
            color: #fff;
            padding: 32px;
        }
        .header h1 {
            margin: 8px 0 0;
            font-size: 28px;
            line-height: 1.3;
        }
        .header p {
            margin: 0;
            opacity: 0.8;
            font-size: 14px;
        }
        .section {
            padding: 28px 32px;
            border-top: 1px solid #edf0f5;
        }
        .section:first-of-type {
            border-top: none;
        }
        .hello {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .summary-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 0 32px 32px;
        }
        .summary-card {
            background: #f7faf9;
            border-radius: 14px;
            flex: 1 1 180px;
            padding: 16px;
        }
        .summary-card span {
            display: block;
        }
        .label {
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
        }
        .cta-box {
            background: #f0fcf7;
            border: 1px solid rgba(15, 61, 44, 0.06);
            border-radius: 16px;
            padding: 20px;
        }
        .footer {
            background: #f0f4f8;
            padding: 24px 32px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }
        .footer strong {
            display: block;
            color: var(--text);
            margin-bottom: 4px;
        }
        @media (max-width: 600px) {
            body {
                padding: 12px;
            }
            .section,
            .summary-grid {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-shell">
        <div class="header">
            <p><?= html_escape($hotelName); ?></p>
            <h1><?= html_escape($email_title); ?></h1>
        </div>

        <div class="section">
            <p class="hello">Hi <?= html_escape($guestName); ?>,</p>
            <p><?= html_escape($greeting_message); ?></p>
            <p>We loved having you with us and hope every moment felt effortless. If there is anything else you need, just reply to this email and we will help right away.</p>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <span class="label">Reservation</span>
                <span class="value">#<?= html_escape($booking_details->booking_number); ?></span>
            </div>
            <div class="summary-card">
                <span class="label">Room</span>
                <span class="value"><?= html_escape($roomType); ?> · <?= html_escape($roomNo); ?></span>
            </div>
            <div class="summary-card">
                <span class="label">Stay</span>
                <span class="value"><?= html_escape($checkin_formatted); ?> – <?= html_escape($checkout_formatted); ?></span>
            </div>
        </div>

        <div class="section cta-box">
            <p style="margin-bottom: 8px;">Need an invoice copy or have feedback for us?</p>
            <p style="margin: 0;">Reply to this email or call <?= html_escape($hotelPhone); ?> and our guest experience team will take it from there.</p>
        </div>

        <div class="footer">
            <strong><?= html_escape($hotelName); ?></strong>
            <div><?= html_escape($hotelEmail); ?></div>
            <div><?= html_escape($hotelPhone); ?></div>
            <div><?= html_escape($hotelAddress); ?></div>
        </div>
    </div>
</body>
</html>
<?php
				$htmlContent = ob_get_clean();
			} else {
				ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($email_title); ?></title>
    <style>
        :root {
            --brand: #1f6f54;
            --brand-soft: #ecf8f3;
            --text: #1f2933;
            --muted: #6b7280;
            --border: #e4e9f2;
            --surface: #ffffff;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 24px;
            background: #f4f6fb;
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            line-height: 1.6;
        }
        .email-shell {
            max-width: 660px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 30px 70px rgba(15, 23, 42, 0.13);
        }
        .header {
            padding: 34px 34px 26px;
            background: #0f3d2c;
            color: #fff;
        }
        .header h1 {
            margin: 8px 0 0;
            font-size: 30px;
        }
        .section {
            padding: 26px 34px;
            border-top: 1px solid var(--border);
        }
        .section:first-of-type {
            border-top: none;
        }
        .hello {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .summary-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .summary-card {
            flex: 1 1 180px;
            background: var(--brand-soft);
            border-radius: 14px;
            padding: 14px 16px;
        }
        .summary-card span {
            display: block;
        }
        .summary-card .label {
            font-size: 12px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .summary-card .value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(31, 111, 84, 0.1);
            color: var(--brand);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 10px 0;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        td.amount {
            text-align: right;
            font-weight: 600;
            color: var(--text);
        }
        .totals td {
            font-size: 15px;
            font-weight: 600;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            background: rgba(31, 111, 84, 0.08);
            color: var(--text);
        }
        .note {
            margin-top: 18px;
            padding: 18px;
            background: #fbf9ef;
            border-radius: 14px;
            border: 1px solid rgba(247, 213, 92, 0.4);
            font-size: 14px;
        }
        .footer {
            background: #f3f5f9;
            padding: 24px 34px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }
        .footer strong {
            display: block;
            color: var(--text);
            margin-bottom: 4px;
        }
        @media (max-width: 600px) {
            body {
                padding: 12px;
            }
            .section {
                padding: 22px;
            }
            .summary-grid {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="email-shell">
        <div class="header">
            <p><?= html_escape($hotelName); ?></p>
            <h1><?= html_escape($email_title); ?></h1>
        </div>

        <div class="section">
            <p class="hello">Dear <?= html_escape($guestName); ?>,</p>
            <p><?= html_escape($greeting_message); ?></p>
            <p style="margin-top: 8px;">We are getting everything ready for a seamless stay. Below is a quick snapshot of your reservation.</p>
        </div>

        <div class="section">
            <div class="summary-grid">
                <div class="summary-card">
                    <span class="label">Reservation</span>
                    <span class="value">#<?= html_escape($booking_details->booking_number); ?></span>
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
            <h3 style="margin: 0 0 12px;">Stay details</h3>
            <table>
                <tbody>
                    <tr>
                        <td>Guest</td>
                        <td class="amount"><?= html_escape($guestName); ?></td>
                    </tr>
                    <tr>
                        <td>Room</td>
                        <td class="amount"><?= html_escape($roomType); ?> · <?= html_escape($roomNo); ?></td>
                    </tr>
                    <tr>
                        <td>Check-in</td>
                        <td class="amount"><?= html_escape($checkin_formatted); ?></td>
                    </tr>
                    <tr>
                        <td>Check-out</td>
                        <td class="amount"><?= html_escape($checkout_formatted); ?></td>
                    </tr>
                    <tr>
                        <td>Nights</td>
                        <td class="amount"><?= (int)$nights; ?></td>
                    </tr>
                    <tr>
                        <td>Guests</td>
                        <td class="amount"><?= (int)$booking_details->nuofpeople; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3 style="margin: 0 0 12px;">Investment summary</h3>
            <table>
                <tbody>
                    <tr>
                        <td>Room rent</td>
                        <td class="amount"><?= $formatMoney($base_rent); ?></td>
                    </tr>
                    <tr>
                        <td>VAT (<?= number_format($total_tax_rate, 1); ?>%)</td>
                        <td class="amount"><?= $formatMoney($tax_amount); ?></td>
                    </tr>
                    <tr>
                        <td>Service charge</td>
                        <td class="amount"><?= $formatMoney($service_charge); ?></td>
                    </tr>
					<?php if ($discount_amount > 0): ?>
                    <tr>
                        <td>Discount <?= !empty($booking_details->discountreason) ? '(' . html_escape($booking_details->discountreason) . ')' : ''; ?></td>
                        <td class="amount">-<?= $formatMoney($discount_amount); ?></td>
                    </tr>
					<?php endif; ?>
                    <tr class="totals">
                        <td>Total payable</td>
                        <td class="amount"><?= $formatMoney($final_total); ?></td>
                    </tr>
                    <tr>
                        <td>Advance paid</td>
                        <td class="amount"><?= $advance_amount > 0 ? $formatMoney($advance_amount) : 'NIL'; ?></td>
                    </tr>
					<?php if ($remaining_amount > 0): ?>
                    <tr>
                        <td>Balance due</td>
                        <td class="amount"><?= $formatMoney($remaining_amount); ?></td>
                    </tr>
					<?php else: ?>
                    <tr>
                        <td>Balance due</td>
                        <td class="amount">Paid in full</td>
                    </tr>
					<?php endif; ?>
                </tbody>
            </table>
        </div>

		<?php if ($check != 4): ?>
        <div class="section">
            <h3 style="margin: 0 0 10px;">How to complete payment</h3>
			<i>Kindly make payment to secure the apartment<i>
			
			<?php if(!empty($pay_ref)){?>

			<div class="pill">
				<p>Click the url below to pay</p>
            <?php if(!empty($payment_link['authorization_url'])): ?>
<a href='<?= htmlspecialchars($payment_link['authorization_url']); ?>' target='_blank'>
    Pay Now
</a>
<?php endif; ?>

            </div>



			<?php
			}else{
			?>
           
            <div class="pill">
                Bank: <?= html_escape($displayBankName); ?> ·
                Account: <?= html_escape($displayAccountNumber); ?> ·
                Name: <?= html_escape($displayAccountName ?: $hotelName); ?>
            </div>
        </div>
		<?php
		}?>
		<?php endif; ?>

		<?php if (!empty($booking_details->special_request)): ?>
        <div class="section">
            <h3 style="margin: 0 0 10px;">Special request</h3>
            <div class="note">
                <?= nl2br(html_escape($booking_details->special_request)); ?>
            </div>
        </div>
		<?php endif; ?>

        <div class="note" style="background: #f0fcf7; border-color: rgba(31,111,84,0.2);">
                <div class="section" style="border-bottom: 1px solid var(--border);">
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
</body>
</html>
<?php
				$htmlContent = ob_get_clean();
			}

			if (!empty($booking_details->email) && filter_var($booking_details->email, FILTER_VALIDATE_EMAIL)) {
    $this->roomreservation_model->send_email(
        strtolower($booking_details->email),
        $subject,
        $appName->title ?? 'Hotel',
        $htmlContent,
        $path
    );
} else {
    log_message('error', 'Invalid or missing email for booking: ' . $booking_details->booking_number);
}

		}
	}

	/**
	 * Validate payment amounts against total payable
	 *
	 * @param string $paymentamount Comma-separated payment amounts
	 * @param float $payableamt Total amount payable
	 * @return array Validation result with 'valid' (bool) and 'message' (string)
	 */
	private function validatePaymentAmounts($paymentamount, $payableamt) {
		$singleamount = explode(",", $paymentamount);
		$totalPaymentAmount = 0;

		// Sum all payment amounts
		foreach ($singleamount as $amount) {
			// Check for negative amounts
			if ($amount < 0) {
				return array(
					'valid' => false,
					'message' => 'Payment amounts cannot be negative'
				);
			}
			$totalPaymentAmount += (float)$amount;
		}

		// Allow small rounding differences (0.01)
		if (abs($totalPaymentAmount - $payableamt) > 0.01) {
			return array(
				'valid' => false,
				'message' => 'Payment amounts (' . number_format($totalPaymentAmount, 2) . ') do not match total payable (' . number_format($payableamt, 2) . ')'
			);
		}

		// Check for zero payment amount
		if ($totalPaymentAmount <= 0) {
			return array(
				'valid' => false,
				'message' => 'Payment amount must be greater than zero'
			);
		}

		return array(
			'valid' => true,
			'message' => 'Payment amounts validated successfully'
		);
	}

	/**
	 * Test email sending function
	 * Access via: /room_reservation/test_email
	 * Optional parameters:
	 *   - email: Email address to send test email to (default: uses sender email from config)
	 *   - debug: Set to 1 to see detailed debug information
	 *
	 * Example: /room_reservation/test_email?email=test@example.com&debug=1
	 */
	public function test_email()
	{
		// Get optional parameters
		$test_email = $this->input->get('email', true);
		$debug = $this->input->get('debug', true);

		// Call the test function from model
		$result = $this->roomreservation_model->test_send_email($test_email, ($debug == '1'));

		// If debug mode, show detailed information
		if($debug == '1') {
			header('Content-Type: application/json');
			echo json_encode($result, JSON_PRETTY_PRINT);
			return;
		}

		// Simple HTML output
		$data['title'] = 'Email Test';
		$data['result'] = $result;
		$data['test_email'] = $test_email;

		echo "<!DOCTYPE html>
		<html>
		<head>
			<title>Email Test Result</title>
			<style>
				body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
				.success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
				.error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
				.info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
				pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
			</style>
		</head>
		<body>
			<h1>Email Test Result</h1>";

		if($result === true) {
			echo "<div class='success'><strong>✓ Success!</strong> Test email sent successfully.";
			if($test_email) {
				echo " Email sent to: " . htmlspecialchars($test_email);
			} else {
				echo " Email sent to configured sender address.";
			}
			echo "</div>";
		} else {
			echo "<div class='error'><strong>✗ Failed!</strong> Email sending failed. Check the error details below.</div>";
			if(is_array($result) && isset($result['debug_info'])) {
				echo "<div class='info'><strong>Debug Information:</strong></div>";
				echo "<pre>" . htmlspecialchars($result['debug_info']) . "</pre>";
			}
		}

		echo "<div class='info'>
			<p><strong>Usage:</strong></p>
			<ul>
				<li>Simple test: <a href='?'>test_email</a></li>
				<li>Test to specific email: <a href='?email=your@email.com'>test_email?email=your@email.com</a></li>
				<li>Debug mode: <a href='?debug=1'>test_email?debug=1</a></li>
				<li>Both: <a href='?email=your@email.com&debug=1'>test_email?email=your@email.com&debug=1</a></li>
			</ul>
		</div>";

		echo "</body></html>";
	}

	  private function generate_link($data)
{
    // Paystack API URL
    $url = "https://api.paystack.co/transaction/initialize";

    // Prepare fields
    $fields = [
        'email' => $data['email'],
        'amount' => ceil($data['amount']),
        'reference' => $data['reference'],
    ];

    // Get Paystack secret key from config or env
    $this->config->load('paystack', TRUE);
    $secretKey = $this->config->item('paystack_secret', 'paystack');
    if (!$secretKey) {
        $secretKey = getenv('PAYSTACK_SECRET'); // fallback
    }

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
        'Cache-Control: no-cache',
    ]);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Handle errors
    if ($response === false) {
        return [
            'status' => false,
            'message' => 'cURL Error: ' . $error,
        ];
    }

    $result = json_decode($response, true);

    if ($httpCode == 200 && isset($result['status']) && $result['status'] === true) {
        return $result; // Successful Paystack response
    }

    return [
        'status' => false,
        'message' => isset($result['message']) ? $result['message'] : 'Failed to initiate payment link. Please try again.',
    ];
}

public function deletebooking($bookingid)
{
   

$res =	$this->permission->module('room_reservation', 'delete')->redirect();


	
    if (!$bookingid||!$res) {
        $this->session->set_flashdata('exception', 'Invalid booking ID');
        redirect($_SERVER['HTTP_REFERER']);
        return;
    }

    $this->db->trans_begin();

    try {

        // get booking info
        $booking = $this->db
            ->select("bookedid, room_no, cutomerid, paid_amount")
            ->from("booked_info")
            ->where("bookedid", $bookingid)
            ->get()
            ->row();

        if (!$booking) {
            throw new Exception('Booking not found');
        }

        $customerid = $booking->cutomerid;
        $paidAmount = $booking->paid_amount;

        /*
        ---------------------------------
        1. Restore room availability
        ---------------------------------
        */

        if (!empty($booking->room_no)) {

            $rooms = explode(",", $booking->room_no);

            foreach ($rooms as $room) {

                $this->db
                    ->where("roomno", $room)
                    ->update("tbl_roomnofloorassign", ['status' => 1]);

            }
        }

        /*
        ---------------------------------
        2. Reverse customer balance
        ---------------------------------
        */

        if ($paidAmount > 0) {

            $customer = $this->db
                ->select("balance")
                ->from("customerinfo")
                ->where("customerid", $customerid)
                ->get()
                ->row();

            if ($customer) {

                $newBalance = $customer->balance - $paidAmount;

                if ($newBalance < 0) {
                    $newBalance = 0;
                }

                $this->db
                    ->where("customerid", $customerid)
                    ->update("customerinfo", [
                        'balance' => $newBalance
                    ]);
            }
        }

        /*
        ---------------------------------
        3. Delete accounting transactions
        ---------------------------------
        */

        $this->db
            ->where("VNo", $bookingid)
            ->delete("acc_transaction");

        /*
        ---------------------------------
        4. Delete guest payments
        ---------------------------------
        */

        $this->db
            ->where("bookedid", $bookingid)
            ->delete("tbl_guestpayments");

        /*
        ---------------------------------
        5. Delete other guests
        ---------------------------------
        */

        $this->db
            ->where("bookedid", $bookingid)
            ->delete("tbl_otherguest");

        /*
        ---------------------------------
        6. Delete booking details
        ---------------------------------
        */

        $this->db
            ->where("bookedid", $bookingid)
            ->delete("booked_details");

        /*
        ---------------------------------
        7. Delete booking record
        ---------------------------------
        */

        $this->db
            ->where("bookedid", $bookingid)
            ->delete("booked_info");


        /*
        ---------------------------------
        Commit Transaction
        ---------------------------------
        */

        if ($this->db->trans_status() === FALSE) {

            throw new Exception('Delete failed');

        }

        $this->db->trans_commit();

        $this->session->set_flashdata('message', 'Booking Deleted Successfully');

    } catch (Exception $e) {

        $this->db->trans_rollback();

        log_message('error', 'Booking delete failed: ' . $e->getMessage());

        $this->session->set_flashdata('exception', 'Booking deletion failed');

    }

   redirect(base_url('room_reservation/booking-list'));
}
	
}
