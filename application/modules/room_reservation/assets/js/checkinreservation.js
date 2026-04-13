'use strict';
// Calculate and update total amount in Advance Details when discount changes
function calculateCheckinTotal() {
    var baseRent = parseFloat($("#booking_charge").text().replace(/,/g, '')) || 0;
    var taxAmount = parseFloat($("#tax_charge").text().replace(/,/g, '')) || 0;
    var serviceCharge = parseFloat($("#service_charge").text().replace(/,/g, '')) || 0;
    var discountAmount = parseFloat($("#discountamount").val()) || 0;
    
   
    
    // Get days from hidden input (if available) or calculate from dates
    var days = parseInt($("#totaldays").val()) || 1;
    if(!days || days < 1) {
        // Try to calculate from datefilter fields if available
        var datefilter1 = $("#datefilter1").val();
        var datefilter2 = $("#datefilter2").val();
        if(datefilter1 && datefilter2) {
            var diff = Math.ceil((Date.parse(datefilter2) - Date.parse(datefilter1)) / 86400000);
            if(diff > 0) {
                days = diff;
            } else {
                days = 1;
            }
        }
    }
    
     // Calculate subtotal (rent + tax + service charge)
    var subtotal = baseRent ;
    
    // Apply discount
    var total = ((subtotal*days)+taxAmount + serviceCharge);
    
    
    // Ensure total is not negative
    if(totalPerDay < 0) {
        totalPerDay = 0;
    }
    // Calculate total for all days
    var totalAmount = total - discountAmount;
    
    // Update advance details total amount
    $("#totalamount").val(totalAmount.toFixed(2));
    
    // Calculate due amount (total - paid)
    var paidAmount = parseFloat($("#paidamount").val().replace(/,/g, '')) || 0;
    var dueAmount = Math.max(0, totalAmount - paidAmount);
    
    // Update due amount field
    if($("#dueamount").length) {
        $("#dueamount").val(dueAmount.toFixed(2));
    }
    
    // Update advance amount to match due amount (what needs to be paid now)
    if($("#advanceamount").length && !$("#advanceamount").is(":focus")) {
        $("#advanceamount").val(dueAmount.toFixed(2));
    }
    
    // Also update billing total charge if it exists
    if($("#total_charge").length) {
        $("#total_charge").text(totalPerDay.toFixed(2));
    }
}

// Update total when discount changes
$("#discountrate").on('change', function(){
    var discountrate = $("#discountrate").val();
    if(discountrate && discountrate <= 100) {
        var baseRent = parseFloat($("#booking_charge").text().replace(/,/g, '')) || 0;
        var disamount = ((baseRent * discountrate) / 100);
        $("#discountamount").val(disamount.toFixed(2));
        calculateCheckinTotal();
    } else {
        $("#discountamount").val(0);
        calculateCheckinTotal();
    }
});

$("#discountamount").on("change keyup input", function(){
    calculateCheckinTotal();
});

