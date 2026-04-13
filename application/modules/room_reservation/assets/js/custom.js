"use strict";

var base_url = $("#base_url").val();

/* ========================================
   IMAGE UPLOAD HANDLER (Reusable)
======================================== */

function handleImageUpload(input, previewId, filenameId, hiddenInputId) {
    var path = input.value;
    var extension = path.split('.').pop().toLowerCase();

    if (["jpg", "jpeg", "png", "gif", "svg"].includes(extension)) {
        document.getElementById(previewId).src = window.URL.createObjectURL(input.files[0]);

        var filename = path.replace(/^.*[\\\/]/, '').split('.').slice(0, -1).join('.');
        $("#" + filenameId).html(filename);

        var fd = new FormData();
        fd.append('img', input.files[0]);
        fd.append('csrf_test_name', $('#csrf_token').val());

        $.ajax({
            url: base_url + "room_reservation/room_reservation/imageupload",
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            success: function (r) {
                $("#" + hiddenInputId).val(r);
            },
            error: function () {
                swal("Error", "Image upload failed.", "error");
            }
        });
    } else {
        swal({
            title: "Failed",
            text: "File not supported. Kindly upload a valid image format.",
            type: "error",
            confirmButtonColor: "#28a745",
            confirmButtonText: "Ok"
        });
    }
}

function fileValueOne(v)   { handleImageUpload(v, 'image-preview',  'filename',  'imgffront');  }
function fileValuesTwo(v)  { handleImageUpload(v, 'image-preview2', 'filename2', 'imgbback');  }
function fileValuesThree(v){ handleImageUpload(v, 'image-preview3','filename3','imggguest'); }

/* ========================================
   DATEPICKER INITIALIZER
======================================== */

function initDatePicker(selector, customOptions = {}) {
    $(selector).each(function () {
        const $input = $(this);

        if ($input.data('daterangepicker')) {
            $input.data('daterangepicker').remove();
        }

        const $modal = $input.closest('.modal');
        const parentEl = $modal.length 
            ? $modal.find('.modal-body, .modal-content').first() 
            : 'body';

        const baseOptions = {
            parentEl: parentEl,
            singleDatePicker: true,
            showDropdowns: true,
            timePicker: true,
            autoUpdateInput: true,
            locale: { format: 'YYYY-MM-DD HH:mm' }
        };

        const options = $.extend(true, baseOptions, customOptions);

        $input.daterangepicker(options);

        $input.on('show.daterangepicker', function (ev, picker) {
            setTimeout(() => {
                picker.updateCalendars();
                picker.updateFormInputs();
                picker.container.css('z-index', 9999);
            }, 50);
        });
    });
}

/* ========================================
   CUSTOMER DATA STORAGE + SAVE BUTTON CONTROL
======================================== */

// Check if occupants already exists, if not, initialize it.
window.occupants = window.occupants || [];

