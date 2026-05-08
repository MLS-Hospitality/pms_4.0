<style>
li {
    font-weight: bold;
}
</style>
<div class="card">
    <div class="card-header">
        <h4><?php echo "Customer Details"; ?></h4>
    </div>
    <div class="row">
        <!--  table area -->
        <div class="col-sm-12">

            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group row">
                            <label for="firstname" class="col-sm-2 col-form-label"><?php echo display('firstname') ?>
                            </label>
                            <div class="col-sm-4">
                                <input readonly name="firstname" autocomplete="off" required class="form-control"
                                    type="text" placeholder="<?php echo display('firstname') ?>" id="firstname"
                                    value="<?php echo html_escape((!empty($intinfo->firstname)?$intinfo->firstname:null)) ?>">
                            </div>
                            <label for="lastname" class="col-sm-2 col-form-label"><?php echo display('lastname') ?>
                            </label>
                            <div class="col-sm-4">
                                <input readonly name="lastname" required autocomplete="off" class="form-control"
                                    type="text" placeholder="<?php echo display('lastname') ?>" id="lastname"
                                    value="<?php echo html_escape((!empty($intinfo->lastname)?$intinfo->lastname:null)) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="email" class="col-sm-2 col-form-label"><?php echo display('email') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="email" autocomplete="off" required class="form-control"
                                    type="text" placeholder="<?php echo display('email') ?>" id="email"
                                    value="<?php echo html_escape((!empty($intinfo->email)?$intinfo->email:null)) ?>">
                            </div>
                            <label for="phone" class="col-sm-2 col-form-label"><?php echo display('phone') ?> </label>
                            <div class="col-sm-4">
                                <input readonly name="phone" autocomplete="off" required class="form-control"
                                    type="number" placeholder="<?php echo display('phone') ?>" id="phone"
                                    value="<?php echo html_escape((!empty($intinfo->cust_phone)?$intinfo->cust_phone:null)) ?>">
                                <!-- <small class="form-text text-muted"><?php echo display('phone_message') ?></small> -->
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="" class="col-sm-2 col-form-label"><?php echo display('dob') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="dob" autocomplete="off" class="datepickers form-control"
                                    type="text" placeholder="<?php echo display('dob') ?>" id="dob"
                                    value="<?php echo html_escape((!empty($intinfo->dob)?$intinfo->dob:null)) ?>">
                            </div>
                            <label for="profession" class="col-sm-2 col-form-label"><?php echo display('profession') ?>
                            </label>
                            <div class="col-sm-4">
                                <input readonly name="profession" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo display('profession') ?>" id="profession"
                                    value="<?php echo html_escape((!empty($intinfo->profession)?$intinfo->profession:null)) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="fathername" class="col-sm-2 col-form-label"><?php echo display('father_name') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="fathername" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo display('father_name') ?>" id="fathername"
                                    value="<?php echo html_escape((!empty($intinfo->fathername)?$intinfo->fathername:null)) ?>">
                            </div>
                            <label for="anniversary" class="col-sm-2 col-form-label"><?php echo display('anniversary') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="anniversary" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo display('anniversary') ?>" id="anniversary"
                                    value="<?php echo html_escape((!empty($intinfo->anniversary)?$intinfo->anniversary:null)) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="city"
                                class="col-sm-2 col-form-label"><?php echo display('city') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="city" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo display('city') ?>" id="city"
                                    value="<?php echo html_escape((!empty($intinfo->city)?$intinfo->city:null)) ?>">
                            </div>
                            <label for="gender"
                                class="col-sm-2 col-form-label"><?php echo display('gender') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="gender" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo display('gender') ?>" id="gender"
                                    value="<?php echo html_escape((!empty($intinfo->gender)?$intinfo->gender:null)) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="contacttype" class="col-sm-2 col-form-label"><?php echo display('contact_type') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="contacttype" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo display('contact_type') ?>" id="contacttype"
                                    value="<?php echo html_escape((!empty($intinfo->contacttype)?$intinfo->contacttype:null)) ?>">
                            </div>
                            <label for="country" class="col-sm-2 col-form-label"><?php echo display('country') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="country" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo display('country') ?>" id="country"
                                    value="<?php echo html_escape((!empty($intinfo->country)?$intinfo->country:null)) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="zipcode" class="col-sm-2 col-form-label"><?php echo display('zipcode') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="zipcode" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo display('zipcode') ?>" id="zipcode"
                                    value="<?php echo html_escape((!empty($intinfo->zipcode)?$intinfo->zipcode:null)) ?>">
                            </div>
                            <label for="nationalitycon" class="col-sm-2 col-form-label"><?php echo display('nationality') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="nationalitycon" id="nationalitycon" autocomplete="off"
                                    class="form-control" type="text" placeholder="<?php echo display('nationality') ?>"
                                    value="<?php echo html_escape((!empty($intinfo->nationality)?$intinfo->nationality:null)) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="passport_no"
                                class="col-sm-2 col-form-label"><?php echo display('passport_no') ?> </label>
                            <div class="col-sm-4">
                                <input readonly name="passport_no" id="passport_no" autocomplete="off"
                                    class="form-control" type="text" placeholder="<?php echo display('passport_no') ?>"
                                    id="passport_no"
                                    value="<?php echo html_escape((!empty($intinfo->passport)?$intinfo->passport:null)) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="pitype"
                                class="col-sm-2 col-form-label"><?php echo "Photo Identity Type" ?></label>
                            <div class="col-sm-4">
                                <input readonly name="pitype" id="pitype" autocomplete="off" class="form-control"
                                    type="text" placeholder="<?php echo "Photo Identity Type" ?>" id="pitype"
                                    value="<?php echo html_escape((!empty($intinfo->pitype)?$intinfo->pitype:null)) ?>">
                            </div>
                            <label for="pid" class="col-sm-2 col-form-label"><?php echo "Photo Identity" ?> </label>
                            <div class="col-sm-4">
                                <input readonly name="pid" id="pid" autocomplete="off" class="form-control" type="text"
                                    placeholder="<?php echo "Photo Identity" ?>" id="pid"
                                    value="<?php echo html_escape((!empty($intinfo->pid)?$intinfo->pid:null)) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="pitype" class="col-sm-2 col-form-label"><?php echo "Photo Front" ?></label>
                            <div class="col-sm-4">
                                <img src="<?php echo html_escape(base_url(!empty($intinfo->imgfront)?$intinfo->imgfront:'assets/img/room-setting/room_images.png')); ?>"
                                    id="" class="img-thumbnail height_150_width_200px jsclrimg" />
                            </div>
                            <label for="pid" class="col-sm-2 col-form-label"><?php echo "Photo Back" ?> </label>
                            <div class="col-sm-4">
                                <img src="<?php echo html_escape(base_url(!empty($intinfo->imgback)?$intinfo->imgback:'assets/img/room-setting/room_images.png')); ?>"
                                    id="" class="img-thumbnail height_150_width_200px jsclrimg" />
                            </div>
                        </div>
                        <?php if($intinfo->isnationality=="foreigner"){ ?>
                        <div class="form-group row">
                            <label for="visa_reg_no"
                                class="col-sm-2 col-form-label"><?php echo display('visa_reg_no') ?></label>
                            <div class="col-sm-4">
                                <input readonly name="visa_reg_no" id="visa_reg_no" autocomplete="off"
                                    class="form-control" type="text" placeholder="<?php echo display('visa_reg_no') ?>"
                                    id="visa_reg_no"
                                    value="<?php echo html_escape((!empty($intinfo->visano)?$intinfo->visano:null)) ?>">
                            </div>
                            <label for="purpose" class="col-sm-2 col-form-label"><?php echo display('purpose') ?>
                            </label>
                            <div class="col-sm-4 radioheightalign">
                                <div class="form-check form-check-inline">
                                    <input readonly type="radio"
                                        <?php if($intinfo->purpose=="Tourist"){ echo "checked";}?>
                                        class="form-check-input readonly" name="purpose" id="materialInline3"
                                        value="Tourist">
                                    <label class="form-check-label"
                                        for="materialInline3"><?php echo display('tourist') ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input readonly type="radio"
                                        <?php if($intinfo->purpose=="Business"){ echo "checked";}?>
                                        class="form-check-input readonly" name="purpose" id="materialInline4"
                                        value="Business">
                                    <label class="form-check-label"
                                        for="materialInline4"><?php echo display('business') ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input readonly type="radio"
                                        <?php if($intinfo->purpose=="Official"){ echo "checked";}?>
                                        class="form-check-input readonly" name="purpose" id="materialInline5"
                                        value="Official">
                                    <label class="form-check-label"
                                        for="materialInline5"><?php echo display('official') ?></label>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="form-group row">
                            <label for="address" class="col-sm-2 col-form-label"><?php echo display('address') ?>
                            </label>
                            <div class="col-sm-4">
                                <textarea disabled name="address" cols="30" rows="6" autocomplete="off"
                                    class="form-control"
                                    placeholder="<?php echo display('address') ?>"><?php echo html_escape((!empty($intinfo->address)?$intinfo->address:null)) ?></textarea>
                            </div>
                            <label for="pitype" class="col-sm-2 col-form-label"><?php echo "Photo Guest" ?></label>
                            <div class="col-sm-4">
                                <img src="<?php echo html_escape(base_url(!empty($intinfo->imgguest)?$intinfo->imgguest:'assets/img/room-setting/room_images.png')); ?>"
                                    id="" class="img-thumbnail height_150_width_200px jsclrimg" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card mt-3">
  <div class="card-header"><h4>Stay History</h4></div>
  <div class="card-body">
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
        <thead>
            <tr>
            <th>Booking #</th>
            <th>Check-In</th>
            <th>Check-Out</th>
            <th>Room(s)</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $statusMap = [0=>'Pending',1=>'Cancelled',2=>'Confirmed',3=>'Reserved',4=>'Checked In',5=>'Checked Out'];
            $statusClass = [0=>'badge-warning',1=>'badge-danger',2=>'badge-info',3=>'badge-secondary',4=>'badge-primary',5=>'badge-success'];
            if (!empty($stayhistory)): foreach($stayhistory as $stay): 
                $sCode = (int)$stay->bookingstatus;
                $sLabel = isset($statusMap[$sCode]) ? $statusMap[$sCode] : 'Unknown';
                $sBadge = isset($statusClass[$sCode]) ? $statusClass[$sCode] : 'badge-secondary';
            ?>
            <tr>
            <td><?php echo html_escape($stay->booking_number); ?></td>
            <td><?php echo html_escape(date('d M Y', strtotime($stay->checkindate))); ?></td>
            <td><?php echo html_escape(date('d M Y', strtotime($stay->checkoutdate))); ?></td>
            <td><?php echo html_escape($stay->room_no); ?></td>
            <td><?php echo html_escape(number_format($stay->total_price, 2)); ?></td>
            <td><?php echo html_escape(number_format($stay->paid_amount, 2)); ?></td>
            <td><span class="badge <?php echo $sBadge; ?>"><?php echo $sLabel; ?></span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center">No stay history found.</td></tr>
            <?php endif; ?>
        </tbody>
        </table>
    </div>
  </div>
</div>