// Function to validate and enable/disable checkin button based on advance amount vs due amount
function validateAdvanceAmount() {
    var totalAmount = parseFloat($("#totalamount").val().replace(/,/g, '')) || 0;
    var paidAmount = parseFloat($("#paidamount").val().replace(/,/g, '')) || 0;
    var dueAmount = parseFloat($("#dueamount").val().replace(/,/g, '')) || 0;
    var advanceAmount = parseFloat($("#advanceamount").val()) || 0;
    
    // If already fully paid, allow check-in with zero payment
    if (dueAmount <= 0.01) {
        $("#bookingsave").prop("disabled", false).removeClass("btn-secondary").addClass("btn-primary");
        $("#msg2").text("").removeClass("text-danger text-success");
        return;
    }
    
    // Check if advance amount equals due amount (with small tolerance for floating point)
    // Allow payment to be less than or equal to due amount
    if (advanceAmount >= 0 && advanceAmount <= dueAmount + 0.01) {
        if (Math.abs(advanceAmount - dueAmount) < 0.01) {
            // Exact payment
            $("#bookingsave").prop("disabled", false).removeClass("btn-secondary").addClass("btn-primary");
            $("#msg2").text("").removeClass("text-danger text-success");
        } else if (advanceAmount < dueAmount && advanceAmount > 0) {
            // Partial payment allowed
            $("#bookingsave").prop("disabled", false).removeClass("btn-secondary").addClass("btn-primary");
            $("#msg2").text(" (Partial payment allowed)").removeClass("text-danger").addClass("text-success");
        } else if (advanceAmount == 0) {
            // No payment
            $("#bookingsave").prop("disabled", true).removeClass("btn-primary").addClass("btn-secondary");
            $("#msg2").text(" Payment required for check-in").removeClass("text-success").addClass("text-danger");
        }
    } else {
        // Payment exceeds due amount
        $("#bookingsave").prop("disabled", true).removeClass("btn-primary").addClass("btn-secondary");
        $("#msg2").text(" Payment cannot exceed due amount").removeClass("text-success").addClass("text-danger");
    }
}

// Validate when advance amount changes
$("#advanceamount").on("change keyup input", function(){
    validateAdvanceAmount();
});

// Validate when total amount changes (via calculateCheckinTotal)
// Modify calculateCheckinTotal to call validateAdvanceAmount
var originalCalculateCheckinTotal = calculateCheckinTotal;
calculateCheckinTotal = function() {
    originalCalculateCheckinTotal();
    validateAdvanceAmount();
};

// Validate when payment mode changes
$("#paymentmode").on('change', function(){
    var paymentmode = $(this).val();

    if (paymentmode === "Bank Payment") {
        $("#advanceamount").attr("disabled", false);
    } else {
        $("#advanceamount").attr("disabled", true);
    }

    validateAdvanceAmount();
});

// Initial calculation
$("#rent-1").trigger('change');
function getbsource() {
    'use strict';
    var booking_type = $("#booking_type").find(":selected").text();
    var csrf = $('#csrf_token').val();
    var myurl = baseurl + "room_reservation/room_reservation/bookingSource";
    if ($('#booking_source')[0].options.length > 1)
        $('#booking_source').find('option').not(':first').remove();
    $("#commissionrate").val('');
    $("#commissionamount").val('');
    $.ajax({
        url: myurl,
        type: "POST",
        data: {
            csrf_test_name: csrf,
            booking_type: booking_type
        },
        success: function(data) {
            var obj = JSON.parse(data);
            $.each(obj, function(key, value) {
                for (var i = 0; i < value.length; i++) {
                    $('#booking_source').append('<option value="' + value[i].btypeinfoid +
                        '">' +
                        value[i].booking_sourse + '</option>');
                }
            });
            $('.selectpicker').selectpicker('refresh');
        }
    });
}

function getcomplementprice(l) {
    "use strict";
    $("#complementary" + l).on("change", function() {
        var ecm = $("#complementary" + l).find(":selected").val();
        if (ecm > 0) {
            $("#compamount" + l).attr("hidden", false);
            $("#compamount" + l).text("Amount: " + ecm);
        } else {
            $("#compamount" + l).attr("hidden", true);
        }
    });
}
"use strict";
function toastrErrorMsg(r) {
    setTimeout(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            showMethod: 'slideDown',
            timeOut: 1500,
        };
        toastr.error(r);
    }, 1000);
}
// //            ========= its for toastr error message =============
"use strict";
function toastrSuccessMsg(r) {
    setTimeout(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            showMethod: 'slideDown',
            timeOut: 1500,
        };
        toastr.success(r);
    }, 1000);
}

