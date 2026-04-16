<link rel="stylesheet" type="text/css"
      href="<?php echo base_url('application/modules/ordermanage/assets/css/posorder_v2.css'); ?>">

<script>
    var siteurl = "<?php echo site_url(); ?>";
    var baseurl = "<?php echo base_url(); ?>";
</script>

<input name="url" type="hidden" id="posurl"
    value="<?php echo site_url('ordermanage/order/getitemlist') ?>" />
<input name="url" type="hidden" id="productdata"
    value="<?php echo site_url('ordermanage/order/getitemdata') ?>" />
<input name="url" type="hidden" id="url"
    value="<?php echo site_url('ordermanage/order/itemlistselect') ?>" />
<input name="url" type="hidden" id="carturl"
    value="<?php echo site_url('ordermanage/order/posaddtocart') ?>" />
<input name="url" type="hidden" id="cartupdateturl"
    value="<?php echo site_url('ordermanage/order/poscartupdate') ?>" />
<input name="url" type="hidden" id="addonexsurl"
    value="<?php echo site_url('ordermanage/order/posaddonsmenu') ?>" />
<input name="url" type="hidden" id="removeurl"
    value="<?php echo site_url('ordermanage/order/removetocart') ?>" />
<input name="updateid" type="hidden" id="updateid" value="" />

