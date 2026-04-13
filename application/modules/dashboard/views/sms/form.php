 <div class="card">
     <div class="row">
         <div class="col-md-12">
             <div class="card-header">
                 <h4><?php echo display('sms_configuration') ?> - 80kobosms</h4>
                 <p class="text-muted">Configure your 80kobosms gateway. <a href="https://www.80kobosms.com/developers" target="_blank">View Documentation</a></p>
             </div>
         </div>
         <div class="col-md-12">
             <div class="card-body">
                 <?php echo form_open('dashboard/smsetting/update_sms_configuration', array('method'=>'post','role'=>'form')); ?>
                 <table width="100%" class="table table-striped table-bordered table-hover">
                     <thead>
                         <tr class="center bg-success">
                             <th><?php echo display('status');?></th>
                             <th><?php echo display('gateway');?> </th>
                             <th>Email <span class="text-danger">*</span></th>
                             <th><?php echo display('password');?> <span class="text-danger">*</span></th>
                             <th>Sender Name <span class="text-danger">*</span></th>
                             <th>Force DND (0 or 1)</th>
                         </tr>
                     </thead>

                     <tbody>
                         <?php  foreach ($gateways as $gateway) { ?>
                         <tr>
                             <input type="hidden" name="id[]" value="<?php echo html_escape($gateway['id']);?>">
                             <td><input type="radio" name="status[]"
                                     <?php echo html_escape($gateway['status'])==1?'checked':''?> class="form-control"
                                     value="<?php echo html_escape($gateway['id']);?>"></td>
                             <td><?php echo '<a target="_blank" href="'.$gateway['link'].'">'.$gateway['gateway'].'</a>'?>
                             </td>
                             <td>
                                 <input type="email" class="form-control" placeholder="your@email.com" 
                                        value="<?php echo html_escape($gateway['user_name']);?>" 
                                        name="user_name[]" 
                                        title="Your registered 80kobosms email address" 
                                        required>
                                 <small class="text-muted">Your 80kobosms registered email</small>
                             </td>
                             <td>
                                 <input type="text" class="form-control" placeholder="Your password"
                                        value="<?php echo html_escape($gateway['password'])?>" 
                                        name="password[]"
                                        title="Your 80kobosms account password"
                                        required>
                             </td>
                             <td>
                                 <input type="text" class="form-control" placeholder="Your sender name"
                                        value="<?php echo html_escape($gateway['sms_from'])?>" 
                                        name="sms_from[]"
                                        title="The sender name/ID that will appear on SMS"
                                        required>
                                 <small class="text-muted">Display name for SMS</small>
                             </td>
                             <td>
                                 <input type="text" class="form-control" placeholder="0 or 1"
                                        value="<?php echo html_escape($gateway['userid'])?>" 
                                        name="userid[]"
                                        title="Set to 1 to send to DND numbers, 0 otherwise"
                                        pattern="[0-1]">
                                 <small class="text-muted">0=No, 1=Yes</small>
                             </td>
                         </tr>
                         <?php } ?>
                     </tbody>
                 </table>
                 <div class="form-group">
                     <button type="submit" class="btn btn-success w-md m-b-5"><?php echo display('update');?></button>
                 </div>
                 </form>
             </div>
         </div>
     </div>
 </div>
 <script src="<?php echo MOD_URL.$module;?>/assets/js/script.js"></script>