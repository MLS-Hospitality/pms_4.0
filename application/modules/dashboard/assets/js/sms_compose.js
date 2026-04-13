$(document).ready(function() {
    
    // Initialize Select2 for customer selection
    if (typeof $.fn.select2 !== 'undefined') {
        $('#customer_select').select2({
            placeholder: "Select customers...",
            allowClear: true,
            width: '100%'
        });
    }
    
    // Character and SMS counter
    $('#sms_message').on('keyup', function() {
        var message = $(this).val();
        var charCount = message.length;
        var smsCount = Math.ceil(charCount / 160);
        
        $('#char_count').text(charCount + ' characters');
        $('#sms_count').text(smsCount + ' SMS');
        
        // Change badge color based on length
        if (charCount > 160) {
            $('#char_count').removeClass('badge-info').addClass('badge-warning');
            $('#sms_count').removeClass('badge-secondary').addClass('badge-warning');
        } else {
            $('#char_count').removeClass('badge-warning').addClass('badge-info');
            $('#sms_count').removeClass('badge-warning').addClass('badge-secondary');
        }
    });
    
    // Quick template selection
    $('#quick_template').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var message = selectedOption.data('message');
        
        if (message) {
            $('#sms_message').val(message);
            // Trigger character count update
            $('#sms_message').trigger('keyup');
        }
    });
    
    // Recipient type change
    $('#recipient_type').on('change', function() {
        var type = $(this).val();
        
        if (type === 'selected') {
            $('#customer_selection_div').slideDown();
            $('#customer_select').prop('required', true);
        } else {
            $('#customer_selection_div').slideUp();
            $('#customer_select').prop('required', false);
        }
    });
    
    // Send SMS button
    $('#send_sms_btn').on('click', function(e) {
        e.preventDefault();
        
        var recipientType = $('#recipient_type').val();
        var message = $('#sms_message').val().trim();
        var customerIds = '';
        
        // Validation
        if (!recipientType) {
            showAlert('error', 'Please select recipient type');
            return false;
        }
        
        if (!message) {
            showAlert('error', 'Please enter a message');
            return false;
        }
        
        if (recipientType === 'selected') {
            var selectedCustomers = $('#customer_select').val();
            if (!selectedCustomers || selectedCustomers.length === 0) {
                showAlert('error', 'Please select at least one customer');
                return false;
            }
            customerIds = selectedCustomers.join(',');
        }
        
        // Confirmation
        var confirmMessage = 'Are you sure you want to send this SMS?';
        if (recipientType === 'all') {
            confirmMessage = 'This will send SMS to ALL customers. Are you sure?';
        }
        
        if (!confirm(confirmMessage)) {
            return false;
        }
        
        // Show progress modal
        $('#progressModal').modal('show');
        
        // Send AJAX request
        $.ajax({
            url: base_url + 'dashboard/sms_compose/send_sms',
            timeout: 60000, // 60 second timeout
            type: 'POST',
            data: {
                recipient_type: recipientType,
                message: message,
                customer_ids: customerIds
            },
            dataType: 'json',
            success: function(response) {
                $('#progressModal').modal('hide');
                
                if (response.success) {
                    var resultHtml = '<div class="alert alert-success">' +
                                    '<h5><i class="ti-check"></i> Success!</h5>' +
                                    '<p>' + response.message + '</p>' +
                                    '<ul>' +
                                    '<li>Successfully sent: <strong>' + response.success_count + '</strong></li>' +
                                    '<li>Failed: <strong>' + response.failed_count + '</strong></li>' +
                                    '</ul>' +
                                    '</div>';
                    
                    $('#result_message').html(resultHtml);
                    $('#resultModal').modal('show');
                    
                    // Clear form
                    $('#sms_compose_form')[0].reset();
                    $('#sms_message').trigger('keyup');
                    $('#customer_selection_div').hide();
                    if (typeof $.fn.select2 !== 'undefined') {
                        $('#customer_select').val(null).trigger('change');
                    }
                } else {
                    var errorHtml = '<div class="alert alert-danger">' +
                                   '<h5><i class="ti-close"></i> Error!</h5>' +
                                   '<p>' + response.message + '</p>' +
                                   '</div>';
                    
                    $('#result_message').html(errorHtml);
                    $('#resultModal').modal('show');
                }
            },
            error: function(xhr, status, error) {
                $('#progressModal').modal('hide');
                
                var errorHtml = '<div class="alert alert-danger">' +
                               '<h5><i class="ti-alert"></i> Request Failed!</h5>' +
                               '<p>An error occurred while sending SMS. Please try again.</p>' +
                               '<small>Error: ' + error + '</small>' +
                               '</div>';
                
                $('#result_message').html(errorHtml);
                $('#resultModal').modal('show');
            }
        });
    });
    
    // Save draft button
    $('#save_draft_btn').on('click', function(e) {
        e.preventDefault();
        
        var message = $('#sms_message').val().trim();
        
        if (!message) {
            showAlert('error', 'Please enter a message to save as draft');
            return false;
        }
        
        var title = prompt('Enter a title for this draft:');
        if (!title) {
            return false;
        }
        
        var recipientType = $('#recipient_type').val();
        var customerIds = '';
        
        if (recipientType === 'selected') {
            var selectedCustomers = $('#customer_select').val();
            if (selectedCustomers && selectedCustomers.length > 0) {
                customerIds = selectedCustomers.join(',');
            }
        }
        
        $.ajax({
            url: base_url + 'dashboard/sms_compose/save_draft',
            timeout: 10000,
            type: 'POST',
            data: {
                title: title,
                message: message,
                recipient_type: recipientType,
                customer_ids: customerIds
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Failed to save draft');
            }
        });
    });
    
    // Helper function to show alerts
    function showAlert(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var icon = type === 'success' ? 'ti-check' : 'ti-alert';
        
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                       '<i class="' + icon + '"></i> ' + message +
                       '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                       '<span aria-hidden="true">&times;</span>' +
                       '</button>' +
                       '</div>';
        
        // Insert alert at the top of the card body
        $('.card-body').prepend(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to top
        $('html, body').animate({
            scrollTop: 0
        }, 'slow');
    }
    
    // Initialize tooltips
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Insert placeholder buttons (optional enhancement)
    var placeholderButtons = '<div class="btn-group btn-group-sm mt-2" role="group">' +
                            '<button type="button" class="btn btn-outline-secondary insert-placeholder" data-placeholder="{firstname}">First Name</button>' +
                            '<button type="button" class="btn btn-outline-secondary insert-placeholder" data-placeholder="{lastname}">Last Name</button>' +
                            '<button type="button" class="btn btn-outline-secondary insert-placeholder" data-placeholder="{fullname}">Full Name</button>' +
                            '<button type="button" class="btn btn-outline-secondary insert-placeholder" data-placeholder="{hotelname}">Hotel Name</button>' +
                            '</div>';
    
    $('#sms_message').after(placeholderButtons);
    
    // Insert placeholder on click
    $(document).on('click', '.insert-placeholder', function() {
        var placeholder = $(this).data('placeholder');
        var textarea = $('#sms_message');
        var cursorPos = textarea.prop('selectionStart');
        var textBefore = textarea.val().substring(0, cursorPos);
        var textAfter = textarea.val().substring(cursorPos);
        
        textarea.val(textBefore + placeholder + textAfter);
        
        // Update character count
        textarea.trigger('keyup');
        
        // Set cursor position after inserted placeholder
        var newPos = cursorPos + placeholder.length;
        textarea[0].setSelectionRange(newPos, newPos);
        textarea.focus();
    });
});

// Get base URL from hidden input or construct it
var base_url = '';
if ($('#base_url').length > 0) {
    base_url = $('#base_url').val();
} else if (typeof baseurl !== 'undefined') {
    base_url = baseurl;
} else {
    // Fallback: construct from window location
    base_url = window.location.origin + window.location.pathname.split('/').slice(0, -2).join('/') + '/';
    // Remove double slashes except after protocol
    base_url = base_url.replace(/([^:]\/)\/+/g, "$1");
}