'use strict';
// Common search function for both mobile and name fields
function searchCustomer() {
    var mobileSearch = $("#existmobile").val();
    var nameSearch = $("#existname").val();
    var search = mobileSearch || nameSearch;
    
    $("#addoldcustomer").attr("disabled", true);
    $("#existcustid").val("");
    $("#existmobile").removeClass("is-valid is-invalid");
    $("#existname").removeClass("is-valid is-invalid");
    
    // If user is typing in one field, clear the other to avoid confusion
    if (mobileSearch && nameSearch) {
        // Keep the last field that was typed in
        if ($("#existmobile").is(":focus")) {
            $("#existname").val("");
            search = mobileSearch;
        } else {
            $("#existmobile").val("");
            search = nameSearch;
        }
    }
    
    if (search != "") {
        var csrf = $('#csrf_token').val();
        var myurl = baseurl + "room_reservation/room_reservation/existcustomer";
        $.ajax({
            url: myurl,
            type: 'post',
            data: {
                csrf_test_name: csrf,
                search: search,
                type: 1
            },
            dataType: 'json',
            success: function(response) {
                if (response.user != "Not found" && Array.isArray(response.user)) {
                    var len = response.user.length;
                    $("#searchResult").empty();
                    for (var i = 0; i < len; i++) {
                        var mobile = response.user[i].cust_phone || '';
                        var name = response.user[i].firstname || '';
                        var customerId = response.user[i].customerid || '';
                        $("#searchResult").append("<li data-mobile='" + mobile + "' data-name='" + name + "' data-id='" + customerId + "'>" + mobile + ' - ' + name + "</li>");
                    }
                    // binding click event to li
                    $("#searchResult li").bind("click", function() {
                        existuser(this);
                    });
                } else {
                    $("#searchResult").empty();
                }
            }
        });
    } else {
        $("#searchResult").empty();
        $("#existcustid").val("");
    }
}

// Search when typing in mobile field
$("#existmobile").on("keyup", function() {
    $("#existname").val("");
    searchCustomer();
});

// Search when typing in name field
$("#existname").on("keyup", function() {
    $("#existmobile").val("");
    searchCustomer();
});

function existuser(value) {
    'use strict';
    var $item = $(value);
    var existmobile = $item.data('mobile') || $item.text().split('-')[0].trim();
    var existname = $item.data('name') || $item.text().split('-').slice(1).join('-').trim();
    var existcustid = $item.data('id') || '';
    
    $("#existmobile").val(existmobile);
    $("#existname").val(existname);
    $("#existcustid").val(existcustid);
    $("#searchResult").empty();
    
    if (existcustid && existmobile) {
        $("#existmobile").removeClass("is-invalid").addClass("is-valid");
        $("#existname").removeClass("is-invalid").addClass("is-valid");
        $("#addoldcustomer").attr("disabled", false);
    } else {
        $("#existmobile").removeClass("is-valid").addClass("is-invalid");
        $("#existname").removeClass("is-valid").addClass("is-invalid");
        $("#existcustid").val("");
        $("#addoldcustomer").attr("disabled", true);
    }
}
$("#mobileNo").on('keyup', mobilenocheck);
$("#mobileNo").on('change', mobilenocheck);

function mobilenocheck() {
    'use strict';
    var mobileno = $("#mobileNo").val();
    if (mobileno) {
        var csrf = $('#csrf_token').val();
        var myurl = baseurl + "room_reservation/room_reservation/mobilenocheck";
        $.ajax({
            url: myurl,
            type: "POST",
            data: {
                csrf_test_name: csrf,
                mobileno: mobileno,
            },
            success: function(data) {
                var obj = JSON.parse(data);
                if (obj.existuser == 1) {
                    $("#mobileNo").addClass("is-invalid");
                    $("#addcustomer").attr("hidden", true);
                } else {
                    $("#mobileNo").removeClass("is-invalid");
                    $("#addcustomer").attr("hidden", false);
                }
            }
        });
    } else {
        $("#mobileNo").removeClass("is-invalid");
        $("#addcustomer").attr("hidden", false);
    }
}
// Validate payment mode and enable/disable advance amount
var pmode = $("#paymentmode").find(":selected").val();
if (pmode != "Bank Payment") {
    $("#advanceamount").attr("disabled", false);
} else {
    $("#advanceamount").attr("disabled", true);
}
// Initial validation
validateAdvanceAmount();

