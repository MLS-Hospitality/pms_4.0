var editIndex = null;
function custedit(r){
    editIndex = r;
    $("#exampleModal").modal("show");
    var full_name = $("#username"+r).text().split(" ");
    var title = full_name[0];
    var last_name = $("#userlastname"+r).text();
    var first_name = $("#username"+r).text().replace(title + " ", "").replace(" " + last_name, "");
    
    $("#title").val(title);
    $("#firstname").val(first_name);
    $("#lastname").val(last_name);
    
    var mobile_full = $("#usermobile"+r).text();
    $("#mobileNo").val(mobile_full); // it includes code but whatever
    
    $("#email").val($("#useremail"+r).text());
    if($("#usergender"+r).text()) {
        $('input[name="customRadioInline"][value="'+$("#usergender"+r).text()+'"]').prop("checked", true);
    }
    $("#fathername").val($("#userfathername"+r).text());
    $("#occupation").val($("#useroccupation"+r).text());
    $("#dob").val($("#userdob"+r).text());
    $("#anniversary").val($("#useranniversary"+r).text());
    
    $("#nationality").val($("#usernationality"+r).text()).trigger('change');
    $("#country").val($("#usercountry"+r).text()).trigger('change');
    
    if($("#uservip"+r).text()) {
        $('input[name="vip"][value="'+$("#uservip"+r).text()+'"]').prop("checked", true);
    }
    $("#pitype").val($("#userpitype"+r).text());
    $("#pid").val($("#userpid"+r).text());
    $("#comments").val($("#usercomments"+r).text());
    $("#contacttype").val($("#usercontacttype"+r).text());
    $("#state").val($("#userstate"+r).text());
    $("#city").val($("#usercity"+r).text());
    $("#zipcode").val($("#userzipcode"+r).text());
    $("#address").val($("#useraddress"+r).text());
    
    $("#addcustomer").attr("disabled", false);
}
