<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="ti-time"></i> <?php echo display('sms_history') ?></h4>
                <div class="float-right">
                    <a href="<?php echo base_url('dashboard/sms_compose'); ?>" class="btn btn-success btn-sm">
                        <i class="ti-email"></i> Compose New SMS
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if($this->session->flashdata('message')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $this->session->flashdata('message'); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if($history && is_array($history) && count($history) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th width="5%">#</th>
                                <th width="30%">Message Preview</th>
                                <th width="15%">Recipient Type</th>
                                <th width="10%">Recipients</th>
                                <th width="10%">Status</th>
                                <th width="15%">Sent Date</th>
                                <th width="15%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sl = 1;
                            foreach($history as $sms): 
                            ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td>
                                    <?php 
                                    $preview = strlen($sms->message) > 80 ? substr($sms->message, 0, 80) . '...' : $sms->message;
                                    echo htmlspecialchars($preview);
                                    ?>
                                </td>
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
                                <td class="text-center">
                                    <span class="badge badge-pill badge-primary"><?php echo $sms->recipient_count; ?></span>
                                </td>
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
                                <td>
                                    <small>
                                        <?php echo date('d M Y', strtotime($sms->sent_date)); ?><br>
                                        <?php echo date('h:i A', strtotime($sms->sent_date)); ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="<?php echo base_url('dashboard/sms_compose/view_sms/'.$sms->id); ?>" 
                                       class="btn btn-xs btn-info" 
                                       data-toggle="tooltip" 
                                       title="View Details">
                                        <i class="ti-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if(isset($pagination) && !empty($pagination)): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <nav aria-label="Page navigation">
                            <?php echo $pagination; ?>
                        </nav>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="ti-info-alt"></i> No SMS history found. 
                    <a href="<?php echo base_url('dashboard/sms_compose'); ?>" class="alert-link">Send your first SMS</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.badge {
    font-size: 11px;
    padding: 4px 8px;
}
.table td {
    vertical-align: middle;
}
.table small {
    font-size: 11px;
}
</style>

<script>
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