function addCustomerToTable(customer) {
    const row = `
        <tr>
            <td>${occupants.length + 1}</td>
            <td>${customer.title || ''} ${customer.firstname} ${customer.lastname || ''}</td>
            <td>${customer.mobileNo || ''}</td>
            <td class="text-right pr-0">
                <button type="button" class="btn btn-danger-soft btn-xs remove-occupant" data-index="${occupants.length}">
                    <i class="far fa-trash-alt"></i>
                </button>
            </td>
        </tr>`;

    $(".customerdetail tbody").append(row);

    // ────────────────────────────────────────────────
    // Existing ones (keep them)
    $("#alluser").val(occupants.map(c => c.firstname + ' ' + (c.lastname || '')).join(','));
    $("#allmobile").val(occupants.map(c => c.mobileNo || '').join(','));
    $("#allemail").val(occupants.map(c => c.email || '').join(','));
    $("#allnationality").val(occupants.map(c => c.nationality || '').join(','));
    $("#alldob").val(occupants.map(c => c.dob || '').join(','));
    $("#allimgfront").val(occupants.map(c => c.imgffront || '').join(','));
    $("#allimgback").val(occupants.map(c => c.imgbback || '').join(','));
    $("#allimgguest").val(occupants.map(c => c.imggguest || '').join(','));

    // ────────────────────────────────────────────────
    // NEW — add these (the missing critical ones)
    $("#allstate").val(occupants.map(c => c.state || '').join(','));
    $("#allcountry").val(occupants.map(c => c.country || '').join(','));
    $("#allcity").val(occupants.map(c => c.city || '').join(','));
    $("#allzipcode").val(occupants.map(c => c.zipcode || '').join(','));
    $("#alladdress").val(occupants.map(c => c.address || '').join(','));
    $("#allcontacttype").val(occupants.map(c => c.contacttype || '').join(','));
    $("#allgender").val(occupants.map(c => c.gender || '').join(','));
    $("#allfather").val(occupants.map(c => c.fathername || '').join(','));
    $("#allpitype").val(occupants.map(c => c.pitype || '').join(','));
    $("#allpid").val(occupants.map(c => c.pid || '').join(','));
    $("#allanniversary").val(occupants.map(c => c.anniversary || '').join(','));
    $("#alloccupation").val(occupants.map(c => c.occupation || '').join(','));
    $("#alllastname").val(occupants.map(c => c.lastname || '').join(','));
    $("#allvip").val(occupants.map(c => c.vip || 'No').join(','));
    $("#allcomments").val(occupants.map(c => c.comments || '').join(','));

    toggleSaveButton();
}

function toggleSaveButton() {
    $("#bookingsave").prop('disabled', occupants.length === 0);
}

/* ========================================
   DOCUMENT READY
======================================== */

