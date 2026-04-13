<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Lsoft_setting
{
    //sms config update form for sending sms
    public function sms_configuration_form(){
        $CI =& get_instance();
        $CI->load->model('dashboard/Sms_model','sms_model');
        $setting_detail = $CI->sms_model->retrieve_sms_editdata();
        return $setting_detail;
    }

	//send sms using 80kobosms gateway
    public function send_sms($order_no=null,$customer_id=null,$type=null,$bookedid=null){

        $CI =& get_instance();
        $CI->load->model('dashboard/Sms_model','sms_model');

        // Get active gateway configuration
        $gateway = $CI->sms_model->retrieve_active_getway();

        // Check if gateway is configured
        if(!$gateway) {
            return json_encode(array(
                'status'      => false,
                'message'     => 'No SMS gateway configured'
            ));
        }

        // Get SMS template
        $sms_template = $CI->db->select('*')->from('sms_template')->where('type',$type)->get()->row();
        if(!$sms_template) {
            return json_encode(array(
                'status'      => false,
                'message'     => 'SMS template not found'
            ));
        }

        // Get customer phone number
        $customer_info = $CI->db->select('cust_phone')->from('customerinfo')->where('customerid',$customer_id)->get()->row();
        if(!$customer_info || empty($customer_info->cust_phone)) {
            return json_encode(array(
                'status'      => false,
                'message'     => 'Customer phone number not found'
            ));
        }

        $recipients = $customer_info->cust_phone;

        // Prepare message with booking details for reservation-related SMS types
        $sms_type = strtolower($sms_template->type);
        $message = $sms_template->message;

        // Handle reservation-related SMS types (booking, checkin, checkout)
        if($sms_type == "completeorder" || $sms_type == "processing" || $sms_type == "cancel" || $sms_type == "checkout"){
            // Fetch booking details if bookedid is provided or find by booking number
            $booking_data = null;
            if($bookedid) {
                $booking_data = $CI->db->select("b.*, c.firstname, c.lastname, bd.discountamount")
                    ->from("booked_info b")
                    ->join("customerinfo c", "c.customerid=b.cutomerid", "left")
                    ->join("booked_details bd", "bd.bookedid=b.bookedid", "left")
                    ->where("b.bookedid", $bookedid)
                    ->get()
                    ->row();
            } elseif($order_no) {
                $booking_data = $CI->db->select("b.*, c.firstname, c.lastname, bd.discountamount")
                    ->from("booked_info b")
                    ->join("customerinfo c", "c.customerid=b.cutomerid", "left")
                    ->join("booked_details bd", "bd.bookedid=b.bookedid", "left")
                    ->where("b.booking_number", $order_no)
                    ->get()
                    ->row();
            }

            // Replace placeholders with booking details
            if($booking_data) {
                // Format dates
                $checkin_date = !empty($booking_data->checkindate) ? date('d/m/Y', strtotime($booking_data->checkindate)) : '';
                $checkout_date = !empty($booking_data->checkoutdate) ? date('d/m/Y', strtotime($booking_data->checkoutdate)) : '';
                $checkin_datetime = !empty($booking_data->checkindate) ? date('d/m/Y H:i', strtotime($booking_data->checkindate)) : '';
                $checkout_datetime = !empty($booking_data->checkoutdate) ? date('d/m/Y H:i', strtotime($booking_data->checkoutdate)) : '';

                // Calculate number of nights
                $nights = 0;
                if(!empty($booking_data->checkindate) && !empty($booking_data->checkoutdate)) {
                    $checkin_timestamp = strtotime($booking_data->checkindate);
                    $checkout_timestamp = strtotime($booking_data->checkoutdate);
                    if($checkin_timestamp && $checkout_timestamp) {
                        $nights = floor(($checkout_timestamp - $checkin_timestamp) / (60 * 60 * 24));
                        if($nights == 0) $nights = 1; // Minimum 1 night
                    }
                }

                // Calculate total price including VAT, service charge, discount, and rent
                $base_rent = !empty($booking_data->total_price) ? floatval($booking_data->total_price) : 0;
                $total_rent = $base_rent * $nights; // Base rent for all nights

                // Calculate tax/VAT on base rent
                $total_tax = 0;
                $taxes = $CI->db->select("rate")->from("tbl_taxmgt")->where("isactive", 1)->get()->result();
                if(!empty($taxes) && $total_rent > 0) {
                    foreach($taxes as $tax) {
                        $total_tax += ($total_rent * floatval($tax->rate)) / 100;
                    }
                }

                // Calculate service charge on base rent
                $service_charge = 0;
                $scharge_setting = $CI->db->select("servicecharge")->from("setting")->get()->row();
                if(!empty($scharge_setting->servicecharge) && $total_rent > 0) {
                    $service_charge = ($total_rent * floatval($scharge_setting->servicecharge)) / 100;
                }

                // Get discount amount
                $discount_amount = !empty($booking_data->discountamount) ? floatval($booking_data->discountamount) * $nights : 0;

                // Calculate subtotal (rent + tax + service charge)
                $subtotal = $total_rent + $total_tax + $service_charge;

                // Apply discount to get final total
                $total_price = $subtotal - $discount_amount;
                if($total_price < 0) $total_price = 0;

                // For checkout, check if there are additional charges in postedbills
                if($sms_type == "checkout" && !empty($booking_data->bookedid)) {
                    $posted_bill = $CI->db->select("swimming_pool, restaurant, hallroom, car_parking, complementary, extrabpc, additional_charges, ex_discount, special_discount")
                        ->from("tbl_postedbills")
                        ->where("bookedid", $booking_data->bookedid)
                        ->get()
                        ->row();

                    if($posted_bill) {
                        // Add additional charges
                        $total_price += floatval($posted_bill->swimming_pool ?? 0);
                        $total_price += floatval($posted_bill->restaurant ?? 0);
                        $total_price += floatval($posted_bill->hallroom ?? 0);
                        $total_price += floatval($posted_bill->car_parking ?? 0);
                        $total_price += floatval($posted_bill->complementary ?? 0);
                        $total_price += floatval($posted_bill->extrabpc ?? 0);
                        $total_price += floatval($posted_bill->additional_charges ?? 0);

                        // Subtract additional discounts
                        $total_price -= floatval($posted_bill->ex_discount ?? 0);
                        $total_price -= floatval($posted_bill->special_discount ?? 0);

                        if($total_price < 0) $total_price = 0;
                    }
                }

                // Format amounts
                $total_price = number_format($total_price, 2);
                $paid_amount = !empty($booking_data->paid_amount) ? number_format($booking_data->paid_amount, 2) : '0.00';

                // Guest name
                $guest_name = trim(($booking_data->firstname ?? '') . ' ' . ($booking_data->lastname ?? ''));
                if(empty($guest_name) && !empty($booking_data->full_guest_name)) {
                    $guest_name = $booking_data->full_guest_name;
                }

                // Number of guests
                $num_guests = !empty($booking_data->nuofpeople) ? $booking_data->nuofpeople : '0';
                $num_children = !empty($booking_data->children) ? $booking_data->children : '0';

                // Room number(s)
                $room_no = !empty($booking_data->room_no) ? $booking_data->room_no : '';

                // Replace all placeholders
                $message = str_replace('{id}', $booking_data->booking_number, $message);
                $message = str_replace('{booking_number}', $booking_data->booking_number, $message);
                $message = str_replace('{room_no}', $room_no, $message);
                $message = str_replace('{checkin_date}', $checkin_date, $message);
                $message = str_replace('{checkout_date}', $checkout_date, $message);
                $message = str_replace('{checkin_datetime}', $checkin_datetime, $message);
                $message = str_replace('{checkout_datetime}', $checkout_datetime, $message);
                $message = str_replace('{total_price}', $total_price, $message);
                $message = str_replace('{paid_amount}', $paid_amount, $message);
                $message = str_replace('{guest_name}', $guest_name, $message);
                $message = str_replace('{num_guests}', $num_guests, $message);
                $message = str_replace('{num_children}', $num_children, $message);
                $message = str_replace('{nights}', $nights, $message);
            } else {
                // Fallback: just replace {id} with order_no if booking data not found
                $message = str_replace('{id}', $order_no, $message);
                $message = str_replace('{booking_number}', $order_no, $message);
            }
        }

        /****************************
        * 80kobosms Gateway Setup
        * API Documentation: https://www.80kobosms.com/developers
        ****************************/

        // Prepare 80kobosms API request data
        $email = $gateway->user_name;  // Email field (stored in user_name)
        $password = $gateway->password;
        $sender_name = !empty($gateway->sms_from) ? $gateway->sms_from : '80koboSMS';
        $forcednd = !empty($gateway->userid) ? $gateway->userid : '0'; // Force DND (stored in userid field)

        // Prepare JSON payload
        $data = array(
            "email"       => $email,
            "password"    => $password,
            "message"     => $message,
            "sender_name" => $sender_name,
            "recipients"  => $recipients,
            "forcednd"    => $forcednd
        );

        $data_string = json_encode($data);

        // Initialize cURL
        $ch = curl_init('https://api.80kobosms.com/v2/app/sms');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));

        // Execute request
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check for cURL errors
        if(curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return json_encode(array(
                'status'  => false,
                'message' => 'SMS sending failed: ' . $error
            ));
        }

        curl_close($ch);

        // Parse response
        $response = json_decode($result, true);

        // Handle response
        if($http_code == 200 && $response) {
            // 80kobosms returns status:1 for success (not "success" string)
            if(isset($response['status']) && ($response['status'] == 'success' || $response['status'] == 1 || $response['status'] === true)) {
                return json_encode(array(
                    'status'  => true,
                    'message' => 'SMS sent successfully via 80kobosms'
                ));
            } else {
                // API returned error message - check multiple fields
                $error_message = 'Unknown error occurred';
                if (isset($response['message'])) {
                    $error_message = $response['message'];
                } elseif (isset($response['msg'])) {
                    $error_message = $response['msg'];
                } elseif (isset($response['error'])) {
                    $error_message = $response['error'];
                }

                return json_encode(array(
                    'status'  => false,
                    'message' => 'SMS failed: ' . $error_message
                ));
            }
        } else {
            // HTTP error or invalid response
            return json_encode(array(
                'status'  => false,
                'message' => 'SMS API error. HTTP Code: ' . $http_code . '. Response: ' . $result
            ));
        }
    }

}