<div class="posv2">
    <div class="posv2-header">
        <div>
            <h2>POS</h2>
            <p>Faster order flow and cleaner layout</p>
        </div>
        <a href="<?php echo site_url('dashboard/home') ?>" class="posv2-home-btn">
            <i class="fa fa-home"></i>
        </a>
    </div>

    <div class="posv2-layout">
        <div class="posv2-left">
            <div class="posv2-panel">
                <div class="posv2-panel-head">
                    <h3>Products</h3>
                </div>

                <form class="posv2-search" method="get"
                      action="<?php echo site_url('ordermanage/order/pos_invoice_v2') ?>">
                    <select id="product_name"
                            onchange="productsrcname()"
                            class="form-control dont-select-me basic-single search-field"
                            dir="ltr"
                            name="s">
                    </select>
                </form>

                <div class="posv2-categories">
                    <button type="button" class="posv2-cat active" onclick="getslcategory('')">
                        <?php echo display('all'); ?>
                    </button>

                    <?php if (!empty($categorylist)) {
                        $result = array_diff($categorylist, array("Select Food Category"));
                        foreach ($result as $key => $test) { ?>
                            <button type="button" class="posv2-cat" onclick="getslcategory(<?php echo $key; ?>)">
                                <?php echo $test; ?>
                            </button>
                    <?php }
                    } ?>
                </div>

                <div class="posv2-products">
                    <div class="row" id="product_search">
                        <?php foreach ($itemlist as $item) {
                            $item = (object)$item;
                            $this->db->select('*');
                            $this->db->from('menu_add_on');
                            $this->db->where('menu_id', $item->ProductsID);
                            $query = $this->db->get();
                            $getadons = $query->num_rows() > 0 ? 1 : 0;
                        ?>
                            <div class="col-6 col-sm-6 col-md-4 col-lg-4 mb-3">
                                <div class="posv2-product-card select_product">
                                    <div class="posv2-product-image">
                                        <img src="<?php echo base_url(!empty($item->small_thumb) ? $item->small_thumb : 'assets/img/icons/default.jpg'); ?>"
                                             alt="<?php echo $item->ProductName; ?>">
                                    </div>

                                    <input type="hidden" class="select_product_id" value="<?php echo $item->ProductsID; ?>">
                                    <input type="hidden" class="select_totalvarient" value="<?php echo $item->totalvarient; ?>">
                                    <input type="hidden" class="select_iscustomeqty" value="<?php echo $item->is_customqty; ?>">
                                    <input type="hidden" class="select_product_size" value="<?php echo $item->variantid; ?>">
                                    <input type="hidden" class="select_product_isgroup" value="<?php echo $item->isgroup; ?>">
                                    <input type="hidden" class="select_product_cat" value="<?php echo $item->CategoryID; ?>">
                                    <input type="hidden" class="select_varient_name" value="<?php echo $item->variantName; ?>">
                                    <input type="hidden" class="select_product_name" value="<?php echo $item->ProductName; if (!empty($item->itemnotes)) { echo ' -'.$item->itemnotes; } ?>">
                                    <input type="hidden" class="select_product_price" value="<?php echo $item->price; ?>">
                                    <input type="hidden" class="select_addons" value="<?php echo $getadons; ?>">

                                    <div class="posv2-product-info">
                                        <h4><?php echo $item->ProductName; ?></h4>
                                        <p><?php echo $item->variantName; ?></p>
                                        <strong>
                                            <?php if ($currency->position == 1) echo $currency->curr_icon; ?>
                                            <?php echo $item->price; ?>
                                            <?php if ($currency->position == 2) echo $currency->curr_icon; ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="posv2-right">
            <form action="<?php echo site_url('ordermanage/order/pos_order') ?>"
                  id="onlineordersubmit"
                  method="post"
                  enctype="multipart/form-data"
                  accept-charset="utf-8">

                <div class="posv2-panel">
                    <div class="posv2-panel-head">
                        <h3>Order Details</h3>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Customer</label>
                            <?php
                            $customerSource = isset($savedcustomerlist) ? $savedcustomerlist : $customerlist;
                            echo form_dropdown(
                                'customer_name',
                                $customerSource,
                                '',
                                'class="postform resizeselect form-control" id="customer_name"'
                            );
                            ?>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>Customer Type</label>
                            <?php
                            echo form_dropdown(
                                'ctypeid',
                                $curtomertype,
                                '',
                                'class="form-control" id="ctypeid" onchange="checkishotel()"'
                            );
                            ?>
                        </div>

                        <div class="col-md-4 form-group">
                            <label>Waiter</label>
                            <?php
                            $waiterkitchen = $this->session->userdata('id');
                            echo form_dropdown(
                                'waiter',
                                $waiterlist,
                                (!empty($waiterkitchen) ? $waiterkitchen : null),
                                'class="form-control" id="waiter"'
                            );
                            ?>
                        </div>

                        <div class="col-md-4 form-group">
                            <label>Table</label>
                            <?php
                            echo form_dropdown(
                                'tableid',
                                $tablelist,
                                '',
                                'class="postform resizeselect form-control" id="tableid" onchange="checktable()"'
                            );
                            ?>
                        </div>

                        <div class="col-md-4 form-group">
                            <label>Cooking Time</label>
                            <input name="cookedtime" type="text"
                                   class="form-control timepicker3"
                                   id="cookedtime"
                                   placeholder="00:00:00"
                                   autocomplete="off" />
                        </div>
                    </div>

                    <input type="hidden" id="order_date" name="order_date" value="<?php echo date('d-m-Y') ?>" />
                    <input type="hidden" id="bill_info" name="bill_info" value="1" />
                    <input type="hidden" id="card_type" name="card_type" value="4" />
                    <input type="hidden" id="isonline" name="isonline" value="0" />
                    <input type="hidden" id="assigncard_terminal" name="assigncard_terminal" value="" />
                    <input type="hidden" id="assignbank" name="assignbank" value="" />
                    <input type="hidden" id="assignlastdigit" name="assignlastdigit" value="" />
                    <input type="hidden" id="product_value" name="">
                </div>

                <div class="posv2-panel">
                    <div class="posv2-panel-head">
                        <h3>Cart</h3>
                    </div>
                    <div id="addfoodlist">
                        <?php $this->load->view('ordermanage/poscartlist'); ?>
                    </div>
                </div>

                <div class="posv2-panel posv2-summary">
                    <div class="posv2-summary-row">
                        <span>VAT / Tax</span>
                        <strong id="calvat">0</strong>
                    </div>

                    <div class="posv2-summary-row">
                        <span>Service Charge (%)</span>
                        <input type="text" id="service_charge" name="service_charge" class="form-control" value="0">
                    </div>

                    <div class="posv2-summary-row grand">
                        <span>Grand Total</span>
                        <strong id="caltotal">0</strong>
                    </div>

                    <div class="posv2-actions">
                        <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#exampleModal">
                            Calculator
                        </button>
                        <a href="<?php echo site_url('ordermanage/order/posclear') ?>" class="btn btn-danger">
                            Cancel
                        </a>
                        <button type="button" id="add_payment2" class="btn btn-primary" onclick="quickorder()">
                            Quick Order
                        </button>
                        <button type="button" id="add_payment" class="btn btn-success" onclick="placeorder()">
                            Place Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo base_url();?>assets/js/jquery.validate.min.js" type="text/javascript"></script>

<script src="<?php echo site_url('ordermanage/order/possettingjs') ?>" type="text/javascript"></script>
<script src="<?php echo site_url('ordermanage/order/quickorderjs') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('application/modules/ordermanage/assets/js/posorder_v2.js'); ?>" type="text/javascript"></script>