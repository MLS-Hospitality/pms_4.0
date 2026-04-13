<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sms_compose_model extends CI_Model {
    
    // Get all customers with phone numbers
    public function get_all_customers() {
        $this->db->select('customerid, firstname, lastname, cust_phone, email, dob, anniversary');
        $this->db->from('customerinfo');
        $this->db->where('cust_phone IS NOT NULL');
        $this->db->where('cust_phone !=', '');
        $this->db->order_by('firstname', 'asc');
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
    }
    
    // Get customers with birthdays today
    public function get_birthday_customers() {
        $today = date('Y-m-d');
        $this->db->select('customerid, firstname, lastname, cust_phone, email, dob');
        $this->db->from('customerinfo');
        $this->db->where('cust_phone IS NOT NULL');
        $this->db->where('cust_phone !=', '');
        $this->db->where("DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT('$today', '%m-%d')");
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
    }
    
    // Get customers with anniversaries today
    public function get_anniversary_customers() {
        $today = date('Y-m-d');
        $this->db->select('customerid, firstname, lastname, cust_phone, email, anniversary');
        $this->db->from('customerinfo');
        $this->db->where('cust_phone IS NOT NULL');
        $this->db->where('cust_phone !=', '');
        $this->db->where('anniversary IS NOT NULL');
        $this->db->where('anniversary !=', '');
        $this->db->where("DATE_FORMAT(anniversary, '%m-%d') = DATE_FORMAT('$today', '%m-%d')");
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
    }
    
    // Get customers by IDs
    public function get_customers_by_ids($ids) {
        if (empty($ids)) {
            return false;
        }
        
        $this->db->select('customerid, firstname, lastname, cust_phone, email');
        $this->db->from('customerinfo');
        $this->db->where_in('customerid', $ids);
        $this->db->where('cust_phone IS NOT NULL');
        $this->db->where('cust_phone !=', '');
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
    }
    
    // Save SMS to history
    public function save_sms_history($data) {
        return $this->db->insert('sms_history', $data);
    }
    
    // Get SMS history
    public function get_sms_history($limit = 50, $offset = 0) {
        $this->db->select('*');
        $this->db->from('sms_history');
        $this->db->order_by('sent_date', 'desc');
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
    }
    
    // Get SMS history count
    public function get_sms_history_count() {
        return $this->db->count_all('sms_history');
    }
    
    // Get single SMS history
    public function get_sms_by_id($id) {
        $this->db->select('*');
        $this->db->from('sms_history');
        $this->db->where('id', $id);
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        return false;
    }
    
    // Save draft
    public function save_draft($data) {
        return $this->db->insert('sms_drafts', $data);
    }
    
    // Update draft
    public function update_draft($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('sms_drafts', $data);
    }
    
    // Get all drafts
    public function get_drafts() {
        $this->db->select('*');
        $this->db->from('sms_drafts');
        $this->db->order_by('updated_date', 'desc');
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
    }
    
    // Get draft by ID
    public function get_draft_by_id($id) {
        $this->db->select('*');
        $this->db->from('sms_drafts');
        $this->db->where('id', $id);
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        return false;
    }
    
    // Delete draft
    public function delete_draft($id) {
        $this->db->where('id', $id);
        return $this->db->delete('sms_drafts');
    }
    
    // Get quick templates
    public function get_quick_templates() {
        $this->db->select('*');
        $this->db->from('sms_quick_templates');
        $this->db->where('is_active', 1);
        $this->db->order_by('category', 'asc');
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result();
        }
        return false;
    }
    
    // Get template by ID
    public function get_template_by_id($id) {
        $this->db->select('*');
        $this->db->from('sms_quick_templates');
        $this->db->where('id', $id);
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        return false;
    }
    
    // Save quick template
    public function save_quick_template($data) {
        return $this->db->insert('sms_quick_templates', $data);
    }
    
    // Update quick template
    public function update_quick_template($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('sms_quick_templates', $data);
    }
    
    // Delete quick template
    public function delete_quick_template($id) {
        $this->db->where('id', $id);
        return $this->db->delete('sms_quick_templates');
    }
    
    // Get customer count
    public function get_customer_count() {
        $this->db->where('cust_phone IS NOT NULL');
        $this->db->where('cust_phone !=', '');
        return $this->db->count_all_results('customerinfo');
    }
    
    // Get birthday count for today
    public function get_birthday_count() {
        $today = date('Y-m-d');
        $this->db->where('cust_phone IS NOT NULL');
        $this->db->where('cust_phone !=', '');
        $this->db->where("DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT('$today', '%m-%d')");
        return $this->db->count_all_results('customerinfo');
    }
    
    // Get anniversary count for today
    public function get_anniversary_count() {
        $today = date('Y-m-d');
        $this->db->where('cust_phone IS NOT NULL');
        $this->db->where('cust_phone !=', '');
        $this->db->where('anniversary IS NOT NULL');
        $this->db->where('anniversary !=', '');
        $this->db->where("DATE_FORMAT(anniversary, '%m-%d') = DATE_FORMAT('$today', '%m-%d')");
        return $this->db->count_all_results('customerinfo');
    }
}

