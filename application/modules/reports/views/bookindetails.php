<link type="text/css" href="<?php echo MOD_URL.$module;?>/assets/css/table.css">
<div class="card">
    <div class="card-body" id="printArea">
        <div class="row">
            <div class="col-sm-6">
                <img src="<?php echo base_url();?><?php echo html_escape(!empty($commominfo->invoice_logo)?$commominfo->invoice_logo: 'assets/img/header-logo.png')?>"
                    class="img-fluid mb-3" alt="">
                <br>
                <address>
                    <strong><?php echo html_escape($storeinfo->storename);?></strong><br>
                    <?php echo html_escape($storeinfo->address);?><br>
                    <abbr title="Phone"><?php echo display('mobile') ?>:</abbr>
                    <?php echo html_escape($storeinfo->phone);?>
                </address>
                <address>
                    <strong><?php echo display('email') ?></strong><br>
                    <a href="mailto:#"><?php echo html_escape($storeinfo->email);?></a>
                </address>
            </div>
            <?php
            $firstdate = !empty($bookinfo->checkindate) ? $bookinfo->checkindate : date('Y-m-d');
            $lastdate = !empty($bookinfo->checkoutdate) ? $bookinfo->checkoutdate : date('Y-m-d');
            $datediff = strtotime($lastdate) - strtotime($firstdate);
            $datediff = max(1, ceil($datediff/(60*60*24))); // Ensure at least 1 day
            $creditamt = null;
            if(!empty($bookinfo->bookingstatus) && $bookinfo->bookingstatus==5){
                $creditamt = $this->db->select("credit")->from("tbl_postedbills")->where("bookedid",$bookinfo->bookedid)->get()->row();
            }
        ?>
            <div class="col-sm-6 text-right">
                <h1 class="h3"><?php echo display('booking_number') ?>
                    #<?php echo html_escape($bookinfo->booking_number);?></h1>
                <div><?php echo display('booking_date') ?>: <?php echo html_escape($bookinfo->date_time);?></div>
                <div class="text-danger m-b-15"><?php echo display('payment_status') ?>:
                    <?php
                    // CRITICAL FIX: Calculate full booking total for payment status check
                    // Calculate base rent the same way as the main calculation (from daily loop)
                    // This ensures consistency between payment status and actual totals
                    $baseRentForStatus = 0;
                    $roomIdForStatus = !empty($bookinfo->roomid) ? explode(",", $bookinfo->roomid) : array();
                    $roomRateForStatus = !empty($bookinfo->roomrate) ? explode(",", $bookinfo->roomrate) : array();

                    // Calculate base rent by summing all room rates for all days (same logic as main calculation)
                    for($li = 0; $li < count($roomIdForStatus); $li++){
                        if(!isset($roomIdForStatus[$li]) || empty($roomIdForStatus[$li])){
                            continue;
                        }
                        $roomRateValueForStatus = isset($roomRateForStatus[$li]) ? floatval($roomRateForStatus[$li]) : 0;
                        for($i = 0; $i < $datediff; $i++){
                            $alldaysForStatus = date("Y-m-d", strtotime($firstdate . ' + ' . $i . 'day'));
                            $getroomForStatus = $this->db->select("*")->from('tbl_room_offer')->where('roomid',$roomIdForStatus[$li])->where('offer_date',$alldaysForStatus)->get()->row();
                            if(!empty($getroomForStatus) && !empty($getroomForStatus->offer)){
                                $singleDiscountForStatus = floatval($getroomForStatus->offer);
                                $roomrateForStatus = $roomRateValueForStatus - $singleDiscountForStatus;
                            } else {
                                $roomrateForStatus = $roomRateValueForStatus;
                            }
                            $baseRentForStatus += $roomrateForStatus;
                        }
                    }

                    $disamountForStatus = $this->db->select("discountamount")->from("booked_details")->where("bookedid", $bookinfo->bookedid)->get()->row();
                    $discountForStatus = (!empty($disamountForStatus->discountamount) ? floatval($disamountForStatus->discountamount) : 0) * $datediff;

                    // Get promocode discount if any
                    $promocodeForStatus = 0;
                    if(!empty($bookinfo->promocode)){
                        $pdiscountForStatus = $this->db->select("discount")->from("promocode")->where("promocode", $bookinfo->promocode)->get()->row();
                        $promocodeForStatus = (!empty($pdiscountForStatus) && !empty($pdiscountForStatus->discount)) ? floatval($pdiscountForStatus->discount) : 0;
                    }

                    $baseRentAfterDiscountForStatus = $baseRentForStatus - $discountForStatus - $promocodeForStatus;
                    $baseRentAfterDiscountForStatus = max(0, $baseRentAfterDiscountForStatus);

                    // Get tax on base rent (before discount)
                    $taxForStatus = 0;
                    if(!empty($taxinfo) && is_array($taxinfo)){
                        foreach($taxinfo as $tax){
                            $taxRate = floatval($tax->rate ?? 0);
                            if($taxRate > 0){
                                $taxForStatus += ($baseRentForStatus * $taxRate) / 100;
                            }
                        }
                    }

                    // Get service charge on base rent (before discount)
                    $schargeForStatus = 0;
                    if(!empty($setting->servicecharge)){
                        $schargeForStatus = ($baseRentForStatus * floatval($setting->servicecharge)) / 100;
                    }

                    $fullTotalForStatus = $baseRentAfterDiscountForStatus + $taxForStatus + $schargeForStatus;

                    // Get actual paid amount
                    // CRITICAL FIX: Filter by book_type = 0 to only get room booking payments
                    $this->db->select('SUM(paymentamount) as actual_paid');
                    $this->db->from('tbl_guestpayments');
                    $this->db->where('bookedid', $bookinfo->bookedid);
                    $this->db->where('book_type', 0); // Only room booking payments
                    $paymentQueryForStatus = $this->db->get();
                    $paymentsPaidForStatus = 0;
                    if ($paymentQueryForStatus->num_rows() > 0 && $paymentQueryForStatus->row()->actual_paid) {
                        $paymentsPaidForStatus = floatval($paymentQueryForStatus->row()->actual_paid);
                    }

                    // Get paid_amount from booked_info (most up-to-date cumulative total)
                    $bookedInfoPaidForStatus = floatval($bookinfo->paid_amount ?? 0);

                    // Use the maximum to ensure we get the most current payment amount
                    // This handles cases where payments are updated vs inserted as new records
                    $actualPaidForStatus = max($paymentsPaidForStatus, $bookedInfoPaidForStatus);

                    if($bookinfo->bookingstatus==5){
                        if(!empty($creditamt) && $creditamt->credit>0){
                            echo display("credit");
                        } else {
                            echo display("paid");
                        }
                    } else if($actualPaidForStatus < $fullTotalForStatus - 0.01){
                        echo display("unpaid");
                    } else {
                        echo display("paid");
                    }
                    ?>
                </div>
                <address>
                    <strong><?php echo display('guest_info') ?></strong><br>
                    <?php echo html_escape((!empty($customerinfo->firstname)?$customerinfo->firstname.' '.$customerinfo->lastname:'User Deleted'));?><br>
                    <?php echo display('address') ?>:
                    <?php echo html_escape(!empty($customerinfo->address)?$customerinfo->address:null);?><br>
                    <abbr title="Phone"><?php echo display('mobile') ?>:</abbr>
                    <?php echo html_escape(!empty($customerinfo->cust_phone)?$customerinfo->cust_phone:null);?>
                </address>
                <address>
                    <strong><?php echo display('email') ?></strong><br>
                    <a
                        href="mailto:#"><?php echo html_escape(!empty($customerinfo->email)?$customerinfo->email:null);?></a>
                </address>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <tbody>
                    <tr>
                        <td>
                            <div><strong><?php echo display('roomtype') ?></strong></div>
                        </td>
                        <?php
                    $allroomtype="";
                    if(!empty($bookinfo->roomid)){
                        $roomid = explode(",",$bookinfo->roomid);
                        for($i=0;$i<count($roomid); $i++){
                            $roomtype = $this->db->select("roomtype")->from("roomdetails")->where("roomid",$roomid[$i])->get()->row();
                            if(!empty($roomtype) && !empty($roomtype->roomtype)){
                                $allroomtype .= $roomtype->roomtype.",";
                            }
                        }
                    }
                 ?>
                        <td><?php echo trim($allroomtype,",");?></td>
                    </tr>
                    <tr>
                        <td>
                            <div><strong><?php echo display('room_no') ?></strong></div>
                        </td>
                        <td><?php echo html_escape(!empty($bookinfo->room_no)?$bookinfo->room_no:null);?></td>
                    </tr>
                    <tr>
                        <td>
                            <div><strong><?php echo display('checkin') ?></strong></div>
                        </td>
                        <td><?php echo html_escape($bookinfo->checkindate);?></td>
                    </tr>
                    <tr>
                        <td>
                            <div><strong><?php echo display('checkout') ?></strong></div>
                        </td>
                        <td><?php echo html_escape($bookinfo->checkoutdate);?></td>
                    </tr>
                    <tr>
                        <td>
                            <div><strong><?php echo display('booking_status') ?></strong></div>
                        </td>
                        <td><?php if($bookinfo->bookingstatus==0){ echo display('pending');}if($bookinfo->bookingstatus==2){ echo display('complete');}if($bookinfo->bookingstatus==1){ echo display("cancel");}if($bookinfo->bookingstatus==4){ echo display("checkin");}if($bookinfo->bookingstatus==5){ echo display("checkout");}?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div><strong><?php echo display('adults') ?></strong></div>
                        </td>
                        <td><?php echo html_escape($bookinfo->nuofpeople);?></td>
                    </tr>
                    <tr>
                        <td>
                            <div><strong><?php echo display('number_of_rooms') ?></strong></div>
                        </td>
                        <td><?php echo html_escape($bookinfo->total_room);?></td>
                        <?php
                        // Calculate actual number of rooms from roomid array
                        $roomIdArray = !empty($bookinfo->roomid) ? explode(",", $bookinfo->roomid) : array();
                        $totalroom = count($roomIdArray);
                        if($totalroom == 0){
                            $totalroom = !empty($bookinfo->total_room) ? intval($bookinfo->total_room) : 1;
                        }
                        ?>
                    </tr>
                    <tr>
                        <td>
                            <div><strong><?php echo display('nights') ?></strong></div>
                        </td>
                        <td><?php
                            echo html_escape($datediff);
                        ?>
                        </td>
                    </tr>
                    <?php if(!empty($btaxinfo->remarks)){ ?>
                    <tr>
                        <td>
                            <div><strong><?php echo display('remarks') ?></strong></div>
                        </td>
                        <td><?php
                            echo html_escape($btaxinfo->remarks);
                        ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <table class="table table-striped table-nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo display('date') ?></th>
                        <th><?php echo display('price') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                                    $totaldiscount=0;
                                    $roomrate=0;
                                    $x=0;
                                    $total=0;
                                    $disamount = $this->db->select("discountamount")->from("booked_details")->where("bookedid", $bookinfo->bookedid)->get()->row();
                                    $promocode=0;
                                    if(!empty($bookinfo->promocode)){
                                        $pdiscount = $this->db->select("discount")->from("promocode")->where("promocode", $bookinfo->promocode)->get()->row();
                                        $promocode = (!empty($pdiscount) && !empty($pdiscount->discount)) ? floatval($pdiscount->discount) : 0;
                                    }
                                    $roomId = !empty($bookinfo->roomid) ? explode(",", $bookinfo->roomid) : array();
                                    $roomRate = !empty($bookinfo->roomrate) ? explode(",", $bookinfo->roomrate) : array();
                                    $roomIdCount = count($roomId);
                                    for($li = 0; $li < $roomIdCount; $li++){
                                        if(!isset($roomId[$li]) || empty($roomId[$li])){
                                            continue;
                                        }
                                        for($i = 0; $i < $datediff; $i++){
                                        $alldays= date("Y-m-d", strtotime($firstdate . ' + ' . $i . 'day'));
                                        $x++;
                                        $getroom=$this->db->select("*")->from('tbl_room_offer')->where('roomid',$roomId[$li])->where('offer_date',$alldays)->get()->row();
                                        $roomRateValue = isset($roomRate[$li]) ? floatval($roomRate[$li]) : 0;
                                        if(!empty($getroom) && !empty($getroom->offer)){
                                            $singleDiscount=floatval($getroom->offer);
                                            $totaldiscount=$totaldiscount+$singleDiscount;
                                            $roomrate=$roomRateValue-$singleDiscount;
                                            }
                                        else{
                                            $roomrate=$roomRateValue;
                                            }
                                        // Price per room per day (totalroom is already calculated above from room count)
                                        $price = $roomrate; // Price is per room per day, loop already handles multiple rooms
                                        $total=$total+$price;
                    ?>
                    <tr>
                        <td>
                            <div><strong><?php echo $x;?></strong></div>
                        </td>
                        <td><?php echo html_escape($alldays);?></td>
                        <td><?php echo html_escape($roomrate);?></td>
                    </tr>
                    <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-sm-8">
            </div>
            <div class="col-sm-4">
                <ul class="list-unstyled text-right">
                    <?php
                    // CRITICAL FIX: Discount applied ONLY to base rent (roomrent), not to subtotal
                    // Ensure all values are numeric to prevent "non-numeric value encountered" warnings
                    // CRITICAL FIX: Cast accumulated $total to float (it may have accumulated string values in loop)
                    $total = floatval($total);
                    // Base rent (before discount) - this is the subtotal from the daily price loop
                    // The loop already calculates total for all rooms and all days
                    $baseRent = $total;

                    // Calculate discount on BASE RENT only
                    $discountAmount = (!empty($disamount->discountamount) ? floatval($disamount->discountamount) : 0) * $datediff; // Discount for all days
                    $promocodeDiscount = (!empty($promocode) ? floatval($promocode) : 0);
                    $baseRentAfterDiscount = $baseRent - $discountAmount - $promocodeDiscount;
                    $baseRentAfterDiscount = max(0, $baseRentAfterDiscount);

                    // Calculate tax on BASE RENT (before discount)
                    $totaltax = 0;
                    $scharge = 0;
                    if(empty($btaxinfo) || empty($btaxinfo->bookedid)){
                        if(!empty($taxinfo) && is_array($taxinfo)){
                             foreach($taxinfo as $tax){
                                $taxRate = floatval($tax->rate ?? 0);
                                $singletax = ($taxRate * $baseRent) / 100;
                                $totaltax += $singletax;
                             }
                        }
                    } else {
                        // Use posted bills tax info if available (for checkout bookings)
                        if(!empty($btaxinfo->rate)){
                            $rate = explode(",", $btaxinfo->rate ?? '');
                            if(!empty($rate[0])){
                                for($bt=0; $bt<count($rate); $bt++){
                                    $taxRate = floatval($rate[$bt] ?? 0);
                                    if($taxRate > 0){
                                        $singletax = ($taxRate * $baseRent) / 100;
                                        $totaltax += $singletax;
                                    }
                                }
                            }
                        } else {
                            // Fallback: If posted bill exists but no rate, use active tax rates
                            if(!empty($taxinfo) && is_array($taxinfo)){
                                 foreach($taxinfo as $tax){
                                    $taxRate = floatval($tax->rate ?? 0);
                                    if($taxRate > 0){
                                        $singletax = ($taxRate * $baseRent) / 100;
                                        $totaltax += $singletax;
                                    }
                                 }
                            }
                        }
                    }

                    // Calculate service charge on BASE RENT (before discount)
                    if($bookinfo->bookingstatus==5 && !empty($btaxinfo) && !empty($btaxinfo->scharge)){
                        $scharge = floatval($btaxinfo->scharge);
                    } else {
                        $serviceChargeRate = floatval($setting->servicecharge ?? 0);
                        $scharge = ($baseRent * $serviceChargeRate) / 100;
                    }
                    ?>
                    <li>
                        <strong><?php echo display('subtotal'); ?>:</strong> <?php echo number_format($baseRent, 2); ?>
                    </li>
                    <?php if($discountAmount>0){ ?>
                    <li>
                        <strong><?php echo display('discount'); ?>:</strong> <?php echo number_format($discountAmount, 2); ?>
                    </li>
                    <?php } ?>
                    <?php if($promocodeDiscount>0){ ?>
                    <li>
                        <strong><?php echo display("promocode_discount"); ?>:</strong> <?php echo number_format($promocodeDiscount, 2); ?>
                    </li>
                    <?php } ?>
                    <?php if(empty($btaxinfo) || empty($btaxinfo->bookedid)){ ?>
                    <?php $taxinfo = $taxinfo ?: []; ?>
                    <?php if(!empty($taxinfo) && is_array($taxinfo)){ ?>
                    <?php foreach($taxinfo as $tax){
                        $taxRate = floatval($tax->rate ?? 0);
                        if($taxRate > 0){
                            $singletax = ($taxRate * $baseRent) / 100;
                    ?>
                    <li>
                        <strong><?php echo html_escape($tax->taxname); ?> (VAT)
                            <?php echo html_escape($tax->rate);?>%:</strong> <?php echo number_format($singletax, 2); ?>
                    </li>
                    <?php } ?>
                    <?php } ?>
                    <?php } ?>
                    <?php }else{ ?>
                    <?php
                    // CRITICAL FIX: Ensure explode doesn't receive null
                    // For posted bills, use the rate from btaxinfo if available
                    if(!empty($btaxinfo) && !empty($btaxinfo->bookedid) && !empty($btaxinfo->rate)) {
                        $taskname = !empty($btaxinfo->taskname) ? explode(",", $btaxinfo->taskname) : array();
                        $rate = explode(",", $btaxinfo->rate);
                    } else {
                        $taskname = array();
                        $rate = array();
                    }
                    ?>
                    <?php if(!empty($rate) && !empty($rate[0])){
                        for($bt=0; $bt<count($rate); $bt++){
                            $taxRate = floatval($rate[$bt] ?? 0);
                            if($taxRate > 0){
                                $taxName = isset($taskname[$bt]) ? html_escape($taskname[$bt]) : display('tax');
                                $singletax = ($taxRate * $baseRent) / 100;
                    ?>
                    <li>
                        <strong><?php echo $taxName; ?> (VAT)
                            <?php echo html_escape($rate[$bt]);?>%:</strong> <?php echo number_format($singletax, 2); ?>
                    </li>
                    <?php } ?>
                    <?php } ?>
                    <?php } } ?>
                    <?php if(floatval($totaltax) > 0){ ?>
                    <li>
                        <strong><?php echo display('tax') ?> (VAT):</strong> <?php echo number_format(floatval($totaltax), 2);?>
                    </li>
                    <?php } ?>
                    <?php if($bookinfo->bookingstatus==5 && !empty($btaxinfo)){ ?>
                    <?php if(!empty($btaxinfo->scharge) || ($scharge > 0)){ ?>
                    <li>
                        <strong><?php echo display("service_charge") ?> :</strong> <?php echo number_format(floatval($scharge), 2);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->complementary) && $btaxinfo->complementary>0){ ?>
                    <li>
                        <strong><?php echo display('complementary') ?> :</strong> <?php echo html_escape($btaxinfo->complementary);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->extrabpc) && $btaxinfo->extrabpc>0){ ?>
                    <li>
                        <strong><?php echo display("extra_bpc"); ?> :</strong> <?php echo html_escape($btaxinfo->extrabpc);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->ex_discount) && $btaxinfo->ex_discount>0){ ?>
                    <li>
                        <strong><?php echo display("extra").display('discount') ?> :</strong> <?php echo html_escape($btaxinfo->ex_discount);?>
                    </li>
                    <?php
                            $denominator = $baseRentAfterDiscount+$totaltax+$scharge;
                            if($denominator > 0){
                                $percent = ($btaxinfo->ex_discount*100)/$denominator;
                                $reducetax = ($totaltax*$percent)/100;
                                $totaltax-=$reducetax;
                            } else {
                                $reducetax = 0;
                            }
                         ?>
                    <?php }else{ $reducetax = 0; } ?>
                    <?php if(!empty($btaxinfo->additional_charges) && $btaxinfo->additional_charges>0){ ?>
                    <li>
                        <strong><?php echo display("additional_charges"); ?> :</strong>
                        <?php echo html_escape($btaxinfo->additional_charges);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->special_discount) && $btaxinfo->special_discount>0){ ?>
                    <li>
                        <strong><?php echo display("special_discount"); ?> :</strong> <?php echo html_escape($btaxinfo->special_discount);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->swimming_pool) && $btaxinfo->swimming_pool>0){ ?>
                    <li>
                        <strong><?php echo display("swimming_pool"); ?> :</strong> <?php echo html_escape($btaxinfo->swimming_pool);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->restaurant) && $btaxinfo->restaurant>0){ ?>
                    <li>
                        <strong><?php echo display("restaurant"); ?> :</strong> <?php echo html_escape($btaxinfo->restaurant);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->hallroom) && $btaxinfo->hallroom>0){ ?>
                    <li>
                        <strong><?php echo display("hall_room"); ?> :</strong> <?php echo html_escape($btaxinfo->hallroom);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->car_parking) && $btaxinfo->car_parking>0){ ?>
                    <li>
                        <strong><?php echo display("car_parking"); ?> :</strong> <?php echo html_escape($btaxinfo->car_parking);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->days) && $btaxinfo->days>0){ ?>
                    <li>
                        <strong><?php echo display("refund_days"); ?> :</strong> <?php echo html_escape($btaxinfo->days);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->amount) && $btaxinfo->amount>0){ ?>
                    <li>
                        <strong><?php echo display("refund_amount"); ?> :</strong> <?php echo html_escape($btaxinfo->amount);?>
                    </li>
                    <?php } ?>
                    <?php if(!empty($btaxinfo->charge) && $btaxinfo->charge>0){ ?>
                    <li>
                        <strong><?php echo display("refund_charge"); ?> :</strong> <?php echo html_escape($btaxinfo->charge);?>
                    </li>
                    <?php } ?>
                    <?php
                    $postedbill = floatval($btaxinfo->complementary ?? 0) + floatval($btaxinfo->extrabpc ?? 0) - floatval($btaxinfo->ex_discount ?? 0) + floatval($btaxinfo->additional_charges ?? 0) - floatval($btaxinfo->special_discount ?? 0) + floatval($btaxinfo->swimming_pool ?? 0) + floatval($btaxinfo->restaurant ?? 0) + floatval($btaxinfo->hallroom ?? 0) + floatval($btaxinfo->car_parking ?? 0);
                }else{
                    $postedbill = 0;
                    $reducetax = 0;
                    // Service charge already calculated above on base rent
                    ?>
                    <li>
                        <strong><?php echo display("service_charge"); ?> :</strong> <?php echo number_format(floatval($scharge), 2);?>
                    </li>
                    <?php } ?>
                    <li>
                        <?php
                        // Grand total = ((base rent - discount) + tax + service charge) + posted bill charges
                        // $baseRentAfterDiscount already contains: (base rent - discount)
                        $grand_total = $baseRentAfterDiscount + $totaltax + $scharge + $postedbill;

                        // Get actual paid amount from payments table
                        // CRITICAL FIX: Filter by book_type = 0 to only get room booking payments (not hall bookings)
                        $this->db->select('SUM(paymentamount) as actual_paid');
                        $this->db->from('tbl_guestpayments');
                        $this->db->where('bookedid', $bookinfo->bookedid);
                        $this->db->where('book_type', 0); // Only room booking payments
                        $paymentQuery = $this->db->get();
                        $paymentsPaid = 0;
                        if ($paymentQuery->num_rows() > 0 && $paymentQuery->row()->actual_paid) {
                            $paymentsPaid = floatval($paymentQuery->row()->actual_paid);
                        }

                        // Get advance_amount from booked_details (this is usually the initial advance payment)
                        $advanceAmount = 0;
                        if(!empty($disamount)){
                            $advanceQuery = $this->db->select('advance_amount')->from('booked_details')->where('bookedid', $bookinfo->bookedid)->get();
                            if($advanceQuery->num_rows() > 0){
                                $advanceAmount = floatval($advanceQuery->row()->advance_amount ?? 0);
                            }
                        }

                        // Get paid_amount from booked_info (this is updated when payments are made and should be most current)
                        $bookedInfoPaid = floatval($bookinfo->paid_amount ?? 0);

                        // CRITICAL FIX: Use the maximum of all three sources to ensure we get the most up-to-date payment
                        // This handles cases where:
                        // 1. Payments are stored as separate records in tbl_guestpayments (SUM works)
                        // 2. Payments are updated in existing records (booked_info.paid_amount is updated)
                        // 3. Advance payments are stored separately (advance_amount)
                        // We use the maximum to ensure we don't miss any payments
                        $actualPaid = max($paymentsPaid, $bookedInfoPaid, $advanceAmount);

                        // If all are zero, ensure we don't have negative values
                        if ($actualPaid == 0) {
                            $actualPaid = 0;
                        }

                        // Calculate due amount
                        $due_amount = $grand_total - $actualPaid;
                        $due_amount = max(0, $due_amount);
                        ?>
                        <strong><?php echo display('grand_total') ?>:</strong>
                        <?php if(!empty($currency) && $currency->position==1){echo html_escape($currency->curr_icon);}?><?php echo number_format($grand_total, 2);?><?php if(!empty($currency) && $currency->position==2){echo html_escape($currency->curr_icon);}?>
                        <br /><strong><?php echo display('paid_amount') ?>:</strong>
                        <?php if(!empty($currency) && $currency->position==1){echo html_escape($currency->curr_icon);}?><?php echo number_format($actualPaid, 2);?><?php if(!empty($currency) && $currency->position==2){echo html_escape($currency->curr_icon);}?>
                        <br /><strong><?php echo display('due_amount') ?>:</strong>
                        <?php if(!empty($currency) && $currency->position==1){echo html_escape($currency->curr_icon);}?><?php echo number_format($due_amount, 2);?><?php if(!empty($currency) && $currency->position==2){echo html_escape($currency->curr_icon);}?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-info mr-2" onclick="printContent('printArea')"><span
                class="fa fa-print"></span></button>
    </div>
</div>