$(document).ready(function () {

    $('.selectpicker').selectpicker();
    $('.testselect2').SumoSelect({
        search: true,
        placeholder: 'Room Select',
        csvDispCount: 5
    });

    /* ===== DATE INITIALIZATION ===== */
    var nowDate = new Date();
    var today = new Date(nowDate.getFullYear(), nowDate.getMonth(), nowDate.getDate(), 0, 0, 0, 0);

    var intime = $("#intime").val() || '';
    var out = $("#outtime").val() || '';
    var findate = $("#findate").val() || '';

    var utime = new Date(out);
    utime.setDate(utime.getDate() + 1);
    var outtime = moment(utime).format("YYYY-MM-DD HH:mm");

    $("#datefilter1").val(intime);
    $("#datefilter2").val(outtime);
    $("#from_date1, #from_date2").val(intime);
    $("#to_date1, #to_date2").val(outtime);

    initDatePicker('.datefilter', { minDate: today, maxDate: findate });
    initDatePicker('.datefilter3', { minDate: intime, maxDate: outtime });
    initDatePicker('.datefilter4', { minDate: intime, maxDate: outtime });

    initDatePicker('.datefilter2', {
        timePicker24Hour: false,
        timePickerIncrement: 60,
        autoUpdateInput: true,
        locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD hh:mm A' }
    });

    $(document).on('apply.daterangepicker', '.datefilter2', function (ev, picker) {
        const selected = picker.startDate.clone().hour(12).minute(0);
        $(this).val(selected.format('YYYY-MM-DD hh:mm A'));
        $(this).trigger('change');
    });

    $(document).on('show.daterangepicker', '.datefilter2', function (ev, picker) {
        picker.startDate.hour(12).minute(0);
        picker.endDate.hour(12).minute(0);
        setTimeout(() => {
            picker.container.find('.hourselect, .minuteselect, .ampmselect').prop('disabled', true);
        }, 100);
    });

    $(document).on('cancel.daterangepicker', '.datefilter2', function () {
        $(this).val('');
    });

    /* ===== DYNAMIC ROWS ===== */
    var counter = 0;

    $("#addrow").on("click", function () {
        var newRow = $("<tr>");
        newRow.html(`
            <td class="border-0 pl-0"><input type="text" class="form-control form-control-xs datefilter2"/></td>
            <td class="border-0"><input type="text" class="form-control form-control-xs datefilter2"/></td>
            <td class="border-0"><div class="d-flex">
                <input type="number" class="form-control form-control-xs" value="0">
                <div class="dropdown dropdown-custom ml-1">
                    <button class="btn btn-inverse-soft btn-xs dropdown-toggle" type="button" data-toggle="dropdown">Tariff</button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr><td>Base Rent</td><td>0</td></tr>
                                <tr><td>Net Rent</td><td>0</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div></td>
            <td class="border-0 pr-0 text-right">
                <button type="button" class="ibtnDel btn btn-danger-soft btn-xs"><i class="far fa-times-circle"></i></button>
            </td>
        `);

        $("table.order-list").append(newRow);

        initDatePicker(newRow.find('.datefilter2'), {
            timePicker24Hour: false,
            timePickerIncrement: 60,
            autoUpdateInput: true,
            locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD hh:mm A' }
        });

        counter++;
    });

    $("table.order-list").on("click", ".ibtnDel", function () {
        $(this).closest("tr").remove();
        counter--;
    });

    /* ===== SAVE CUSTOMER FROM MODAL ===== */

    $("#addcustomer").on("click", function () {
        const customer = {
            title: $("#title").val(),
            firstname: $("#firstname").val().trim(),
            lastname: $("#lastname").val().trim(),
            fathername: $("#fathername").val().trim(),
            mobileNo: $("#mobileNo").val().trim(),
            email: $("#email").val().trim(),
            nationality: $("#nationality").val(),
            country: $("#country").val(),
            country_code: $("#code").val(),
            occupation: $("#occupation").val(),
            dob: $("#dob").val(),
            anniversary: $("#anniversary").val(),
            gender: $("input[name='customRadioInline']:checked").val() || '',
            vip: $("#vip").is(":checked") ? 'Yes' : 'No',
            imgffront: $("#imgffront").val(),
            imgbback: $("#imgbback").val(),
            imggguest: $("#imggguest").val(),
            contacttype: $("#contacttype").val(),
            address: $("#address").val(),
            state: $("#state").val(),
            city: $("#city").val(),
            zipcode: $("#zipcode").val(),
            comments: $("#comments").val()
        };

        if (!customer.firstname || !customer.mobileNo) {
            swal("Error", "First name and mobile number are required.", "error");
            return;
        }

        occupants.push(customer);
        addCustomerToTable(customer);

        $('#exampleModal input, #exampleModal select, #exampleModal textarea').val('');
        $('#exampleModal input[type="checkbox"]').prop('checked', false);
        $('#exampleModal .image-preview').attr('src', '/assets/img/proof_icon.png'); // reset preview
        $('#exampleModal .filename').html('Drag and drop');

        $('#exampleModal').modal('hide');

        swal("Success", "Customer added successfully.", "success");
    });

   $(document).on("click", ".remove-occupant", function () {
    const index = $(this).data('index');

    // Remove the occupant from the array
    occupants.splice(index, 1);

    // Remove the row from the table
    $(this).closest("tr").remove();

    // Re-index the remaining rows (SL numbers and data-index)
    $(".customerdetail tbody tr").each(function (i) {
        $(this).find("td:first").text(i + 1);
        $(this).find(".remove-occupant").data('index', i);
    });

    // ────────────────────────────────────────────────
    // Update ALL relevant hidden fields (same as in addCustomerToTable)
    $("#alluser").val(occupants.map(c => c.firstname + ' ' + (c.lastname || '')).join(','));
    $("#allmobile").val(occupants.map(c => c.mobileNo || '').join(','));
    $("#allemail").val(occupants.map(c => c.email || '').join(','));
    $("#allnationality").val(occupants.map(c => c.nationality || '').join(','));
    $("#alldob").val(occupants.map(c => c.dob || '').join(','));
    $("#allimgfront").val(occupants.map(c => c.imgffront || '').join(','));
    $("#allimgback").val(occupants.map(c => c.imgbback || '').join(','));
    $("#allimgguest").val(occupants.map(c => c.imggguest || '').join(','));

    // ────────────────────────────────────────────────
    // Missing fields — now added
    $("#allstate").val(occupants.map(c => c.state || '').join(','));
    $("#allcountry").val(occupants.map(c => c.country || '').join(','));
    $("#allcity").val(occupants.map(c => c.city || '').join(','));
    $("#allzipcode").val(occupants.map(c => c.zipcode || '').join(','));
    $("#alladdress").val(occupants.map(c => c.address || '').join(','));
    $("#allcontacttype").val(occupants.map(c => c.contacttype || '').join(','));
    $("#allgender").val(occupants.map(c => c.gender || '').join(','));
    $("#allfather").val(occupants.map(c => c.fathername || '').join(','));
    $("#allpitype").val(occupants.map(c => c.pitype || '').join(','));
    $("#allpid").val(occupants.map(c => c.pid || '').join(','));
    $("#allanniversary").val(occupants.map(c => c.anniversary || '').join(','));
    $("#alloccupation").val(occupants.map(c => c.occupation || '').join(','));
    $("#alllastname").val(occupants.map(c => c.lastname || '').join(','));
    $("#allvip").val(occupants.map(c => c.vip || 'No').join(','));
    $("#allcomments").val(occupants.map(c => c.comments || '').join(','));

    // Enable/disable save button based on whether there are occupants left
    toggleSaveButton();
});

    // Initial check
    toggleSaveButton();
});


