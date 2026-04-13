<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paystack_reference_model extends CI_Model
{
    protected $table = 'paystack_reference';

    /**
     * Generate a unique Paystack payment reference
     */
    public function generate_ref($hotel_name, $booking_id)
{
    $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $hotel_name));
    
    // Generate unique reference
    do {
        $reference = $prefix . '_' . $this->random_string(30);
    } while ($this->reference_exists($reference));
    
    // If booking_id is provided, save to database
    
        $data = [
            'ref' => $reference,
            'booked_id' => $booking_id,
            'payment_status' => 'initiated',  // or 'pending'
            'created_at' => date('Y-m-d H:i:s'),
            'used'=>false
        ];
        
        $this->db->insert('paystack_reference', $data);
        
        // Check if insert was successful
        if ($this->db->affected_rows() > 0) {
            log_message('info', 'Paystack reference created: ' . $reference . ' for booking: ' . $booking_id);
            
        } else {
            log_message('error', 'Failed to save paystack reference: ' . $reference);
            return [
                'success' => false,
                'error' => 'Database insert failed'
            ];
        }

    
    // Return just the reference string if no booking_id
    return $reference;
}
    /**
     * Update payment status by reference
     *
     * @param string $reference
     * @param bool|int $status (true/1 = success, false/0 = pending/failed)
     * @return bool
     */
    public function update_status($reference, $status)
    {
        return $this->db
            ->where('payment_reference', $reference)
            ->update($this->table, [
                'used' => (int) $status
            ]);
    }

    /**
     * Check if reference exists
     */
    private function reference_exists($reference)
    {
        return $this->db
            ->where('ref', $reference)
            ->limit(1)
            ->get($this->table)
            ->num_rows() > 0;
    }

    /**
     * Generate secure random string
     */
    private function random_string($length = 30)
    {
        return bin2hex(random_bytes($length / 2));
    }
}
