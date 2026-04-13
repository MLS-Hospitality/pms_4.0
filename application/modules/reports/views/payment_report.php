<link type="text/css" href="<?php echo MOD_URL.$module;?>/assets/css/table.css">
<link type="text/css" href="<?php echo MOD_URL.$module;?>/assets/css/report_search.css">
<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h4><?php echo display('payment_report'); ?></h4>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-body">
                        <?php echo form_open('reports/report/payment_report',array('class' => 'form-inline', 'method' => 'get'))?>
                        <div class="form-group">
                            <label class="padding_right_5px col-form-label"
                                for="start_date"><?php echo display('start_date') ?>
                            </label>
                            <input type="text" name="start_date" value="<?php echo $this->input->get('start_date'); ?>" class="form-control datepickers"
                                id="start_date" placeholder="<?php echo display('start_date') ?>">
                        </div>

                        <div class="form-group">
                            <label class="padding_0_5px col-form-label" for="end_date"> <?php echo display('end_date') ?>
                            </label>
                            <input type="text" name="end_date" value="<?php echo $this->input->get('end_date'); ?>" class="form-control datepickers" id="end_date"
                                placeholder="<?php echo display('end_date') ?>">
                        </div>
                        &nbsp;<button type="submit" class="btn btn-success"><span class="text-white">
                                <?php echo display('search') ?></span></button>&nbsp;
                        <a href="<?php echo base_url('reports/report/payment_report'); ?>" class="btn btn-info"><span class="text-white">
                                <?php echo display('reset') ?></span></a>
                        <?php echo form_close()?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header">
                <h4><?php echo display('paid_payments') ?></h4>
                <small class="float-right">
                    <input type="button" class="btn btn-info button-print text-white"
                        name="btnPrint" id="btnPrint" value="Print"
                        onclick="printContent('printArea')" />
                </small>
            </div>
            <div class="row" id="printArea">
                <div class="col-sm-12 col-md-12">
                    <div class="row">
                        <div class="col-sm-12">
                            <?php if(!empty($payments)){?>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table width="100%" id="rexdatatable"
                                        class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th><?php echo display('sl_no') ?></th>
                                                <th><?php echo display('payment_date') ?></th>
                                                <th><?php echo display('invoice') ?></th>
                                                <th><?php echo display('booking_number') ?></th>
                                                <th><?php echo display('customer') ?></th>
                                                <th><?php echo display('check_in') ?></th>
                                                <th><?php echo display('check_out') ?></th>
                                                <th><?php echo display('status') ?></th>
                                                <th><?php echo display('payment_amount') ?></th>
                                                <th><?php echo display('action') ?></th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th colspan="8" style="text-align:right"><?php echo display('total') ?>:
                                                </th>
                                                <th style="text-align:right"> </th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                        <tbody>
                                            <?php
                                            $i = 0;
                                            $total_amount = 0;
                                            foreach($payments as $payment){
                                                $i++;
                                                $total_amount += floatval($payment->paymentamount ?? 0);
                                                $customer_name = '';
                                                if(!empty($payment->firstname) || !empty($payment->lastname)){
                                                    $customer_name = trim(($payment->firstname ?? '') . ' ' . ($payment->lastname ?? ''));
                                                }
                                                if(empty($customer_name)){
                                                    $customer_name = display('n/a');
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $i;?></td>
                                                <td><?php echo !empty($payment->paydate) ? date('Y-m-d', strtotime($payment->paydate)) : display('n/a');?></td>
                                                <td><?php echo html_escape($payment->invoice ?? display('n/a'));?></td>
                                                <td><?php echo html_escape($payment->booking_number ?? display('n/a'));?></td>
                                                <td><?php echo html_escape($customer_name);?></td>
                                                <td><?php echo !empty($payment->checkindate) ? html_escape($payment->checkindate) : display('n/a');?></td>
                                                <td><?php echo !empty($payment->checkoutdate) ? html_escape($payment->checkoutdate) : display('n/a');?></td>
                                                <td><?php
                                                    if(isset($payment->bookingstatus)){
                                                        if($payment->bookingstatus == 5){
                                                            echo display("checkout");
                                                        } else if($payment->bookingstatus == 0){
                                                            echo display('pending');
                                                        } else if($payment->bookingstatus == 1){
                                                            echo display("cancel");
                                                        } else if($payment->bookingstatus == 4){
                                                            echo display("checkin");
                                                        } else {
                                                            echo display('n/a');
                                                        }
                                                    } else {
                                                        echo display('n/a');
                                                    }
                                                ?></td>
                                                <td data-order="<?php echo floatval($payment->paymentamount ?? 0); ?>">
                                                    <?php if(!empty($currency) && $currency->position==1){echo $currency->curr_icon;}?>
                                                    <?php echo html_escape(number_format($payment->paymentamount ?? 0, 2));?>
                                                    <?php if(!empty($currency) && $currency->position==2){echo $currency->curr_icon;}?>
                                                </td>
                                                <td>
                                                    <?php if(!empty($payment->bookedid)){ ?>
                                                    <a href="<?php echo base_url("reports/booking-details/".html_escape($payment->bookedid)) ?>"
                                                        class="btn btn-success btn-sm" data-toggle="tooltip"
                                                        data-placement="top" data-original-title="Details"
                                                        title="Details"><i class="ti-eye"></i></a>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Hidden input with PHP-calculated total for JavaScript -->
                                <input type="hidden" id="php_total_amount" value="<?php echo floatval($total_amount); ?>">
                            </div>
                            <?php }
                            else { ?>
                            <div class="card-body text-center">
                                <span class="text-center">
                                    <svg class="mb-4" height="150pt" viewBox="0 0 496 496" width="150pt"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="m232 320h-96c-4.425781 0-8 3.574219-8 8v48c0 4.425781 3.574219 8 8 8h96c4.425781 0 8-3.574219 8-8v-48c0-4.425781-3.574219-8-8-8zm-8 48h-80v-32h80zm0 0" />
                                        <path
                                            d="m232 400h-96c-4.425781 0-8 3.574219-8 8v48c0 4.425781 3.574219 8 8 8h96c4.425781 0 8-3.574219 8-8v-48c0-4.425781-3.574219-8-8-8zm-8 48h-80v-32h80zm0 0" />
                                        <path
                                            d="m360 320h-96c-4.425781 0-8 3.574219-8 8v48c0 4.425781 3.574219 8 8 8h96c4.425781 0 8-3.574219 8-8v-48c0-4.425781-3.574219-8-8-8zm-8 48h-80v-32h80zm0 0" />
                                        <path
                                            d="m360 400h-96c-4.425781 0-8 3.574219-8 8v48c0 4.425781 3.574219 8 8 8h96c4.425781 0 8-3.574219 8-8v-48c0-4.425781-3.574219-8-8-8zm-8 48h-80v-32h80zm0 0" />
                                        <path d="m160 208h176v16h-176zm0 0" />
                                        <path d="m352 208h16v16h-16zm0 0" />
                                        <path d="m160 240h176v16h-176zm0 0" />
                                        <path d="m352 240h16v16h-16zm0 0" />
                                        <path d="m160 272h176v16h-176zm0 0" />
                                        <path d="m352 272h16v16h-16zm0 0" />
                                        <path d="m128 208h16v16h-16zm0 0" />
                                        <path d="m128 240h16v16h-16zm0 0" />
                                        <path d="m128 272h16v16h-16zm0 0" />
                                        <path
                                            d="m400 96c-26.472656 0-48 21.527344-48 48h16c0-17.648438 14.351562-32 32-32s32 14.351562 32 32h16c0-26.472656-21.527344-48-48-48zm0 0" />
                                        <path
                                            d="m372 72c6.617188 0 12-5.382812 12-12s-5.382812-12-12-12-12 5.382812-12 12 5.382812 12 12 12zm0-16c2.199219 0 4 1.792969 4 4s-1.800781 4-4 4-4-1.792969-4-4 1.800781-4 4-4zm0 0" />
                                        <path
                                            d="m428 72c6.617188 0 12-5.382812 12-12s-5.382812-12-12-12-12 5.382812-12 12 5.382812 12 12 12zm0-16c2.199219 0 4 1.792969 4 4s-1.800781 4-4 4-4-1.792969-4-4 1.800781-4 4-4zm0 0" />
                                        <path
                                            d="m400 0c-52.9375 0-96 43.0625-96 96 0 11.230469 2.039062 21.976562 5.601562 32h-123.203124c3.5625-10.023438 5.601562-20.769531 5.601562-32 0-52.9375-43.0625-96-96-96s-96 43.0625-96 96 43.0625 96 96 96v296c0 4.425781 3.574219 8 8 8h288c4.425781 0 8-3.574219 8-8v-296c52.9375 0 96-43.0625 96-96s-43.0625-96-96-96zm-128 144v16h-48v-16zm-256-48c0-44.113281 35.886719-80 80-80s80 35.886719 80 80-35.886719 80-80 80-80-35.886719-80-80zm368 384h-272v-289.449219c28.625-4.832031 52.945312-22.328125 67.007812-46.550781h28.992188v24c0 4.414062 3.574219 8 8 8h64c4.425781 0 8-3.585938 8-8v-24h28.992188c14.0625 24.230469 38.382812 41.71875 67.007812 46.550781zm16-304c-44.113281 0-80-35.886719-80-80s35.886719-80 80-80 80 35.886719 80 80-35.886719 80-80 80zm0 0" />
                                        <path
                                            d="m88 112h16c3.566406 0 6.710938-2.367188 7.695312-5.800781l16-56c1.058594-3.703125-.671874-7.632813-4.121093-9.351563l-8.839844-4.421875c-11.574219-5.785156-25.886719-5.785156-37.46875 0l-8.839844 4.414063c-3.449219 1.726562-5.179687 5.65625-4.121093 9.359375l16 56c.984374 3.433593 4.128906 5.800781 7.695312 5.800781zm-3.574219-61.265625c7.160157-3.574219 16-3.574219 23.160157 0l2.902343 1.457031-12.519531 43.808594h-3.9375l-12.511719-43.816406zm0 0" />
                                        <path d="m112 144c0 8.835938-7.164062 16-16 16s-16-7.164062-16-16 7.164062-16 16-16 16 7.164062 16 16zm0 0" />
                                    </svg><br />
                                    <?php echo display('no_result_found'); ?>
                                </span>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printContent(el){
    var restorepage = document.body.innerHTML;
    var printcontent = document.getElementById(el).innerHTML;
    document.body.innerHTML = printcontent;
    window.print();
    document.body.innerHTML = restorepage;
}

<?php if(!empty($payments)){ ?>
$(document).ready(function() {
    'use strict';
    // Check if DataTable is already initialized and destroy it first
    // This prevents the "Cannot reinitialise DataTable" error
    if ($.fn.DataTable.isDataTable('#rexdatatable')) {
        $('#rexdatatable').DataTable().destroy();
    }

    $("#rexdatatable").DataTable({
        dom: "<'row m-0'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
        lengthMenu: [
            [10, 25, 50, -1],
            [10, 25, 50, "All"]
        ],
        buttons: [{
                extend: "copy",
                className: "btn-sm prints",
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: "csv",
                title: "Payment Report",
                className: "btn-sm prints",
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: "pdf",
                title: "Payment Report",
                className: "btn-sm prints",
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: "print",
                className: "btn-sm prints",
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend:"colvis",
                className:"btn-sm prints"
            }
        ],
         "footerCallback": function (row, data, start, end, display) {
             var api = this.api();

             // Get PHP-calculated total (server-side calculation is more reliable)
             var phpTotal = parseFloat($('#php_total_amount').val()) || 0;
             var total = phpTotal;

             // If there's a search/filter active, recalculate from filtered rows
             var searchValue = api.search();
             if (searchValue) {
                 total = 0;
                 api.rows({ search: 'applied' }).every(function() {
                     var rowNode = this.node();
                     var amountCell = $(rowNode).find('td:eq(8)'); // Column 8 (Payment Amount)
                     if (amountCell.length) {
                         var orderValue = amountCell.attr('data-order');
                         if (orderValue) {
                             total += parseFloat(orderValue) || 0;
                         }
                     }
                 });
             }

            // Get currency info from first cell
            var currencySymbol = '';
            var currencyPosition = 1;
            var firstCell = $(api.column(8).nodes()).first().find('td').first();
            if (firstCell.length) {
                var cellText = firstCell.text();
                var match = cellText.match(/[₦$€£¥₹]/);
                if (match) {
                    currencySymbol = match[0];
                    var firstDigit = cellText.search(/\d/);
                    var symbolIndex = cellText.indexOf(currencySymbol);
                    currencyPosition = (symbolIndex >= 0 && symbolIndex < firstDigit) ? 1 : 2;
                }
            }

             // Format the total
             var formattedTotal = parseFloat(total).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            // Add currency symbol
            if (currencySymbol) {
                formattedTotal = (currencyPosition == 1) ? currencySymbol + ' ' + formattedTotal : formattedTotal + ' ' + currencySymbol;
            }

            // Update footer in the correct column
            $(api.column(8).footer()).html(formattedTotal);
        }
    });

    $('.dataTables_filter').addClass('');
    $('.dataTables_filter label').addClass('search__inner');
    $('.dataTables_filter input').addClass('search__text');
});
<?php } ?>
</script>

