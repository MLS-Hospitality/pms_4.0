<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="ti-file"></i> SMS Details</h4>
                <div class="float-right">
                    <a href="<?php echo base_url('dashboard/sms_compose/history'); ?>" class="btn btn-secondary btn-sm">
                        <i class="ti-arrow-left"></i> Back to History
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5>Message Content</h5>
                        <div class="alert alert-light border">
                            <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($sms->message); ?></p>
                        </div>

                        <h5 class="mt-4">Recipients</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <?php 
                                if(!empty($sms->recipients)) {
                                    $recipient_ids = explode(',', $sms->recipients);
                                    $this->db->select('customerid, firstname, lastname, cust_phone');
                                    $this->db->from('customerinfo');
                                    $this->db->where_in('customerid', $recipient_ids);
                                    $customers = $this->db->get()->result();
                                    
                                    if($customers):
                                ?>
                                <thead>
                                    <tr class="bg-light">
                                        <th width="5%">#</th>
                                        <th width="40%">Customer Name</th>
                                        <th width="35%">Phone Number</th>
                                        <th width="20%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sl = 1;
                                    foreach($customers as $customer): 
                                    ?>
                                    <tr>
                                        <td><?php echo $sl++; ?></td>
                                        <td><?php echo $customer->firstname . ' ' . $customer->lastname; ?></td>
                                        <td><?php echo $customer->cust_phone; ?></td>
                                        <td>
                                            <span class="badge badge-success">Sent</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php 
                                    else:
                                        echo '<tbody><tr><td colspan="4" class="text-center">No recipient details available</td></tr></tbody>';
                                    endif;
                                } else {
                                    echo '<tbody><tr><td colspan="4" class="text-center">No recipients found</td></tr></tbody>';
                                }
                                ?>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <h5>SMS Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Recipient Type:</th>
                                <td>
                                    <?php 
                                    $type_badges = array(
                                        'all' => 'badge-primary',
                                        'birthday' => 'badge-success',
                                        'anniversary' => 'badge-info',
                                        'selected' => 'badge-warning'
                                    );
                                    $badge_class = isset($type_badges[$sms->recipient_type]) ? $type_badges[$sms->recipient_type] : 'badge-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($sms->recipient_type); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Total Recipients:</th>
                                <td><strong><?php echo $sms->recipient_count; ?></strong></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <?php 
                                    $status_badges = array(
                                        'sent' => 'badge-success',
                                        'partial' => 'badge-warning',
                                        'failed' => 'badge-danger',
                                        'pending' => 'badge-secondary'
                                    );
                                    $status_badge = isset($status_badges[$sms->status]) ? $status_badges[$sms->status] : 'badge-secondary';
                                    ?>
                                    <span class="badge <?php echo $status_badge; ?>">
                                        <?php echo ucfirst($sms->status); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Sent Date:</th>
                                <td><?php echo date('d M Y, h:i A', strtotime($sms->sent_date)); ?></td>
                            </tr>
                            <tr>
                                <th>Sender Name:</th>
                                <td><?php echo $sms->sender_name; ?></td>
                            </tr>
                            <tr>
                                <th>Gateway Response:</th>
                                <td><small><?php echo $sms->gateway_response; ?></small></td>
                            </tr>
                        </table>

                        <div class="mt-3">
                            <h5>Message Statistics</h5>
                            <div class="alert alert-info">
                                <strong>Characters:</strong> <?php echo strlen($sms->message); ?><br>
                                <strong>SMS Parts:</strong> <?php echo ceil(strlen($sms->message) / 160); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

