  // Enable the button only if the checkbox is checked
  const checkbox = document.getElementById('confirmCheckbox');
  const button = document.getElementById('truncateButton');
  const password = document.getElementById('adminPassword');

  checkbox.addEventListener('change', function() {
      button.disabled = !this.checked;
      password.disabled = !this.checked;
      password.value = '';
  });
  password.addEventListener('keyup', function() {
      button.disabled = !this.value;
  });
  var csrf = $('#csrf_token').val();
  var baseurl = $('#base_url').val() + 'dashboard/setting/truncate_table';

  $(document).ready(function() {
      $('#truncateForm').submit(function(event) {
          event.preventDefault();
          $('#adminPassword').val()
          var selectedTables = [];

          $('input[type="checkbox"]:checked').each(function() {
              selectedTables.push($(this).val());
          });

          var totalTables = selectedTables.length;
          var progressPerTable = 100 / totalTables;
          var progress = 0;
          if (!$('#adminPassword').val()) {
              $('#message').text('Password arguments required');
          } else {
              swal({
                      title: "Confirmation",
                      text: "Are you sure you want to submit?",
                      type: "warning",
                      showCancelButton: true,
                      confirmButtonColor: "#28a745",
                      confirmButtonText: "Yes",
                      cancelButtonText: "No",
                      closeOnConfirm: true,
                      closeOnCancel: true
                  },
                  function(isConfirm) {
                      if (isConfirm) {
                          passwordCheck(selectedTables, progressPerTable, progress, totalTables);
                      }
                  });
          }

      });

      function truncateTables(tables, progressPerTable, progress, totalTables) {
          var currentTableIndex = 0;

          function nextTable() {
              if (currentTableIndex < tables.length) {
                  var table = tables[currentTableIndex];

                  $.ajax({
                      url: baseurl,
                      type: 'POST',
                      data: {
                          csrf_test_name: csrf,
                          table: table
                      },
                      dataType: 'json',
                      success: function(response) {
                          // Check if response indicates an error
                          if (response && response.status === 'error') {
                              alert('Error truncating ' + table + ': ' + (response.message || 'Unknown error'));
                              $('#message').text('Error: ' + (response.message || 'Failed to truncate ' + table));
                              return;
                          }

                          progress += progressPerTable;
                          updateProgressBar(progress);
                          currentTableIndex++;
                          nextTable();
                      },
                      error: function(xhr, status, error) {
                          var errorMsg = 'Error truncating ' + table;
                          try {
                              var response = JSON.parse(xhr.responseText);
                              if (response && response.message) {
                                  errorMsg += ': ' + response.message;
                              }
                          } catch (e) {
                              errorMsg += ': ' + error;
                          }
                          alert(errorMsg);
                          $('#message').text(errorMsg);
                      }
                  });
              } else {
                  $('#message').text('All selected tables have been truncated successfully!');
              }
          }
          nextTable();
      }

      function backupDatabase(selectedTables, progressPerTable, progress, totalTables) {
          $('#message').text("Creating database backup...");

          // Use a simple form submission to avoid blob responseType issues with DataTables
          // This prevents DataTables from trying to access responseText on blob responses
          var form = $('<form>', {
              'method': 'POST',
              'action': $('#base_url').val() + 'dashboard/autoupdate/download_backup',
              'style': 'display: none',
              'target': '_blank' // Open download in new window/tab
          });

          // Add CSRF token
          form.append($('<input>', {
              'type': 'hidden',
              'name': 'csrf_test_name',
              'value': csrf
          }));

          // Append form to body and submit
          $('body').append(form);
          form.submit();

          // Clean up form immediately
          setTimeout(function() {
              form.remove();
          }, 100);

          // Give time for backup to start, then proceed with truncation
          setTimeout(function() {
              $('#message').text('Database backup download initiated');
              truncateTables(selectedTables, progressPerTable, progress, totalTables);
          }, 1500);
      }

      function passwordCheck(selectedTables, progressPerTable, progress, totalTables) {
          $('#message').text('Password checking...');
          $.ajax({
              url: $('#base_url').val() + 'dashboard/auth/password_check',
              type: 'POST',
              data: {
                  csrf_test_name: csrf,
                  password: $('#adminPassword').val()
              },
              success: function(response) {
                  let data = JSON.parse(response);
                  if (data.status) {
                      $('#message').text('You are valid user, thanks');
                      backupDatabase(selectedTables, progressPerTable, progress, totalTables);
                  } else {
                      $('#message').text('Invalid password');
                  }
              },
              error: function() {
                  alert('Error checking password');
              }
          });
      }

      function updateProgressBar(progress) {
          $('#progressBar').css('width', progress + '%').attr('aria-valuenow', progress).text(progress.toFixed(2) + '%');
      }
  });
