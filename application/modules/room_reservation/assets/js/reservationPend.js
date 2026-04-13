$(document).ready(function() {
    'use strict';
    var base = $('#base_url').val();
    var csrf = $('#csrf_token').val();

    var table = $("#bookingdetails").DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            url: base + "/room_reservation/room_reservation/bookingdatatablepend",
            type: "POST",
            data: {'csrf_test_name': csrf},
            dataSrc: function(json) {
                console.log("Server response:", json); // <-- Debug: check JSON
                return json.data || [];
            },
            error: function(xhr, error, thrown) {
                console.error("AJAX error:", xhr.responseText);
                $("#employee_grid_processing").css("display", "none");
            }
        },
        dom: "<'row m-0'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
        order: [[1, "desc"]],
        lengthMenu: [[10,25,50,-1],[10,25,50,"All"]],
        buttons: [
            { extend: "copy", className: "btn-sm prints", exportOptions: { columns: ':visible' } },
            { extend: "csv", title: "Booking List", className: "btn-sm prints", exportOptions: { columns: ':visible' } },
            { extend: "pdf", title: "Booking List", className: "btn-sm prints", exportOptions: { columns: ':visible' } },
            { extend: "print", className: "btn-sm prints", exportOptions: { columns: ':visible' } },
            { extend: "colvis", className: "btn-sm prints" }
        ],
        "initComplete": function(settings, json) {
            $('.dataTables_filter').addClass('search');  
            $('.dataTables_filter label').addClass('search__inner');  
            $('.dataTables_filter input').addClass('search__text');    
            $('[data-toggle="tooltip"]').tooltip(); 
        }
    });
});
