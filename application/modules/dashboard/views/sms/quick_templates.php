<div class="row">
    <div class="col-md-12">
        <div class="card panel-form">
            <div class="row">
                <div class="col-md-12">
                    <div class="card-header">
                        <h4><i class="ti-bookmark-alt"></i> Quick Templates</h4>
                        <div class="float-right">
                            <a href="<?php echo base_url('dashboard/sms_compose'); ?>" class="btn btn-success btn-sm">
                                <i class="ti-email"></i> Compose SMS
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <div class="portlet-body form">
                            <?php echo form_open('dashboard/sms_compose/save_quick_template', array('class' => 'form-horizontal','method'=>'post','role'=>'form')); ?>
                            
                            <div class="form-body">
                                <div class="form-group">
                                    <label class="col-md-4 control-label">Template Title <span class="text-danger">*</span>:</label>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" name="title" placeholder="e.g., Happy Birthday" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-4 control-label">Category <span class="text-danger">*</span>:</label>
                                    <div class="col-md-8">
                                        <select name="category" class="form-control" required>
                                            <option value="">-- Select Category --</option>
                                            <option value="birthday">Birthday</option>
                                            <option value="holiday">Holiday</option>
                                            <option value="promotion">Promotion</option>
                                            <option value="announcement">Announcement</option>
                                            <option value="reminder">Reminder</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-4 control-label">Message <span class="text-danger">*</span>:</label>
                                    <div class="col-md-8">
                                        <textarea name="message" class="form-control" rows="6" placeholder="Type your template message..." required></textarea>
                                        <small class="text-muted">
                                            Use placeholders: {firstname}, {lastname}, {fullname}, {hotelname}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <button type="submit" class="btn btn-success">
                                        <i class="ti-save"></i> Save Template
                                    </button>
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="ti-info-alt"></i> Template Tips</h6>
                            <ul class="small mb-0">
                                <li>Create reusable message templates</li>
                                <li>Use placeholders for personalization</li>
                                <li>Keep messages concise and clear</li>
                                <li>Categorize for easy access</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4>Template List</h4>
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

                <?php if($templates && count($templates) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th width="5%">#</th>
                                <th width="25%">Title</th>
                                <th width="15%">Category</th>
                                <th width="40%">Message</th>
                                <th width="15%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sl = 1;
                            foreach($templates as $template): 
                            ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo htmlspecialchars($template->title); ?></td>
                                <td>
                                    <?php 
                                    $category_badges = array(
                                        'birthday' => 'badge-success',
                                        'holiday' => 'badge-info',
                                        'promotion' => 'badge-warning',
                                        'announcement' => 'badge-primary',
                                        'reminder' => 'badge-secondary'
                                    );
                                    $badge_class = isset($category_badges[$template->category]) ? $category_badges[$template->category] : 'badge-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($template->category); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $preview = strlen($template->message) > 80 ? substr($template->message, 0, 80) . '...' : $template->message;
                                    echo htmlspecialchars($preview);
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo base_url('dashboard/sms_compose/delete_template/'.$template->id); ?>" 
                                       class="btn btn-xs btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this template?')" 
                                       data-toggle="tooltip" 
                                       title="Delete">
                                        <i class="ti-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="ti-info-alt"></i> No templates found. Create your first template above.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