window.nigerianStates = window.nigerianStates || [
    "Abia", "Adamawa", "Akwa Ibom", "Anambra", "Bauchi", "Bayelsa", "Benue", "Borno",
    "Cross River", "Delta", "Ebonyi", "Edo", "Ekiti", "Enugu", "Gombe", "Imo",
    "Jigawa", "Kaduna", "Kano", "Katsina", "Kebbi", "Kogi", "Kwara", "Lagos",
    "Nasarawa", "Niger", "Ogun", "Ondo", "Osun", "Oyo", "Plateau", "Rivers",
    "Sokoto", "Taraba", "Yobe", "Zamfara", "FCT"
];

$(document).ready(function() {
    const $country = $('#country');
    const $stateWrapper = $('#state').closest('.icon-addon');
    const originalStateHTML = $stateWrapper.html(); // store original input HTML

    function buildStateSelect() {
        const $select = $('<select>', {
            id: 'state',
            class: 'form-control select2-nationality',
            name: 'state'
        }).append('<option value="">Select state</option>');

        nigerianStates.forEach(state => {
            $select.append(`<option value="${state}">${state}</option>`);
        });

        return $select;
    }

    function updateStateField() {
        const selectedCountry = $country.val()?.trim().toLowerCase() || '';

        if (selectedCountry === 'nigeria') {
            // Only replace if it's currently an input
            if ($('#state').is('input')) {
                // Destroy previous Select2 if any (safety)
                if ($('#state').hasClass('select2-hidden-accessible')) {
                    $('#state').select2('destroy');
                }

                $stateWrapper.empty().append(buildStateSelect());

                // Initialize Select2
                $('#state').select2({
                    dropdownParent: $('#exampleModal .modal-body'),
                    width: '100%',
                    placeholder: 'Select state',
                    allowClear: true,
                    minimumResultsForSearch: 10
                });z
            }
        } else {
            // Revert to original input safely
            if ($('#state').is('select')) {
                if ($('#state').hasClass('select2-hidden-accessible')) {
                    $('#state').select2('destroy');
                }
                $stateWrapper.html(originalStateHTML);
            }
        }
    }

    // Run on change and on page load
    $country.on('change', updateStateField);
    updateStateField(); // initial load in case country is pre-selected
});


