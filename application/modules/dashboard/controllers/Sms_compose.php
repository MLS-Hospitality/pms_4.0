<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sms_compose extends MX_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->library('lsoft_setting');
        $this->load->model(array(
            'sms_model',
            'sms_compose_model'
        ));
        
        if (! $this->session->userdata('isAdmin'))
            redirect('login');
    }
    
    // Main compose SMS view
    public function index() {
        $data['title'] = 'Compose SMS';
        $data['module'] = "dashboard";
        $data['page'] = "sms/compose";
        $data['customers'] = $this->sms_compose_model->get_all_customers();
        $data['quick_templates'] = $this->sms_compose_model->get_quick_templates();
        $data['customer_count'] = $this->sms_compose_model->get_customer_count();
        $data['birthday_count'] = $this->sms_compose_model->get_birthday_count();
        $data['anniversary_count'] = $this->sms_compose_model->get_anniversary_count();
        
        echo Modules::run('template/layout', $data);
    }
    
    // Get customers by type (AJAX)
    public function get_customers_by_type() {
        $type = $this->input->post('type', TRUE);
        $customers = array();
        
        switch($type) {
            case 'all':
                $customers = $this->sms_compose_model->get_all_customers();
                break;
            case 'birthday':
                $customers = $this->sms_compose_model->get_birthday_customers();
                break;
            case 'anniversary':
                $customers = $this->sms_compose_model->get_anniversary_customers();
                break;
        }
        
        if ($customers) {
            $result = array();
            foreach ($customers as $customer) {
                $result[] = array(
                    'customerid' => $customer->customerid,
                    'name' => $customer->firstname . ' ' . $customer->lastname,
                    'phone' => $customer->cust_phone
                );
            }
            echo json_encode(array('success' => true, 'customers' => $result));
        } else {
            echo json_encode(array('success' => false, 'message' => 'No customers found'));
        }
    }
    
    // Send SMS
    public function send_sms() {
        $recipient_type = $this->input->post('recipient_type', TRUE);
        $message = $this->input->post('message', TRUE);
        $customer_ids = $this->input->post('customer_ids', TRUE);
        
        // Validation
        if (empty($message)) {
            echo json_encode(array('success' => false, 'message' => 'Message cannot be empty'));
            return;
        }
        
        // Get gateway configuration
        $gateway = $this->sms_model->retrieve_active_getway();
        if (!$gateway) {
            echo json_encode(array('success' => false, 'message' => 'No active SMS gateway configured'));
            return;
        }
        
        // Get customers based on recipient type
        $customers = array();
        switch($recipient_type) {
            case 'all':
                $customers = $this->sms_compose_model->get_all_customers();
                break;
            case 'birthday':
                $customers = $this->sms_compose_model->get_birthday_customers();
                break;
            case 'anniversary':
                $customers = $this->sms_compose_model->get_anniversary_customers();
                break;
            case 'selected':
                if (!empty($customer_ids)) {
                    $ids = explode(',', $customer_ids);
                    $customers = $this->sms_compose_model->get_customers_by_ids($ids);
                }
                break;
        }
        
        if (!$customers) {
            echo json_encode(array('success' => false, 'message' => 'No recipients found'));
            return;
        }
        
        // Send SMS to each customer
        $success_count = 0;
        $failed_count = 0;
        $recipient_list = array();
        $error_details = array();
        $last_api_response = '';
        
        foreach ($customers as $customer) {
            // Replace placeholders in message
            $personalized_message = $this->replace_placeholders($message, $customer);
            
            // Send SMS using 80kobosms
            $result = $this->send_single_sms($customer->cust_phone, $personalized_message, $gateway);
            
            if (is_array($result) && $result['success']) {
                $success_count++;
                if (isset($result['response'])) {
                    $last_api_response = json_encode($result['response']);
                }
            } else {
                $failed_count++;
                $error_msg = is_array($result) ? $result['error'] : 'Unknown error';
                $error_details[] = $customer->firstname . ' ' . $customer->lastname . ': ' . $error_msg;
                
                if (is_array($result) && isset($result['response'])) {
                    $last_api_response = is_array($result['response']) ? json_encode($result['response']) : $result['response'];
                }
            }
            
            $recipient_list[] = $customer->customerid;
        }
        
        // Prepare detailed gateway response
        $gateway_response = "Success: $success_count, Failed: $failed_count";
        if (!empty($error_details)) {
            $gateway_response .= " | Errors: " . implode('; ', array_slice($error_details, 0, 3)); // First 3 errors
        }
        if (!empty($last_api_response)) {
            $gateway_response .= " | API Response: " . substr($last_api_response, 0, 200);
        }
        
        // Save to history
        $history_data = array(
            'message' => $message,
            'recipient_type' => $recipient_type,
            'recipients' => implode(',', $recipient_list),
            'recipient_count' => count($customers),
            'sent_by' => $this->session->userdata('id'),
            'sent_date' => date('Y-m-d H:i:s'),
            'status' => ($failed_count == 0) ? 'sent' : (($success_count == 0) ? 'failed' : 'partial'),
            'gateway_response' => $gateway_response,
            'sender_name' => $gateway->sms_from
        );
        
        $this->sms_compose_model->save_sms_history($history_data);
        
        // Return detailed response
        $response_data = array(
            'success' => ($success_count > 0),
            'message' => "SMS sent successfully to $success_count recipient(s). Failed: $failed_count",
            'success_count' => $success_count,
            'failed_count' => $failed_count,
            'total_count' => count($customers)
        );
        
        // Add error details if any failures
        if ($failed_count > 0) {
            $response_data['errors'] = $error_details;
            $response_data['api_response'] = $last_api_response;
        }
        
        echo json_encode($response_data);
    }
    
    // Send single SMS using 80kobosms (with debugging)
    private function send_single_sms($phone, $message, $gateway) {
        // Prepare 80kobosms API request data
        $email = $gateway->user_name;
        $password = $gateway->password;
        $sender_name = !empty($gateway->sms_from) ? $gateway->sms_from : '80koboSMS';
        $forcednd = !empty($gateway->userid) ? $gateway->userid : '0';
        
        // Prepare JSON payload
        $data = array(
            "email"       => $email,
            "password"    => $password,
            "message"     => $message,
            "sender_name" => $sender_name,
            "recipients"  => $phone,
            "forcednd"    => $forcednd
        );
        
        $data_string = json_encode($data);
        
        // Log request (without sensitive data in production)
        log_message('debug', '80kobosms Request - Phone: ' . $phone . ', Sender: ' . $sender_name);
        
        // Initialize cURL
        $ch = curl_init('https://api.80kobosms.com/v2/app/sms');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));
        
        // Execute request
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        
        curl_close($ch);
        
        // Log full response for debugging
        log_message('debug', '80kobosms Response - HTTP Code: ' . $http_code);
        log_message('debug', '80kobosms Response - Body: ' . $result);
        
        if($curl_errno) {
            log_message('error', '80kobosms cURL Error (' . $curl_errno . '): ' . $curl_error);
            return array('success' => false, 'error' => 'Connection error: ' . $curl_error);
        }
        
        // Parse response
        $response = json_decode($result, true);
        
        if($http_code == 200) {
            // 80kobosms API returns status:1 for success, not status:"success"
            if(isset($response['status']) && ($response['status'] == 'success' || $response['status'] == 1 || $response['status'] === true)) {
                return array('success' => true, 'response' => $response);
            } else {
                // Check for error message in different fields
                $error_msg = 'Unknown error';
                if (isset($response['message'])) {
                    $error_msg = $response['message'];
                } elseif (isset($response['msg'])) {
                    $error_msg = $response['msg'];
                } elseif (isset($response['error'])) {
                    $error_msg = $response['error'];
                }
                
                log_message('error', '80kobosms API Error: ' . $error_msg);
                return array('success' => false, 'error' => $error_msg, 'response' => $response);
            }
        } else {
            log_message('error', '80kobosms HTTP Error: ' . $http_code . ' - ' . $result);
            return array('success' => false, 'error' => 'HTTP Error: ' . $http_code, 'response' => $result);
        }
    }
    
    // Replace placeholders in message
    private function replace_placeholders($message, $customer) {
        $setting = $this->db->select('*')->from('setting')->where('id', 2)->get()->row();
        
        $replacements = array(
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{fullname}' => $customer->firstname . ' ' . $customer->lastname,
            '{hotelname}' => $setting ? $setting->title : '',
            '{phone}' => $setting ? $setting->phone : '',
            '{email}' => $setting ? $setting->email : ''
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
    
    // SMS History view
    public function history() {
        $data['title'] = 'SMS History';
        $data['module'] = "dashboard";
        $data['page'] = "sms/history";
        
        // Pagination
        $config['base_url'] = base_url('dashboard/sms_compose/history');
        $config['total_rows'] = $this->sms_compose_model->get_sms_history_count();
        $config['per_page'] = 20;
        $config['uri_segment'] = 4;
        
        $this->pagination->initialize($config);
        
        $page = ($this->uri->segment(4)) ? $this->uri->segment(4) : 0;
        $data['history'] = $this->sms_compose_model->get_sms_history($config['per_page'], $page);
        $data['pagination'] = $this->pagination->create_links();
        
        echo Modules::run('template/layout', $data);
    }
    
    // View single SMS details
    public function view_sms($id) {
        $data['title'] = 'SMS Details';
        $data['module'] = "dashboard";
        $data['page'] = "sms/view_sms";
        $data['sms'] = $this->sms_compose_model->get_sms_by_id($id);
        
        if (!$data['sms']) {
            $this->session->set_flashdata('error', 'SMS not found');
            redirect('dashboard/sms_compose/history');
        }
        
        echo Modules::run('template/layout', $data);
    }
    
    // Save draft
    public function save_draft() {
        $title = $this->input->post('title', TRUE);
        $message = $this->input->post('message', TRUE);
        $recipient_type = $this->input->post('recipient_type', TRUE);
        $recipients = $this->input->post('customer_ids', TRUE);
        
        $data = array(
            'title' => $title,
            'message' => $message,
            'recipient_type' => $recipient_type,
            'recipients' => $recipients,
            'created_by' => $this->session->userdata('id'),
            'created_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        );
        
        if ($this->sms_compose_model->save_draft($data)) {
            echo json_encode(array('success' => true, 'message' => 'Draft saved successfully'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Failed to save draft'));
        }
    }
    
    // Get template by ID (AJAX)
    public function get_template() {
        $id = $this->input->post('id', TRUE);
        $template = $this->sms_compose_model->get_template_by_id($id);
        
        if ($template) {
            echo json_encode(array('success' => true, 'template' => $template));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Template not found'));
        }
    }
    
    // Quick Templates Management
    public function quick_templates() {
        $data['title'] = 'Quick Templates';
        $data['module'] = "dashboard";
        $data['page'] = "sms/quick_templates";
        $data['templates'] = $this->sms_compose_model->get_quick_templates();
        
        echo Modules::run('template/layout', $data);
    }
    
    // Save quick template
    public function save_quick_template() {
        $data = array(
            'title' => $this->input->post('title', TRUE),
            'message' => $this->input->post('message', TRUE),
            'category' => $this->input->post('category', TRUE),
            'is_active' => 1,
            'created_by' => $this->session->userdata('id'),
            'created_date' => date('Y-m-d H:i:s')
        );
        
        if ($this->sms_compose_model->save_quick_template($data)) {
            $this->session->set_flashdata('message', 'Template saved successfully');
        } else {
            $this->session->set_flashdata('error', 'Failed to save template');
        }
        
        redirect('dashboard/sms_compose/quick_templates');
    }
    
    // Delete quick template
    public function delete_template($id) {
        if ($this->sms_compose_model->delete_quick_template($id)) {
            $this->session->set_flashdata('message', 'Template deleted successfully');
        } else {
            $this->session->set_flashdata('error', 'Failed to delete template');
        }
        
        redirect('dashboard/sms_compose/quick_templates');
    }
    
    // Show API test page
    public function test() {
        $data['title'] = 'API Test & Debug';
        $data['module'] = "dashboard";
        $data['page'] = "sms/test_api";
        
        echo Modules::run('template/layout', $data);
    }
    
    // Test 80kobosms API connection (for debugging)
    public function test_api() {
        // Get gateway configuration
        $gateway = $this->sms_model->retrieve_active_getway();
        
        if (!$gateway) {
            echo json_encode(array(
                'success' => false,
                'message' => 'No active SMS gateway configured',
                'help' => 'Please configure 80kobosms in Settings → SMS Configuration'
            ));
            return;
        }
        
        // Test phone number (use admin's or a test number)
        $test_phone = $this->input->post('test_phone');
        if (empty($test_phone)) {
            $test_phone = '2348000000000'; // Placeholder
        }
        
        $test_message = 'Test SMS from Hotel Management System at ' . date('Y-m-d H:i:s');
        
        // Prepare API request
        $email = $gateway->user_name;
        $password = $gateway->password;
        $sender_name = !empty($gateway->sms_from) ? $gateway->sms_from : '80koboSMS';
        $forcednd = !empty($gateway->userid) ? $gateway->userid : '0';
        
        $data = array(
            "email"       => $email,
            "password"    => $password,
            "message"     => $test_message,
            "sender_name" => $sender_name,
            "recipients"  => $test_phone,
            "forcednd"    => $forcednd
        );
        
        $data_string = json_encode($data);
        
        // Log request
        $debug_data = $data;
        $debug_data['password'] = '****';  // Hide password
        
        // Make API call
        $ch = curl_init('https://api.80kobosms.com/v2/app/sms');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        
        // Parse response
        $response = json_decode($result, true);
        
        // Return comprehensive debug info
        echo json_encode(array(
            'gateway_config' => array(
                'email' => $email,
                'sender_name' => $sender_name,
                'forcednd' => $forcednd,
                'gateway_status' => $gateway->status == 1 ? 'Active' : 'Inactive'
            ),
            'request_sent' => $debug_data,
            'curl_info' => array(
                'http_code' => $http_code,
                'curl_error' => $curl_error,
                'curl_errno' => $curl_errno
            ),
            'api_response' => array(
                'raw' => $result,
                'parsed' => $response,
                'status' => isset($response['status']) ? $response['status'] : 'unknown',
                'is_success' => (isset($response['status']) && ($response['status'] == 1 || $response['status'] == 'success'))
            ),
            'diagnosis' => $this->diagnose_api_response($http_code, $response, $curl_errno, $curl_error),
            'timestamp' => date('Y-m-d H:i:s')
        ), JSON_PRETTY_PRINT);
    }
    
    // Diagnose API response
    private function diagnose_api_response($http_code, $response, $curl_errno, $curl_error) {
        $diagnosis = array();
        
        if ($curl_errno) {
            $diagnosis[] = "❌ cURL Error: Connection failed - " . $curl_error;
            $diagnosis[] = "Check: Internet connection, firewall, SSL certificate";
            return $diagnosis;
        }
        
        if ($http_code != 200) {
            $diagnosis[] = "❌ HTTP Error: " . $http_code;
            if ($http_code == 401) {
                $diagnosis[] = "Problem: Invalid credentials (email or password)";
                $diagnosis[] = "Solution: Check your 80kobosms email and password";
            } elseif ($http_code == 400) {
                $diagnosis[] = "Problem: Bad request (invalid parameters)";
                $diagnosis[] = "Solution: Check phone number format, sender name, message";
            } elseif ($http_code == 500) {
                $diagnosis[] = "Problem: 80kobosms server error";
                $diagnosis[] = "Solution: Try again later or contact 80kobosms support";
            }
            return $diagnosis;
        }
        
        if (!isset($response['status'])) {
            $diagnosis[] = "❌ Invalid response format from API";
            $diagnosis[] = "Check: API endpoint, request format";
            return $diagnosis;
        }
        
        // 80kobosms returns status:1 for success
        if ($response['status'] == 'success' || $response['status'] == 1 || $response['status'] === true) {
            $diagnosis[] = "✅ SMS API is working correctly!";
            $diagnosis[] = "Configuration is valid and SMS can be sent.";
            if (isset($response['balance'])) {
                $diagnosis[] = "Account Balance: ₦" . $response['balance'];
            }
            if (isset($response['units'])) {
                $diagnosis[] = "SMS Units Used: " . $response['units'];
            }
        } else {
            $diagnosis[] = "❌ API returned error status";
            if (isset($response['message'])) {
                $diagnosis[] = "Error: " . $response['message'];
            }
            
            // Common error messages
            if (isset($response['message'])) {
                $msg = strtolower($response['message']);
                if (strpos($msg, 'insufficient') !== false || strpos($msg, 'balance') !== false) {
                    $diagnosis[] = "Problem: Insufficient balance in 80kobosms account";
                    $diagnosis[] = "Solution: Top up your 80kobosms account";
                } elseif (strpos($msg, 'invalid') !== false && strpos($msg, 'credential') !== false) {
                    $diagnosis[] = "Problem: Invalid login credentials";
                    $diagnosis[] = "Solution: Verify email and password in SMS Configuration";
                } elseif (strpos($msg, 'sender') !== false) {
                    $diagnosis[] = "Problem: Invalid sender name";
                    $diagnosis[] = "Solution: Use alphanumeric sender name (max 11 chars)";
                } elseif (strpos($msg, 'recipient') !== false || strpos($msg, 'phone') !== false) {
                    $diagnosis[] = "Problem: Invalid phone number format";
                    $diagnosis[] = "Solution: Use format 2348012345678 (country code + number)";
                }
            }
        }
        
        return $diagnosis;
    }
}

