<div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel">
               
                <div class="panel-body">
                <?php echo form_open('customer/customer_info/updateguest'); ?>
                    <?php echo form_hidden('guestid', (!empty($intinfo->otherguest_id)?$intinfo->otherguest_id:null)) ?>
                        
                        <div class="form-group row">
                            <label for="guestname" class="col-sm-4 col-form-label"><?php echo display("guest") ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input name="guestname" class="form-control" type="text" placeholder="<?php echo display("guest") ?>" id="guestname" value="<?php echo html_escape((!empty($intinfo->guestname)?$intinfo->guestname:null)) ?>" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="gender" class="col-sm-4 col-form-label"><?php echo display("gender") ?></label>
                            <div class="col-sm-8">
                                <select name="gender" class="form-control">
                                    <option value="Male" <?php echo ($intinfo->gender == 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?php echo ($intinfo->gender == 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Others" <?php echo ($intinfo->gender == 'Others') ? 'selected' : '' ?>>Others</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="mobile" class="col-sm-4 col-form-label"><?php echo display("mobile") ?></label>
                            <div class="col-sm-8">
                                <input name="mobile" class="form-control" type="text" placeholder="<?php echo display("mobile") ?>" id="mobile" value="<?php echo html_escape((!empty($intinfo->mobile)?$intinfo->mobile:null)) ?>">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="email" class="col-sm-4 col-form-label"><?php echo display("email") ?></label>
                            <div class="col-sm-8">
                                <input name="email" class="form-control" type="email" placeholder="<?php echo display("email") ?>" id="email" value="<?php echo html_escape((!empty($intinfo->email)?$intinfo->email:null)) ?>">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="photo_id_type" class="col-sm-4 col-form-label"><?php echo display("photo_id_type") ?></label>
                            <div class="col-sm-8">
                                <input name="photo_id_type" class="form-control" type="text" placeholder="<?php echo display("photo_id_type") ?>" id="photo_id_type" value="<?php echo html_escape((!empty($intinfo->photo_id_type)?$intinfo->photo_id_type:null)) ?>">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="photo_id" class="col-sm-4 col-form-label"><?php echo display("photo_id") ?></label>
                            <div class="col-sm-8">
                                <input name="photo_id" class="form-control" type="text" placeholder="<?php echo display("photo_id") ?>" id="photo_id" value="<?php echo html_escape((!empty($intinfo->photo_id)?$intinfo->photo_id:null)) ?>">
                            </div>
                        </div>
  						
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-success w-md m-b-5"><?php echo display('update') ?></button>
                        </div>
                    <?php echo form_close() ?>

                </div>  
            </div>
        </div>
    </div>