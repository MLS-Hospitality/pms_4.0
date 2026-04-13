<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="ti-email"></i> <?php echo display('compose_sms') ?></h4>
                <div class="float-right">
                    <a href="<?php echo base_url('dashboard/sms_compose/test'); ?>" class="btn btn-warning btn-sm">
                        <i class="ti-settings"></i> Test API
                    </a>
                    <a href="<?php echo base_url('dashboard/sms_compose/history'); ?>" class="btn btn-info btn-sm">
                        <i class="ti-time"></i> <?php echo display('sms_history') ?>
                    </a>
                    <a href="<?php echo base_url('dashboard/sms_compose/quick_templates'); ?>" class="btn btn-primary btn-sm">
                        <i class="ti-bookmark-alt"></i> Quick Templates
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h3 class="mb-0"><?php echo $customer_count; ?></h3>
                                <p class="mb-0"><i class="ti-user"></i> Total Customers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h3 class="mb-0"><?php echo $birthday_count; ?></h3>
                                <p class="mb-0"><i class="ti-gift"></i> Birthdays Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h3 class="mb-0"><?php echo $anniversary_count; ?></h3>
                                <p class="mb-0"><i class="ti-heart"></i> Anniversaries Today</p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php echo form_open('', array('id' => 'sms_compose_form', 'method'=>'post','role'=>'form')); ?>
                
                <div class="row">
                    <!-- Left Column - Compose Form -->
                    <div class="col-md-8">
                        <!-- Quick Templates -->
                        <div class="form-group">
                            <label for="quick_template">Quick Templates <small class="text-muted">(Optional)</small></label>
                            <select class="form-control" id="quick_template">
                                <option value="">-- Select a Template --</option>
                                <?php if($quick_templates): ?>
                                    <?php foreach($quick_templates as $template): ?>
                                        <option value="<?php echo $template->id; ?>" data-message="<?php echo htmlspecialchars($template->message); ?>">
                                            <?php echo $template->title; ?> (<?php echo ucfirst($template->category); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Select a pre-defined template to quickly compose your message</small>
                        </div>

                        <!-- Recipient Type -->
                        <div class="form-group">
                            <label for="recipient_type">Send To <span class="text-danger">*</span></label>
                            <select class="form-control" id="recipient_type" name="recipient_type" required>
                                <option value="">-- Select Recipients --</option>
                                <option value="all">All Customers (<?php echo $customer_count; ?>)</option>
                                <?php if($birthday_count > 0): ?>
                                <option value="birthday">Birthday Customers Today (<?php echo $birthday_count; ?>)</option>
                                <?php endif; ?>
                                <?php if($anniversary_count > 0): ?>
                                <option value="anniversary">Anniversary Customers Today (<?php echo $anniversary_count; ?>)</option>
                                <?php endif; ?>
                                <option value="selected">Selected Customers</option>
                            </select>
                        </div>

                        <!-- Selected Customers (Hidden by default) -->
                        <div class="form-group" id="customer_selection_div" style="display:none;">
                            <label for="customer_select">Select Customers <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="customer_select" name="customer_ids[]" multiple="multiple" style="width: 100%;">
                                <?php if($customers): ?>
                                    <?php foreach($customers as $customer): ?>
                                        <option value="<?php echo $customer->customerid; ?>">
                                            <?php echo $customer->firstname . ' ' . $customer->lastname; ?> - <?php echo $customer->cust_phone; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple customers</small>
                        </div>

                        <!-- Message Text Area -->
                        <div class="form-group">
                            <label for="sms_message">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="sms_message" name="message" rows="8" required placeholder="Type your message here..."></textarea>
                            <div class="mt-2">
                                <span class="badge badge-info" id="char_count">0 characters</span>
                                <span class="badge badge-secondary" id="sms_count">0 SMS</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-group">
                            <button type="button" class="btn btn-success btn-lg" id="send_sms_btn">
                                <i class="ti-mobile"></i> Send SMS
                            </button>
                            <button type="button" class="btn btn-secondary" id="save_draft_btn">
                                <i class="ti-save"></i> Save as Draft
                            </button>
                            <button type="reset" class="btn btn-warning">
                                <i class="ti-reload"></i> Clear
                            </button>
                        </div>
                    </div>

                    <!-- Right Column - Instructions -->
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title"><i class="ti-info-alt"></i> Instructions</h5>
                                
                                <h6 class="mt-3">Available Placeholders:</h6>
                                <ul class="list-unstyled">
                                    <li><code>{firstname}</code> - Customer's first name</li>
                                    <li><code>{lastname}</code> - Customer's last name</li>
                                    <li><code>{fullname}</code> - Full name</li>
                                    <li><code>{hotelname}</code> - Hotel name</li>
                                    <li><code>{phone}</code> - Hotel phone</li>
                                    <li><code>{email}</code> - Hotel email</li>
                                </ul>

                                <h6 class="mt-3">Example Messages:</h6>
                                <div class="alert alert-info">
                                    <strong>Birthday:</strong><br>
                                    Happy Birthday {firstname}! Enjoy a special discount on your next stay. - {hotelname}
                                </div>
                                
                                <div class="alert alert-success">
                                    <strong>Holiday:</strong><br>
                                    Merry Christmas {fullname}! Best wishes from {hotelname}
                                </div>

                                <h6 class="mt-3">Tips:</h6>
                                <ul class="small">
                                    <li>Keep messages concise and clear</li>
                                    <li>160 characters = 1 SMS</li>
                                    <li>Use placeholders for personalization</li>
                                    <li>Test with a small group first</li>
                                    <li>Check your SMS balance</li>
                                </ul>

                                <div class="alert alert-warning mt-3">
                                    <i class="ti-alert"></i> <strong>Note:</strong> Ensure your 80kobosms account has sufficient balance before sending.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sending SMS...</h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3">Please wait while we send your messages...</p>
                <div class="progress mt-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">SMS Sending Result</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="result_message">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="<?php echo base_url('dashboard/sms_compose/history'); ?>" class="btn btn-primary">View History</a>
            </div>
        </div>
    </div>
</div>

<script>
// Define API endpoints using CodeIgniter helpers
var SMS_SEND_URL = '<?php echo base_url("dashboard/sms_compose/send_sms"); ?>';
var SMS_DRAFT_URL = '<?php echo base_url("dashboard/sms_compose/save_draft"); ?>';
var SMS_BASE_URL = '<?php echo base_url(); ?>';

// Debug: Log URLs
console.log('SMS Send URL:', SMS_SEND_URL);
console.log('SMS Draft URL:', SMS_DRAFT_URL);
console.log('Base URL:', SMS_BASE_URL);
console.log('Module URL:', '<?php echo MOD_URL.$module;?>');
</script>
<script src="<?php echo MOD_URL.$module;?>/assets/js/sms_compose_v2.js"></script>
<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.badge {
    font-size: 12px;
    padding: 5px 10px;
}
code {
    color: #e83e8c;
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
}
.alert {
    font-size: 12px;
}
.card-body h3 {
    font-size: 2rem;
    font-weight: bold;
}
.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>

