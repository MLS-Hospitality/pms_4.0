function checkdshift(id) {
    "use strict";

    var base = $('#base_url').val();
    var csrf = $('#csrf_token').val();

    var chksh_id = $('input[name="shiftchk"]:checked').val();

    $.ajax({
        type: "POST",
        dataType: "json",
        url: base + "duty_roster/Shift_management/load_checkedshift",
        data: {
            csrf_test_name: csrf,
            chksh_id: chksh_id,
        },
        success: function(data) {
            $('#emp_startroster_time').val(data.shift_start).trigger('change');
            $('#emp_endroster_time').val(data.shift_end).trigger('change');
            
            var start_date = $('#emp_startroster_date').val();
            var end_date = start_date;
            if (data.shift_end < data.shift_start) {
                var date = new Date(start_date);
                date.setDate(date.getDate() + 1);
                var yr = date.getFullYear();
                var month = (date.getMonth() + 1) < 10 ? '0' + (date.getMonth() + 1) : (date.getMonth() + 1);
                var day = date.getDate() < 10 ? '0' + date.getDate() : date.getDate();
                end_date = yr + '-' + month + '-' + day;
            }
            $('#emp_endroster_date').val(end_date);
        }
    });

}
$('#emp_startroster_time').change(function(){

    "use strict";
    
    var base = $('#base_url').val();
    var csrf = $('#csrf_token').val();

    var cng_date = $('#emp_startroster_date').val();
    var chksh_id = $('input[name="shiftchk"]:checked').val();

    $.ajax({
        type: "POST",
        dataType: "json",
        url: base + "duty_roster/Shift_management/load_checkedroster",
        data: {
            csrf_test_name: csrf,
            chksh_id: chksh_id,
            cng_date: cng_date,
        },
        success: function(data) {
            
              $('#roster_id').val(data.roster_id);

        }
    });

});