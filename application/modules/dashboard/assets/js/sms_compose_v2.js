$(document).ready(function() {
    
    // Use URLs defined in the view (most reliable method for CodeIgniter)
    var send_url = (typeof SMS_SEND_URL !== 'undefined') ? SMS_SEND_URL : $('#base_url').val() + 'dashboard/sms_compose/send_sms';
    var draft_url = (typeof SMS_DRAFT_URL !== 'undefined') ? SMS_DRAFT_URL : $('#base_url').val() + 'dashboard/sms_compose/save_draft';
    var base_url = (typeof SMS_BASE_URL !== 'undefined') ? SMS_BASE_URL : $('#base_url').val();
    
    console.log('URLs configured:'); // Debug
    console.log('- Send SMS:', send_url);
    console.log('- Save Draft:', draft_url);
    console.log('- Base URL:', base_url);
    
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
        
        // Prepare data
        var postData = {
            recipient_type: recipientType,
            message: message,
            customer_ids: customerIds
        };
        
        // Add CSRF token if available
        var csrf_token = $('#csrf_token').val() || $('input[name="csrf_test_name"]').val();
        if (csrf_token) {
            postData.csrf_test_name = csrf_token;
        }
        
        console.log('Sending SMS with data:', postData); // Debug
        console.log('URL:', base_url + 'dashboard/sms_compose/send_sms'); // Debug
        
        // Send AJAX request - Use predefined URL
        $.ajax({
            url: send_url,
            type: 'POST',
            data: postData,
            dataType: 'json',
            timeout: 60000,
            success: function(response) {
                console.log('Full API Response:', response); // Debug
                $('#progressModal').modal('hide');
                
                if (response.success) {
                    var resultHtml = '<div class="alert alert-success">' +
                                    '<h5><i class="ti-check"></i> Success!</h5>' +
                                    '<p>' + response.message + '</p>' +
                                    '<ul>' +
                                    '<li>Total recipients: <strong>' + response.total_count + '</strong></li>' +
                                    '<li>Successfully sent: <strong>' + response.success_count + '</strong></li>' +
                                    '<li>Failed: <strong>' + response.failed_count + '</strong></li>' +
                                    '</ul>';
                    
                    // Show errors if any
                    if (response.failed_count > 0 && response.errors) {
                        resultHtml += '<h6 class="mt-3">Error Details:</h6><ul class="small">';
                        for (var i = 0; i < Math.min(response.errors.length, 5); i++) {
                            resultHtml += '<li>' + response.errors[i] + '</li>';
                        }
                        resultHtml += '</ul>';
                        
                        // Show API response
                        if (response.api_response) {
                            resultHtml += '<h6 class="mt-3">API Response:</h6>';
                            resultHtml += '<pre class="small" style="background:#f5f5f5;padding:10px;max-height:150px;overflow:auto;">' + 
                                         response.api_response + '</pre>';
                        }
                    }
                    
                    resultHtml += '</div>';
                    
                    $('#result_message').html(resultHtml);
                    $('#resultModal').modal('show');
                    
                    // Clear form only if all sent successfully
                    if (response.failed_count === 0) {
                        $('#sms_compose_form')[0].reset();
                        $('#sms_message').trigger('keyup');
                        $('#customer_selection_div').hide();
                        if (typeof $.fn.select2 !== 'undefined') {
                            $('#customer_select').val(null).trigger('change');
                        }
                    }
                } else {
                    var errorHtml = '<div class="alert alert-danger">' +
                                   '<h5><i class="ti-close"></i> Error!</h5>' +
                                   '<p>' + (response.message || 'Unknown error occurred') + '</p>';
                    
                    // Show detailed errors
                    if (response.errors && response.errors.length > 0) {
                        errorHtml += '<h6 class="mt-3">Error Details:</h6><ul class="small">';
                        for (var i = 0; i < Math.min(response.errors.length, 10); i++) {
                            errorHtml += '<li>' + response.errors[i] + '</li>';
                        }
                        errorHtml += '</ul>';
                    }
                    
                    // Show API response
                    if (response.api_response) {
                        errorHtml += '<h6 class="mt-3">API Response:</h6>';
                        errorHtml += '<pre class="small" style="background:#f5f5f5;padding:10px;max-height:200px;overflow:auto;">' + 
                                    response.api_response + '</pre>';
                    }
                    
                    errorHtml += '<div class="mt-3"><small class="text-muted">Check the details above to understand why the SMS failed.</small></div>';
                    errorHtml += '</div>';
                    
                    $('#result_message').html(errorHtml);
                    $('#resultModal').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error); // Debug
                console.error('Response:', xhr.responseText); // Debug
                $('#progressModal').modal('hide');
                
                var errorMsg = 'An error occurred while sending SMS.';
                if (status === 'timeout') {
                    errorMsg = 'Request timed out. SMS may still be sending. Please check history.';
                } else if (xhr.status === 404) {
                    errorMsg = 'SMS endpoint not found. Please check if the controller exists.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error occurred. Please check server logs.';
                } else if (xhr.responseText) {
                    errorMsg += '<br><small>' + xhr.responseText.substring(0, 200) + '</small>';
                }
                
                var errorHtml = '<div class="alert alert-danger">' +
                               '<h5><i class="ti-alert"></i> Request Failed!</h5>' +
                               '<p>' + errorMsg + '</p>' +
                               '<small>Status: ' + status + ', Error: ' + error + '</small>' +
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
        
        var postData = {
            title: title,
            message: message,
            recipient_type: recipientType,
            customer_ids: customerIds
        };
        
        // Add CSRF token if available
        var csrf_token = $('#csrf_token').val() || $('input[name="csrf_test_name"]').val();
        if (csrf_token) {
            postData.csrf_test_name = csrf_token;
        }
        
        $.ajax({
            url: draft_url,
            type: 'POST',
            data: postData,
            dataType: 'json',
            timeout: 10000,
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
        $('.card-body').first().prepend(alertHtml);
        
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
    
    // Insert placeholder buttons
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

