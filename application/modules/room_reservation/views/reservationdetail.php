<div class="card">
    <div class="card-body">
        <form>
           <?php
// Nights calculation
$instart = strtotime($bookinginfo->checkindate);
$inend   = strtotime($bookinginfo->checkoutdate);
$indays  = ceil(($inend - $instart) / (60 * 60 * 24));

if($bookinginfo->bookingstatus == 0 && $bookinginfo->booked_from == 1){
    $indays = 1;
}
if ($indays <= 0) { $indays = 1; }

// Base rent
$baseRent = floatval($bookinginfo->total_price) * $indays;

// Discount (per day × nights)
$discountAmount = (!empty($bookinginfo->discountamount) ? floatval($bookinginfo->discountamount) : 0);


// TAX calculation
$totaltax = 0;
if (empty($btaxinfo->bookedid)) {
    if (!empty($taxinfo) && is_array($taxinfo)) {
        foreach ($taxinfo as $tax) {
            $totaltax += ($tax->rate * $baseRent) / 100;
        }
    }
} else {
    $rate = explode(",", $btaxinfo->rate);
    foreach ($rate as $r) {
        if ($r != "") {
            $totaltax += ($r * $baseRent) / 100;
        }
    }
}

// Service charge
$scharge = 0;
if($bookinginfo->bookingstatus == 5 && !empty($btaxinfo->scharge)){
    $scharge = $btaxinfo->scharge;
} else {
    $scharge = (!empty($setting->servicecharge)) ? ($baseRent * $setting->servicecharge) / 100 : 0;
}

// Subtotal before discount
$subtotal = $baseRent + $totaltax + $scharge;

// **Do NOT protect discount** — this allows negative total like your previous -319,500
$totalAfterDiscount = $subtotal - $discountAmount;

// Grand total including extras and complementary
$grandTotal = $totalAfterDiscount
              + floatval($bookinginfo->totalComplementary)
              + floatval($bookinginfo->totalExAmount);

// Balance
$balance = $grandTotal - floatval($bookinginfo->paid_amount);
            $singleRoom = explode(",", $bookinginfo->roomid);
            $roomnames = [];
            foreach($singleRoom as $rmid){
                $roominfo = $this->db->select("roomtype")->from('roomdetails')->where('roomid',$rmid)->get()->row();
                if($roominfo) { $roomnames[] = $roominfo->roomtype; }
            }
            $roomnameString = implode(", ", $roomnames);
            ?>

            <h2 class="font-weight-600 mb-3"><?php echo display('booking_information') ?></h2>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-600"><?php echo display('booking_number') ?></label>
                        <div><?php echo html_escape($bookinginfo->booking_number); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-600"><?php echo display('room_name') ?></label>
                        <div><?php echo html_escape($roomnameString); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-600"><?php echo display('checkin') ?></label>
                        <div><?php echo html_escape($bookinginfo->checkindate); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-600"><?php echo display('checkout') ?></label>
                        <div><?php echo html_escape($bookinginfo->checkoutdate); ?></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-600">Nights</label>
                        <div><?php echo $indays; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-600"><?php echo display('adults') ?></label>
                        <div><?php echo html_escape($bookinginfo->nuofpeople); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-600"><?php echo display('children') ?></label>
                        <div><?php echo html_escape($bookinginfo->children); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-600"><?php echo display('num_of_room') ?></label>
                        <div><?php echo html_escape($bookinginfo->total_room); ?> (<?php echo html_escape($bookinginfo->room_no); ?>)</div>
                    </div>
                </div>
            </div>

            <hr>

            <h4 class="font-weight-600 mt-3">Financial Breakdown</h4>
            <div class="row">
                <div class="col-md-3">
                    <label class="text-muted">Base Rent (<?php echo $indays; ?> nights)</label>
                    <div class="font-weight-bold"><?php echo number_format($baseRent, 2); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted">Total Tax</label>
                    <div><?php echo number_format($totaltax, 2); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted">Service Charge</label>
                    <div><?php echo number_format($scharge, 2); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted">Subtotal</label>
                    <div><?php echo number_format($subtotal, 2); ?></div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-3 text-danger">
                    <label>Discount</label>
                    <div>-<?php echo number_format($discountAmount, 2); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted">Extra Charges</label>
                    <div><?php echo number_format($bookinginfo->totalExAmount, 2); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted">Complementary</label>
                    <div><?php echo number_format($bookinginfo->totalComplementary, 2); ?></div>
                </div>
                <div class="col-md-3 font-weight-bold text-primary">
                    <label>Grand Total</label>
                    <div style="font-size: 1.2rem;"><?php echo number_format($grandTotal, 2); ?></div>
                </div>
            </div>

            <div class="row mt-3 bg-light p-2 border-radius">
                <div class="col-md-6 text-success border-right">
                    <label class="font-weight-600"><?php echo display('paid_amount') ?></label>
                    <div class="h4"><?php echo number_format($bookinginfo->paid_amount, 2); ?></div>
                </div>
                <div class="col-md-6 text-warning">
                    <label class="font-weight-600">Balance Due</label>
                    <div class="h4"><?php echo number_format($balance, 2); ?></div>
                </div>
            </div>

            <hr>

            <h2 class="font-weight-600 mt-3"><?php echo display('customer_information') ?></h2>
            <?php 
                $userinfo = $this->db->select("*")
                    ->from('customerinfo')
                    ->where('customerid', $bookinginfo->cutomerid)
                    ->get()->row(); 
            ?>
            <div class="row">
                <div class="col-md-3">
                    <label class="font-weight-600"><?php echo display('account_name') ?></label>
                    <div><?php echo html_escape($userinfo->firstname." ".$userinfo->lastname); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-600"><?php echo display('email') ?></label>
                    <div><?php echo html_escape($userinfo->email); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-600"><?php echo display('phone') ?></label>
                    <div><?php echo html_escape($userinfo->cust_phone); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-600"><?php echo display('address') ?></label>
                    <div><?php echo html_escape($userinfo->address); ?></div>
                </div>
            </div>

            <?php if(!empty($bookinginfo->special_request)): ?>
            <div class="row mt-3">
                <div class="col-md-12">
                    <label class="font-weight-600"><?php echo display('special_request') ?></label>
                    <div class="alert alert-info py-2"><?php echo html_escape($bookinginfo->special_request); ?></div>
                </div>
            </div>
            <?php endif; ?>

        </form>
    </div>
</div>