function checkinBooking() {
    'use strict';
    var finyear = $("#finyear").val();
    if (finyear <= 0) {
        swal({
            title: "Failed",
            text: "Please Create Financial Year First",
            type: "error",
            confirmButtonColor: "#28a745",
            confirmButtonText: "Ok",
            closeOnConfirm: true
        });
        return false;
    }
    $("#msg").text("");
    $("#msg1").text("");
    var datefilter1 = $("#datefilter1").val();
    if (datefilter1 == "") {
        $("#msg").text("Start date and time field is required");
        return false;
    }
    var datefilter2 = $("#datefilter2").val();
    if (datefilter2 == "") {
        $("#msg").text("End date and time field is required");
        return false;
    }
    if (datefilter2 <= datefilter1) {
        $("#msg").text("Checkout field can not equal or smaller than Checkin field");
        return false;
    }
    var currtime = $("#currtime").val();
    if (currtime < datefilter1) {
        swal({
            title: "Warning",
            text: "Checkin time is greater than current time",
            type: "warning",
            confirmButtonColor: "#28a745",
            confirmButtonText: "Ok",
            closeOnConfirm: true
        });
        return false;
    }
    //roomdetails
    var all = $("table.room-list > tbody").length;
    var room_type = $('#room_type').find(":selected").val();
    if (room_type == null) {
        room_type = $('#room_type-1').find(":selected").val();
    }
    for (var s = 0; s < all - 1; s++) {
        room_type += ",".concat($("#room_type" + s).val());
    }
    if (room_type == "") {
        $("#msg1").text("Room type field is required");
        return false;
    }
    var roomno = $('#roomno').find(":selected").val();
    if (roomno == null) {
        roomno = $('#roomno-1').find(":selected").val();
    }
    for (var s = 0; s < all - 1; s++) {
        roomno += ",".concat($("#roomno" + s).val());
    }
    if (roomno == "") {
        $("#msg1").text("Room type field is required");
        return false;
    }
    var adults = $("#adults").val();
    if (adults == null) {
        adults = $("#adults-1").val();
    }
    for (var s = 0; s < all - 1; s++) {
        adults += ",".concat($("#adults" + s).val());
    }
    if (adults == "") {
        $("#msg1").text("Adults field is required");
        return false;
    }
    var children = $("#children").val();
    if (children == null) {
        children = $("#children-1").val();
    }
    for (var s = 0; s < all - 1; s++) {
        children += ",".concat($("#children" + s).val());
    }
    var bed = $("#bed-1").val();
    if (bed == "") {
        bed = 0;
    }
    for (var s = 0; s < all - 1; s++) {
        var bedval = $("#bed" + s).val();
        if (bedval == "") {
            bedval = 0;
        }
        bed += ",".concat(bedval);
    }
    var amount1 = $("#amount1").val();
    if (amount1 == null) {
        amount1 = $("#amount1-1").val();
    }
    for (var s = 0; s < all - 1; s++) {
        amount1 += ",".concat($("#amount1" + s).val());
    }
    var person = $("#person-1").val();
    if (person == "") {
        person = 0;
    }
    for (var s = 0; s < all - 1; s++) {
        var personval = $("#person" + s).val();
        if (personval == "") {
            personval = 0;
        }
        person += ",".concat(personval);
    }
    var amount2 = $("#amount2-1").val();
    for (var s = 0; s < all - 1; s++) {
        amount2 += ",".concat($("#amount2" + s).val());
    }
    var child = $("#child1-1").val();
    if (child == "") {
        child = 0;
    }
    for (var s = 0; s < all - 1; s++) {
        var childval = $("#child1" + s).val();
        if (childval == "") {
            childval = 0;
        }
        child += ",".concat(childval);
    }
    var amount3 = $("#amount3").val();
    if (amount3 == null) {
        amount3 = $("#amount3-1").val();
    }
    for (var s = 0; s < all - 1; s++) {
        amount3 += ",".concat($("#amount3" + s).val());
    }
    if (amount3 == "") {
        amount3 = 0;
    }
    var extrastart = $('#from_date2').val();
    if (extrastart == null) {
        extrastart = $("#from_date2-1").val();
    }
    for (var s = 0; s < all - 1; s++) {
        extrastart += ",".concat($("#from_date2" + s).val());
    }
    var extraend = $('#to_date2').val();
    if (extraend == null) {
        extraend = $("#to_date2-1").val();
    }
    for (var s = 0; s < all - 1; s++) {
        extraend += ",".concat($("#to_date2" + s).val());
    }
    var diff = Math.ceil((Date.parse(datefilter2) - Date.parse(datefilter1)) / 86400000);
    var rentval = parseFloat($("#rent").val());
    var rent = rentval / parseFloat(diff);
    if (rent == null | isNaN(rent)) {
        var rentval = parseFloat($("#rent-1").val());
        var rent = rentval / parseFloat(diff);
    }
    for (var s = 0; s < all - 1; s++) {
        var rentval = parseFloat($("#rent" + s).val());
        var rentdiv = rentval / parseFloat(diff);
        rent += ",".concat(rentdiv);
    }
    var complementary = $("#complementary-1").find(":selected").text();
    if (complementary == "Choose Complementary") {
        complementary = "no";
    }
    for (var s = 0; s < all - 1; s++) {
       var newcomplementary = $("#complementary" + s).find(":selected").text();
        if (newcomplementary == "Choose Complementary") {
            newcomplementary = "no";
        }
        complementary += ",".concat(newcomplementary);
    }
    complementary = $.trim(complementary.replace(/\s+/g, " "));

    var complementaryprice = $("#complementary").find(":selected").val();
    if (complementaryprice == null) {
        complementaryprice = $("#complementary-1").find(":selected").val();
    }
    for (var s = 0; s < all - 1; s++) {
        complementaryprice += ",".concat($("#complementary" + s).find(":selected").val());
    }
    var offer_price = $("#offer_price-1").text();
    if (offer_price == '') {
        offer_price = 0;
    }
    for (var s = 0; s < all - 1; s++) {
        offer_price += ",".concat(($("#offer_price" + s).text() ? $("#offer_price" + s).text() : 0));
    }
    //end
    var name = $("#alluser").val();
    var userid = $("#alluserid").val();
    if (name == "") {
        var tc = $("table.customerdetail-1 tbody tr").length;
        var newname = $("#username0").text();
        var newuserid = $("#userid0").text();
        for (var s = 1; s < tc; s++) {
            newname += ",".concat($("#username" + s).text());
            newuserid += ",".concat($("#userid" + s).text());
        }
        if (name.length < newname.length) {
            userid = $.trim(newuserid.replace(/\s+/g, " "));
            if (userid === '') {
                name = $.trim(newname.replace(/\s+/g, " "));
            } else {
                name = "";
            }
        }
    }
    //reservation details
    var booking_type = $("#booking_type").find(":selected").val();
    var booking_source = $("#booking_source").find(":selected").val();
    var bsorurce_no = $("#bsorurce_no").val();
    var arrival_from = $("#arrival_from").val();
    var pof_visit = $("#pof_visit").val();
    var booking_remarks = $("#booking_remarks").val();
    //user details
    var email = $("#allemail").val();
    var mobile = $("#allmobile").val();
    var lastname = $("#alllastname").val();
    var gender = $("#allgender").val();
    var father = $("#allfather").val();
    var occupation = $("#alloccupation").val();
    var dob = $("#alldob").val();
    var anniversary = $("#allanniversary").val();
    var pitype = $("#allpitype").val();
    var imgfront = $("#allimgfront").val();
    var imgback = $("#allimgback").val();
    var imgguest = $("#allimgguest").val();
    var contacttype = $("#allcontacttype").val();
    var state = $("#allstate").val();
    var city = $("#allcity").val();
    var zipcode = $("#allzipcode").val();
    var address = $("#alladdress").val();
    var country = $("#allcountry").val();
    //payment details
    var discountreason = $("#discountreason").val();
    var discountamount = $("#discountamount").val();
    var commissionrate = $("#commissionrate").val();
    var commissionamount = $("#commissionamount").val();
    var paymentmode = $("#paymentmode").find(":selected").val();
    if (paymentmode == "Bank Payment") {
        if ($("#cardno").val() == "") {
            $("#cardno").addClass("is-invalid");
            return false;
        } else if ($("#bankname").find(":selected").val() == "") {
            $("#cardno").removeClass("is-invalid");
            $("#bankname").parent().addClass("is-invalid");
            return false;
        } else {
            $("#cardno").removeClass("is-invalid");
            $("#bankname").parent().removeClass("is-invalid");
        }
    }
    var bankname = $("#bankname").find(":selected").val();
    var cardno = $("#cardno").val();
    var advanceamount = $("#advanceamount").val();
    var advanceremarks = $("#advanceremarks").val();
    var bookingid = $("#bookingid").val();

    var csrf = $('#csrf_token').val();
    var myurl = baseurl + "room_reservation/room_reservation/checkinBooking";
    $.ajax({
        url: myurl,
        type: "POST",
        data: {
            csrf_test_name: csrf,
            booking_type: booking_type,
            booking_source: booking_source,
            bsorurce_no: bsorurce_no,
            arrival_from: arrival_from,
            pof_visit: pof_visit,
            booking_remarks: booking_remarks,
            datefilter1: datefilter1,
            datefilter2: datefilter2,
            room_type: room_type,
            roomno: roomno,
            adults: adults,
            children: children,
            rent: rent,
            discount_price: offer_price,
            complementary: complementary,
            complementaryprice: complementaryprice,
            name: name,
            mobile: mobile,
            email: email,
            lastname: lastname,
            gender: gender,
            father: father,
            occupation: occupation,
            dob: dob,
            anniversary: anniversary,
            pitype: pitype,
            imgfront: imgfront,
            imgback: imgback,
            imgguest: imgguest,
            contacttype: contacttype,
            state: state,
            city: city,
            zipcode: zipcode,
            address: address,
            country: country,
            bed: bed,
            amount1: amount1,
            person: person,
            amount2: amount2,
            child: child,
            amount3: amount3,
            extrastart: extrastart,
            extraend: extraend,
            discountreason: discountreason,
            discountamount: discountamount,
            commissionrate: commissionrate,
            commissionamount: commissionamount,
            paymentmode: paymentmode,
            bankname: bankname,
            cardno: cardno,
            advanceamount: advanceamount,
            advanceremarks: advanceremarks,
            bookingid: bookingid
        },
        success: function(data) {
            if (data.substr(4, 1) === "S") {
                $("#booking_list").show();
                $("#reservation").hide();
                toastrSuccessMsg(data);
                $("#bookingdetails").DataTable().ajax.reload();
                $(".sidebar-mini").removeClass('sidebar-collapse');
            } else
                toastrErrorMsg(data);
            setTimeout(function() {}, 1000);
        }
    });
}
"use strict";
$("#view_checin,#previous").on("click", function() {
    $("#booking_list").show();
    $("#reservation").hide();
    $("#openregister").modal('hide');
    $(".sidebar-mini").removeClass('sidebar-collapse');
});