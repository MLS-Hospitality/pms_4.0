<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="ti-settings"></i> 80kobosms API Test & Debug</h4>
                <p class="text-muted">Test your 80kobosms connection and diagnose issues</p>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="ti-info-alt"></i> This tool will test your 80kobosms API connection and show you exactly what's happening.
                    No SMS will actually be sent unless you provide a real phone number.
                </div>

                <form id="test_api_form">
                    <div class="form-group">
                        <label for="test_phone">Test Phone Number (Optional)</label>
                        <input type="text" class="form-control" id="test_phone" name="test_phone" 
                               placeholder="2348012345678" 
                               value="2348000000000">
                        <small class="text-muted">Leave as default or enter your phone to test actual sending</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="ti-reload"></i> Test API Connection
                    </button>
                    
                    <a href="<?php echo base_url('dashboard/sms_compose'); ?>" class="btn btn-secondary">
                        <i class="ti-arrow-left"></i> Back to Compose
                    </a>
                </form>

                <hr>

                <div id="test_results" style="display:none;">
                    <h5>Test Results</h5>
                    <div id="results_content"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#test_api_form').on('submit', function(e) {
        e.preventDefault();
        
        $('#test_results').hide();
        $('#results_content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Testing API connection...</p></div>');
        $('#test_results').show();
        
        var test_phone = $('#test_phone').val();
        
        $.ajax({
            url: '<?php echo base_url("dashboard/sms_compose/test_api"); ?>',
            type: 'POST',
            data: { test_phone: test_phone },
            dataType: 'json',
            success: function(response) {
                console.log('Test Response:', response);
                displayResults(response);
            },
            error: function(xhr, status, error) {
                $('#results_content').html(
                    '<div class="alert alert-danger">' +
                    '<h5><i class="ti-alert"></i> Request Failed</h5>' +
                    '<p>Status: ' + status + '</p>' +
                    '<p>Error: ' + error + '</p>' +
                    '<pre>' + xhr.responseText + '</pre>' +
                    '</div>'
                );
            }
        });
    });
    
    function displayResults(data) {
        var html = '';
        
        // Gateway Configuration
        html += '<div class="card mb-3">';
        html += '<div class="card-header bg-info text-white"><h6 class="mb-0">Gateway Configuration</h6></div>';
        html += '<div class="card-body"><table class="table table-sm">';
        html += '<tr><td><strong>Email:</strong></td><td>' + data.gateway_config.email + '</td></tr>';
        html += '<tr><td><strong>Sender Name:</strong></td><td>' + data.gateway_config.sender_name + '</td></tr>';
        html += '<tr><td><strong>Force DND:</strong></td><td>' + data.gateway_config.forcednd + '</td></tr>';
        html += '<tr><td><strong>Status:</strong></td><td>' + data.gateway_config.gateway_status + '</td></tr>';
        html += '</table></div></div>';
        
        // cURL Info
        var curlClass = data.curl_info.http_code == 200 ? 'success' : 'danger';
        html += '<div class="card mb-3">';
        html += '<div class="card-header bg-' + curlClass + ' text-white"><h6 class="mb-0">Connection Status</h6></div>';
        html += '<div class="card-body"><table class="table table-sm">';
        html += '<tr><td><strong>HTTP Code:</strong></td><td>' + data.curl_info.http_code + '</td></tr>';
        if (data.curl_info.curl_errno) {
            html += '<tr><td><strong>cURL Error:</strong></td><td class="text-danger">' + data.curl_info.curl_error + '</td></tr>';
        } else {
            html += '<tr><td colspan="2"><span class="text-success">✓ Connection successful</span></td></tr>';
        }
        html += '</table></div></div>';
        
        // API Response
        html += '<div class="card mb-3">';
        html += '<div class="card-header bg-primary text-white"><h6 class="mb-0">API Response</h6></div>';
        html += '<div class="card-body">';
        html += '<p><strong>Status:</strong> ' + data.api_response.status + '</p>';
        html += '<p><strong>Raw Response:</strong></p>';
        html += '<pre style="background:#f5f5f5;padding:10px;max-height:300px;overflow:auto;">' + 
                JSON.stringify(data.api_response.parsed, null, 2) + '</pre>';
        html += '</div></div>';
        
        // Diagnosis
        var diagnosisClass = data.diagnosis[0].indexOf('✅') !== -1 ? 'success' : 'warning';
        html += '<div class="card mb-3">';
        html += '<div class="card-header bg-' + diagnosisClass + ' text-white"><h6 class="mb-0">Diagnosis</h6></div>';
        html += '<div class="card-body"><ul class="mb-0">';
        for (var i = 0; i < data.diagnosis.length; i++) {
            html += '<li>' + data.diagnosis[i] + '</li>';
        }
        html += '</ul></div></div>';
        
        // Request Details
        html += '<div class="card mb-3">';
        html += '<div class="card-header bg-secondary text-white"><h6 class="mb-0">Request Sent</h6></div>';
        html += '<div class="card-body">';
        html += '<pre style="background:#f5f5f5;padding:10px;">' + 
                JSON.stringify(data.request_sent, null, 2) + '</pre>';
        html += '</div></div>';
        
        $('#results_content').html(html);
    }
});
</script>

<style>
pre {
    font-size: 12px;
    border-radius: 4px;
}
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>


