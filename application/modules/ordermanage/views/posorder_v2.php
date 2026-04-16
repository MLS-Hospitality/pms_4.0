<link rel="stylesheet" href="<?php echo base_url('application/modules/ordermanage/assets/css/posorder_v2.css'); ?>">

<div class="container-fluid">
    <div class="pos-v2-header">
        <h2>POS V2</h2>
        <p>Modernized and Better POS interface initial</p>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Products</div>
                <div class="card-body">
                    <p>Products area</p>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Order Panel</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Saved Customer</label>
                        <?php echo form_dropdown('customer_name', $savedcustomerlist, '', 'class="form-control"'); ?>
                    </div>

                    <div class="form-group">
                        <label>Customer Type</label>
                        <?php echo form_dropdown('ctypeid', $curtomertype, '', 'class="form-control"'); ?>
                    </div>

                    <div class="form-group">
                        <label>Waiter</label>
                        <?php echo form_dropdown('waiter', $waiterlist, '', 'class="form-control"'); ?>
                    </div>

                    <div class="form-group">
                        <label>Table</label>
                        <?php echo form_dropdown('tableid', $tablelist, '', 'class="form-control"'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>