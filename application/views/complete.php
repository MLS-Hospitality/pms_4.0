<?php
$orderid = $this->uri->segment(4);
?>

<div class="section" style="min-height:250px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 text-center">

                <?php if ($this->session->userdata('message')) { ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?php
                            echo $this->session->userdata('message');
                            $this->session->unset_userdata('message');
                        ?>
                    </div>
                <?php } ?>

                <?php if ($this->session->userdata('exception')) { ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?php
                            echo $this->session->userdata('exception');
                            $this->session->unset_userdata('exception');
                        ?>
                    </div>
                <?php } ?>

                <?php if (validation_errors()) { ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?php echo validation_errors(); ?>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>
</div>

<?php
$html = '';

// ✅ SAFETY CHECK — prevent whatsapp_settings error
if ($this->db->table_exists('whatsapp_settings')) {

    $wtapp = $this->db->get('whatsapp_settings')->row();

    if (!empty($wtapp) && (int)$wtapp->orderenable === 1) {

        $storeinfo    = $this->hotel_model->read('*', 'setting', ['id' => 2]);
        $currencysign = getCurrency();
        $wporderinfo  = $this->hotel_model->read('*', 'booked_info', ['booking_number' => $orderid]);

        if (!empty($wporderinfo)) {

            $customerinfo = $this->hotel_model->read(
                '*',
                'customerinfo',
                ['customerid' => $wporderinfo->cutomerid]
            );

            $html  = 'Hi! I would like to make a reservation%0a';
            $html .= '---------------------------------------%0a';
            $html .= '*Booking Number:* ' . $orderid . '%0a';
            $html .= '*Reservation Date:* ' . date("Y-m-d", strtotime($wporderinfo->checkindate)) . '%0a';
            $html .= '*Customer Name:* ' . $customerinfo->firstname . ' ' . $customerinfo->lastname . '%0a';
            $html .= '*Customer Address:* ' . $customerinfo->address . '%0a';
            $html .= '---------------------------------------%0a';
            $html .= '*Phone Number:* ' . $customerinfo->cust_phone . '%0a';
            $html .= '*Room Number:* ' . $wporderinfo->room_no . '%0a';
            $html .= '*Total Price:* ' . $currencysign->curr_icon . number_format($wporderinfo->total_price, 2);
        }
    }
}
?>

<input type="hidden" id="wamsg" value="<?php echo $html; ?>">

<script src="<?php echo base_url('assets/sweetalert/sweetalert.min.js'); ?>"></script>
<script src="<?php echo base_url('website_assets/js/wabooking.js?v=1'); ?>"></script>
