<?php
session_start(); 
if (isset($_GET['ss_id'])) {
  $sessionToken = $_GET['ss_id'];

  if (
      isset($_SESSION['payroll_token']) &&
      isset($_COOKIE['payroll_token']) &&
      $_SESSION['payroll_token'] === $sessionToken &&
      $_COOKIE['payroll_token'] === $sessionToken
  ) {
      // User is authenticated
     

      // Perform the database query
      include("db_conn.php"); // Make sure you include the database connection

      $checkTokenSql = "SELECT user_code,token FROM tokens WHERE token=?";
      $checkTokenStmt = $conn->prepare($checkTokenSql);
      $checkTokenStmt->bind_param("s", $sessionToken);
      $checkTokenStmt->execute();
      $result = $checkTokenStmt->get_result();

      if ($result->num_rows == 1) {
            
            $row = $result->fetch_assoc();
            $user_code = $row['user_code'];
            $token = $row['token'];

           


?>
<!DOCTYPE html>
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Dashboard - Contact Candidate </title>

    <meta name="description" content="" />
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link href="https://cdn.datatables.net/v/bs4/dt-1.13.7/datatables.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/libs/apex-charts/apex-charts.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/js/config.js"></script>

    <style>
      /* Position the suggestions dropdown */
      .user-suggestions {
          position: absolute;
          top: 100%;
          left: 0;
          right: 0;
          z-index: 1000;
          display: none; /* Hidden by default */
          max-height: 200px;
          overflow-y: auto;
          background-color: #fff;
          border: 1px solid #ccc;
          border-radius: 0.25rem;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }

      /* Style for individual suggestion items */
      .user-suggestion-item {
          padding: 8px 12px;
          cursor: pointer;
      }

      .user-suggestion-item:hover {
          background-color: #f1f1f1;
      }

      /* Style for selected user tags */
      .selected-user-tag {
          display: inline-block;
          background-color: #007bff;
          color: #fff;
          padding: 4px 8px;
          margin-right: 4px;
          margin-bottom: 4px;
          border-radius: 0.25rem;
      }

      .selected-user-tag .remove-tag {
          margin-left: 8px;
          cursor: pointer;
      }

    </style>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <?php require('header.php'); ?>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar">
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <!-- Search -->
              <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                  <span class="demo">
                    <center><img src="<?php echo LOGO_PATH; ?>" style="width: 55%;" /></center>
                  </span>
                  
                </div>
              </div>
              <!-- /Search -->
              <?php																		
                          $result = mysqli_query($conn, "SELECT * FROM user WHERE user_code= '".$user_code."'");
                          $rows = mysqli_fetch_assoc($result);                
							          ?>
              <ul class="navbar-nav flex-row align-items-center ms-auto">
                
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow"  data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block"><?php echo $rows["name"];?></span>
                            <small class="text-muted">Admin</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    
                    <li>
                      <a class="dropdown-item" href="logout">
                        <i class="bx bx-power-off me-2"></i>
                        <span class="align-middle">Log Out</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>


          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->
            <input type="hidden" value="<?php if(isset($user_code)){ echo $user_code; } ?>" id="user_code">
            <div class="container-xxl flex-grow-1 container-p-y">
              <div class="row">
                
                
                <!-- Total Revenue -->
                <div class="col-lg-12 col-md-12 order-1">
                  <div class="col-xxl">
                    <div class="card mb-4">
                    <div class="card">
                        <h5 class="card-header">Contact Candidate</h5>

                        <div class="table-responsive text-nowrap" style="padding: 2em; font-size: 12px;">
                            <div class="row">
                                <div class="col-lg-6">
                                    <button id="updateAllBtn" class="btn btn-primary" style="margin-bottom: 10px;">Update All</button>
                                    <div id="updateStatus"></div>
                                </div>
                            </div>
                            <table class="table" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th>S.NO</th>
                                        <th>Candidate No</th>
                                        <th>Name</th>
                                        <th>Mobile No</th>
                                        <th>Adjusted No</th>
                                        <th>Watsapp</th>
                                        <th>Adjusted No</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Rows will be populated by AJAX -->
                                </tbody>
                            </table>
                        </div>


                    
                        

                      </div>
                      
                    </div>
                  </div>
                </div>

                

                
                
              </div>              
            </div>
            <!-- / Content -->

            




            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  ©
                  <script>
                    document.write(new Date().getFullYear());
                  </script>
                 <?php echo COMPANY_NAME; ?>. All Rights Reserved.                   
                </div>
                
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->
   

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <script src="https://cdn.datatables.net/v/bs4/dt-1.13.7/datatables.min.js"></script>                                     
    <script>
     
        $(document).ready(function () {
            fetchUpdatedContacts();

            var loaderCSS = `
                .loader-overlay {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                    background-color: rgba(0,0,0 /0%);
                }
                .loader {
                    position: absolute;
                    left: 50%;
                    top: 50%;
                    transform: translate(-50%, -50%);
                    border: 16px solid #f3f3f3;
                    border-top: 16px solid #3498db;
                    border-radius: 50%;
                    width: 120px;
                    height: 120px;
                    animation: spin 2s linear infinite;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;

            // Append CSS to head
            $('head').append('<style>' + loaderCSS + '</style>');

            // Create loader HTML and append to body
            $('body').append('<div class="loader-overlay"><div class="loader"></div></div>');

            function showLoader() {
                $('.loader-overlay').show();
            }

            function hideLoader() {
                $('.loader-overlay').hide();
            }

            function cleanPhoneNumber(number) {
                if (!number) return ""; // Handle empty values

                // Trim whitespace
                number = number.trim();

                // If the number is already in a correct format, return as is
                const correctFormatRegex = /^\+91-[6789]\d{9}$/;
                if (correctFormatRegex.test(number)) {
                    return number;
                }

                // Remove spaces and special characters except '/' and ','
                number = number.replace(/[\s\-().]/g, '');

                // Split multiple numbers by `/` or `,`, and take the first valid one
                let numberParts = number.split(/[\/,]/);
                number = numberParts[0] ? numberParts[0].trim() : '';

                // Remove non-numeric characters except leading '+'
                number = number.replace(/[^\d+]/g, '');

                // Remove leading '+' and '0'
                number = number.replace(/^\+/, '').replace(/^0+/, '');

                // Define country codes and validation patterns
                const countryCodes = {
                    '91': [  // India supports multiple formats
                        /^91[6789]\d{9}$/,       // Standard Indian number with country code
                        /^0[6789]\d{9}$/,        // Indian number with leading zero
                        /^[6789]\d{9}$/,         // Indian number without country code
                        /^91-[6789]\d{9}$/,      // Indian number with dash
                        /^\+91[6789]\d{9}$/,     // Indian number with '+' sign
                        /^\+91-[6789]\d{9}$/,    // Indian number with '+' and dash
                        /^\+91 [6789]\d{9}$/,    // Indian number with space after '+91'
                        /^91 [6789]\d{4} \d{5}$/, // Indian number with space separation
                        /^\+91 [6789]\d{4} \d{5}$/ // Indian number with '+' and space separation
                    ],
                    '32': /^32\d{8,9}$/,  // <?php echo COMPANY_COUNTRY; ?>
                    '44': /^447\d{8,9}$/, // UK
                    '46': /^46\d{7,10}$/, // Sweden
                    '33': /^33\d{8,9}$/,  // France
                    '31': /^31\d{8,9}$/,  // Netherlands
                    '216': /^216\d{8}$/,  // Tunisia
                    '385': /^385\d{8,9}$/, // Croatia
                    '351': /^351\d{8,9}$/, // Portugal
                    '330': /^330\d{8,9}$/, // France (Alternate prefix)
                    '212': /^212\d{8,9}$/, // Morocco
                    '320': /^320\d{8,9}$/, // <?php echo COMPANY_COUNTRY; ?> (Mobile numbers)
                    '417': /^417\d{7,9}$/, // Switzerland
                    '642': /^642\d{8,9}$/  // New Zealand
                };

                let countryCode = '';

                // Detect country code
                for (let code in countryCodes) {
                    let patterns = countryCodes[code];

                    if (Array.isArray(patterns)) {
                        // If multiple patterns exist (like for India), check all
                        for (let pattern of patterns) {
                            if (pattern.test(number)) {
                                countryCode = code;
                                break;
                            }
                        }
                    } else if (patterns.test(number)) {
                        // If it's a single pattern, test directly
                        countryCode = code;
                    }

                    if (countryCode) break; // Stop if a match is found
                }

                // Format number correctly
                if (countryCode) {
                    if (countryCode === '91') {
                        return `+91-${number.replace(/^91/, '')}`; // Ensure single +91 prefix
                    }
                    return `+${countryCode}-${number.slice(countryCode.length)}`;
                }

                return number; // Return raw number if no valid country code is found
            }


          function fetchUpdatedContacts() {
              showLoader();
              $.ajax({
                  url: 'login_handle.php',
                  type: 'post',
                  data: { type: "fetch_all_contacts" },
                  dataType: 'json',
                  success: function(response) {
                      var tableBody = $('#myDataTable tbody');
                      tableBody.empty();

                      response.forEach(function(row, index) {
                          let adjustedNumber = cleanPhoneNumber(row.contact_details);
                          let adjustedAlternate = cleanPhoneNumber(row.alternate_contact_details);

                          var rowHTML = `<tr>
                              <td>${index + 1}</td>
                              <td>${row.can_code}</td>
                              <td>${row.candidate_name}</td>
                              <td class="original-number">${row.contact_details}</td>
                              <td>
                                  <input type="text" value="${adjustedNumber}" class="form-control adjusted-number" data-id="${row.id}">
                              </td>
                              <td class="original-number-alternate">${row.alternate_contact_details}</td>
                              <td>
                                  <input type="text" value="${adjustedAlternate}" class="form-control adjusted-alternate" data-id="${row.id}">
                              </td>
                          </tr>`;
                          tableBody.append(rowHTML);
                      });

                      $('#myDataTable').DataTable().destroy(); // Destroy existing DataTable instance
                      $('#myDataTable').DataTable({
                          searching: true,
                          paging: true,
                          ordering: true,
                          order: [2, 'asc'],
                          lengthMenu: [[-1], ["All"]],
                          columnDefs: [
                              { targets: 0, orderable: false, searchable: false }
                          ],
                      });

                      hideLoader();
                  },
                  error: function(xhr, status, error) {
                      console.log("Error loading data: ", error);
                      $("#updateStatus").html('<div class="alert alert-danger">Error loading candidate data.</div>');
                      hideLoader();
                  }
              });
          }


            // Adjust the original number before making any updates
            $(".adjusted-number, .adjusted-alternate").each(function() {
                $(this).attr("data-original", $(this).val());
            });

            $("#updateAllBtn").click(function() {
                var updateData = [];

                $("tr").each(function() {
                    var candidateId = $(this).find(".adjusted-number").data("id");

                    if (candidateId) {
                        var originalNumber = $(this).find('.original-number').text().trim();
                        var adjustedNumber = $(this).find('.adjusted-number').val().trim();

                        var originalAlternate = $(this).find('.original-number-alternate').text().trim();
                        var adjustedAlternate = $(this).find('.adjusted-alternate').val().trim();

                        var updateEntry = { id: candidateId };

                        if (adjustedNumber !== originalNumber) {
                            updateEntry.number = adjustedNumber;
                        }
                        if (adjustedAlternate !== originalAlternate) {
                            updateEntry.alternate_number = adjustedAlternate;
                        }

                        if (updateEntry.number || updateEntry.alternate_number) {
                            updateData.push(updateEntry);
                        }
                    }
                });

                if (updateData.length === 0) {
                    $("#updateStatus").html('<div class="alert alert-info">No changes detected.</div>');
                    return;
                }

                showLoader();

                $.ajax({
                    url: "login_handle.php",
                    type: "POST",
                    data: {
                        data: JSON.stringify(updateData),
                        type: "update_contacts"
                    },
                    success: function(response) {
                        hideLoader();
                        $("#updateStatus").html('<div class="alert alert-success">Numbers updated successfully!</div>');

                        $(".adjusted-number, .adjusted-alternate").each(function() {
                            $(this).attr("data-original", $(this).val());
                        });

                        fetchUpdatedContacts(); // Reload updated data
                    },
                    error: function() {
                        $("#updateStatus").html('<div class="alert alert-danger">Error updating numbers.</div>');
                    }
                });
            });



        });

       

    </script>
    <script src="assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="assets/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>
    <script src="js/payroll.js"></script>

    <!-- Page JS -->
    <script src="assets/js/dashboards-analytics.js"></script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
  </body>
</html>
<?php 

              } else {
                // Handle the case where the database query did not return a valid result
                echo 'Database query failed.';
              }

          // Close the database connection
          $checkTokenStmt->close();
          $conn->close();
          } else {
          // User is not authenticated, redirect to the login page (index.html)
          header("Location: index.html");
          exit();
          }
} else {
// sessionToken is missing from the query parameter, redirect to the login page (index.html)
header("Location: index.html");
exit();
}            
   
   
   
   
   ?>
