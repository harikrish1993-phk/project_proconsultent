$(document).ready(function () {
    $('#user_hidden_data').hide();
    
    function checkLoginStatus() { 

            var token = localStorage.getItem('user_token_payroll');         
            var currentDate = new Date();            
            var year = currentDate.getFullYear();
            var month = (currentDate.getMonth() + 1).toString().padStart(2, '0'); // Month is 0-based
            var day = currentDate.getDate().toString().padStart(2, '0');
            var formattedDate = day + '-' + month + '-' + year;

            // Get the current time (formatted as "HH:MM:SS")
            var hours = currentDate.getHours().toString().padStart(2, '0');
            var minutes = currentDate.getMinutes().toString().padStart(2, '0');
            var seconds = currentDate.getSeconds().toString().padStart(2, '0');
            var formattedTime = hours + ':' + minutes + ':' + seconds;       

    
            $.ajax({
                type: "POST",
                url: "login_handle.php", // Create this PHP file
                data: { token:token,formattedDate:formattedDate, formattedTime:formattedTime, type:'checkLoginStatus' },
                success: function(response) { 
                    $("#login_status").html(response);
                }
            });
       
    }


    function showAlertError(message) {
        var alertContainer = $("#alertContainer");
        alertContainer.css({
            'display': 'none',
            'position': 'sticky',
            'width': '100%',
            'padding': '10px',
            'background-color': 'rgba(246, 115, 115, 0.5)',
            'color': 'black',
            'text-align': 'center',
            'z-index': '1'
        });
        alertContainer.html(message);
        $("#alertContainer").show();
        setTimeout(function () {
            $("#alertContainer").hide();
        }, 2000); // Hide after 3 seconds
    }
    function showAlertSuccess(message) {
        var alertContainer = $("#alertContainer");
        alertContainer.css({
            'display': 'none',
            'position': 'sticky',
            'width': '100%',
            'padding': '10px',
            'background-color': 'rgb(115 246 157 / 50%)',
            'color': 'black',
            'text-align': 'center',
            'z-index': '1'
        });
        alertContainer.html(message);
        $("#alertContainer").show();
        setTimeout(function () {
            $("#alertContainer").hide();
        }, 2000); // Hide after 3 seconds
    }


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

        $('#level').on('change', function() {
            var selectedOption = $(this).val();
            if (selectedOption === 'consultancy') {
                $("#comstandard0, #comweekend0, #comovertime0, #taxper0, #empstandard0, #empweekend0, #empovertime0, #fixed0")
                .val("0.00")   
                $("#name, #email, #mobileno, #password, #proconsultancy_data, #company_data, #invoice_emp_id, #invoice_vat, #invoice_pf_id, #invoice_endclient, #invoice_contractref, #bank_data ,#invoice_to ,#invoice_cc, #invoice_bcc")
        .val("")
        .prop('checked', false);
    
        $('#user_hidden_data').show();
            } else {  
            
                $("#name, #email, #mobileno, #password, #comstandard0, #comweekend0, #comovertime0, #taxper0, #empstandard0, #empweekend0, #empovertime0, #fixed0, #proconsultancy_data, #company_data, #invoice_emp_id, #invoice_vat, #invoice_pf_id, #invoice_endclient, #invoice_contractref, #bank_data ,#invoice_to ,#invoice_cc, #invoice_bcc")
        .val("")
        .prop('checked', false);
    
        $('#user_hidden_data').hide();
            }
        });
    
   
    $("#formAuthentication").submit(function (e) { 
      e.preventDefault();         
                
        function generateSessionToken() {
            const tokenLength = 32;
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let token = '';
            for (let i = 0; i < tokenLength; i++) {
                token += characters.charAt(Math.floor(Math.random() * characters.length));
            }
            return token;
        }       
        var sessionToken = generateSessionToken();        
        localStorage.setItem('user_token_payroll', sessionToken);
        var email = $("#email").val();        
        var password = $("#password").val();
        

        if (email.trim() === "" || password.trim() === "" || sessionToken.trim() === "") {
            showAlertError("Please fill in all fields.");
            return;
        }       
             
        var formData = {email: email, password: password,sessionToken: sessionToken, type: 'user_login' };       
            $.ajax({
                type: "POST",
                url: "login_handle.php",
                data: formData,
                success: function (response) {
                
                if (response === "success") {               
                    showAlertSuccess("Login successful.");                    
                    window.location.href = "dashboard?ss_id=" + localStorage.getItem('user_token_payroll');
                    checkLoginStatus();
                } else {               
                    showAlertError("Login failed. Please check your credentials.");
                }
            },
                error: function () {            
                    showAlertError("An error occurred during login.");
                },
            });
    });

    $('#forgetPasswordForm').on('submit', function(event) {
        event.preventDefault();     
        var email = $('#forgetPasswordEmail').val(); 
        var type = 'Forget_password';
        showLoader();
        $.ajax({
          url: 'login_handle.php', 
          type: 'POST',
          data: { email: email, type:type },
          
          success: function(response) {
              if (response.trim() === 'success') { 
                hideLoader();               
                $('#forgetPasswordModal').modal('hide');
                showAlertSuccess("An email has been sent with your password details.");                
                setTimeout(function() {
                  window.location.reload();
                }, 4000);
              } else {
                hideLoader();
                $('#forgetPasswordModal').modal('hide');
                showAlertError(response);                
              }          
          },
          error: function(xhr, status, error) {
            hideLoader();
            showAlertError("There was an error processing your request. Please try again later.");
            
          }
        });
      });


    $("#basicdetails").submit(function(e) {
        e.preventDefault();

        // Collect form data
        var usercode = $("#user_code").val();
        var name = $("#name").val();
        var email = $("#email").val();
        var mobile = $("#mobile").val();
        var password = $("#password").val();
        var con_pass = $("#con_pass").val();

        // Validate data
        if (!name || !email || !mobile || !password || !con_pass || !usercode) {
            showAlertError("Please fill in all fields.");
            return;
        }

        if (password !== con_pass) {
            showAlertError("Password and Confirm Password do not match.");
            return;
        }

        // Data is valid, send it to login_handle.php via AJAX
        $.ajax({
            type: "POST",
            url: "login_handle.php",
            data: {
                usercode: usercode,
                name: name,
                email: email,
                mobile: mobile,
                password: password,
                con_pass: con_pass,
                type: 'user_basicdetails' // You may change this type as needed
            },
            success: function(response) {
                // Handle the response from login_handle.php
                if (response === "success") {
                    showAlertSuccess("Update successful.");
                    window.location.reload();
                } else {
                    showAlertError("Update failed. Please check your data.");
                }
            },
            error: function() {
                showAlertError("An error occurred during the update.");
            }
        });
    });
    

    $("#add_job").submit(function(e) {
        e.preventDefault();

        // Collect form data
        var formData = new FormData();
        var usercode = $("#user_code").val();
        var ref_no = Math.floor(100000 + Math.random() * 900000);
        var heading = $("#heading").val();
        var company_name = $("#company_name").val();
        var experience = $("#experience").val();
        var annual_package = $("#annual_package").val();
        var job_location = $("#job_location").val();
        var job_opening = $("#job_opening").val();
        var posted_date = $("#posted_date").val();
        var job_details = $("#editor").val();
        var jobstatus = $("#jobstatus").val();
        
       

        // Validate each field
        if (!ref_no || !heading || !company_name || !experience || !annual_package || !job_location || !job_opening || !posted_date || !job_details || !usercode || !jobstatus) {
            showAlertError("Please fill in all fields.");
            return;
        }

        // Add each field to FormData
        formData.append("usercode", usercode);
        formData.append("ref_no", ref_no);
        formData.append("heading", heading);
        formData.append("company_name", company_name);
        formData.append("experience", experience);
        formData.append("annual_package", annual_package);
        formData.append("job_location", job_location);
        formData.append("job_opening", job_opening);
        formData.append("posted_date", posted_date);
        formData.append("job_details", job_details);
        formData.append("jobstatus", jobstatus);
        formData.append("type", 'add_job');

        // Data is valid, send it to login_handle.php via AJAX
        $.ajax({
            type: "POST",
            url: "login_handle.php",
            data: formData,
            processData: false,  // Prevent jQuery from processing the data
            contentType: false,  // Prevent jQuery from setting the content type
            success: function(response) {
                // Handle the response from login_handle.php
                if (response === "success") {
                    showAlertSuccess("Job added successfully.");
                    window.location.href = "list_jobs?ss_id=" + localStorage.getItem('user_token_payroll');
                } else {
                    showAlertError("Job addition failed. Please check your data.");
                }
            },
            error: function() {
                showAlertError("An error occurred while adding the job.");
            }
        });
    });

    $("#approve_job").submit(function(e) {
        e.preventDefault();

        // Collect form data
        var formData = {
            job_refno: $("#jb_id").val(),
            heading: $("#heading").val(),
            company_name: $("#company_name").val(),
            experience: $("#experience").val(),
            annual_package: $("#annual_package").val(),
            job_location: $("#job_location").val(),
            job_opening: $("#job_opening").val(),
            posted_date: $("#posted_date").val(),
            job_details: $("#editor").val(),             
            jobstatus: '1',
            type: 'approve_job'
        };

        // Validate each field
        if (!formData.heading || !formData.company_name || !formData.experience || !formData.annual_package || !formData.job_location || !formData.job_opening || !formData.posted_date || !formData.job_details || !formData.job_refno || !formData.jobstatus) {
            showAlertError("Please fill in all fields.");
            return;
        }

        // Data is valid, send it to login_handle.php via AJAX
        $.ajax({
            type: "POST",
            url: "login_handle.php",
            data: formData,
            success: function(response) {
                // Handle the response from login_handle.php
                if (response === "success") {
                    showAlertSuccess("Job approved successfully.");
                    window.location.href = "job_status?ss_id=" + localStorage.getItem('user_token_payroll');
                    // You can redirect or perform other actions as needed
                } else {
                    showAlertError("Job approval failed. Please check your data.");
                }
            },
            error: function() {
                showAlertError("An error occurred while approving the job.");
            }
        });
    });

    $("#edit_job").submit(function(e) {
        e.preventDefault();

        // Collect form data
        var formData = {
            job_refno: $("#jb_id").val(),
            usercode : $("#user_code").val(),
            heading: $("#heading").val(),
            company_name: $("#company_name").val(),
            experience: $("#experience").val(),
            annual_package: $("#annual_package").val(),
            job_location: $("#job_location").val(),
            job_opening: $("#job_opening").val(),
            posted_date: $("#posted_date").val(),
            job_details: $("#editor").val(),
            type: 'edit_job'
        };

        // Validate each field
        if (!formData.heading || !formData.company_name || !formData.experience || !formData.annual_package || !formData.job_location || !formData.job_opening || !formData.posted_date || !formData.job_details || !formData.job_refno || !formData.usercode) {
            showAlertError("Please fill in all fields.");
            return;
        }

        // Data is valid, send it to login_handle.php via AJAX
        $.ajax({
            type: "POST",
            url: "login_handle.php",
            data: formData,
            success: function(response) {
                // Handle the response from login_handle.php
                if (response === "success") {
                    showAlertSuccess("Job edited successfully.");
                    window.location.href = "job_status?ss_id=" + localStorage.getItem('user_token_payroll');
                    // You can redirect or perform other actions as needed
                } else {
                    showAlertError("Job approval failed. Please check your data.");
                }
            },
            error: function() {
                showAlertError("An error occurred while Editing the job.");
            }
        });
    });

    $(".deletejob").click(function() {
        // Get the user ID from the 'id' attribute
        var job_refno = $(this).attr("id");        
        var type = 'delete_job';

        // Use JavaScript's confirm dialog to confirm deletion
        var confirmDelete = confirm("Do you want to delete this post?");
        
        if (confirmDelete) {
            $.ajax({
                type: "POST",
                url: "login_handle.php", // Change the URL to your PHP file
                data: {
                    id: job_refno,
                    type: type
                },
                success: function(response) {
                    // Handle the response from the server (e.g., show a success message)
                    if (response === "success") {
                        showAlertSuccess("Post deleted successfully.");
                        window.location.href = "list_jobs?ss_id=" + localStorage.getItem('user_token_payroll');
                    } else {
                        showAlertError("Failed to delete post.");
                    }
                },
                error: function() {
                    showAlertError("An error occurred while deleting the post.");
                }
            });
        }
    });

   
    // Validation for Primary Contact
    $('#contact_details').on('input', function () {
        let contactDetails = $(this).val().trim();
        let phonePattern = /^\+\d{1,3}-\d{9,12}$/;  // Strict Format

        if (contactDetails === "") {
            $('#mobile-error').hide();
            return;
        }

        if (!phonePattern.test(contactDetails)) {
            $('#mobile-error').text('Invalid format! Use +<country_code>-<number> like +31-1212121212.').show();
        } else {
            $('#mobile-error').hide();
        }
    });

    // Validation for Alternate Contact
    $('#alternate_contact_details').on('input', function () {
        let contactDetails = $(this).val().trim();
        let phonePattern = /^\+\d{1,3}-\d{9,12}$/;  // Same Format for Alternate

        if (contactDetails === "") {
            $('#alternate-mobile-error').hide();
            return;
        }

        if (!phonePattern.test(contactDetails)) {
            $('#alternate-mobile-error').text('Invalid format! Use +<country_code>-<number> like +91-1212121212.').show();
        } else {
            $('#alternate-mobile-error').hide();
        }
    });

    $("#linkedin").on("input", function () {
        const value = $(this).val().trim(); // Get the trimmed value of the input
        const isValid = value.startsWith("linkedin.com"); // Check if it starts with "linkedin.com"
        const user_token_payroll = localStorage.getItem('user_token_payroll'); 
        // Show error if input doesn't start with "linkedin.com" and is not empty
        if (value.length > 0 && !isValid) {
            $("#link-error").text('The URL must start with "linkedin.com".').show();
        } else {
            $("#link-error").hide(); // Hide the error if valid or empty
    
            // If valid, check if the LinkedIn profile exists
            if (isValid && value.length > 0) {
                $.ajax({
                    url: 'login_handle.php',
                    type: 'POST',
                    data: {
                        linkedin: value,
                        type: 'check_linkedin' // Type to check LinkedIn profile
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.exists) {
                            const link = `<a href="can_edit?ss_id=${user_token_payroll}&can_id=${response.can_code}">${response.can_code}</a>`;
                            $('#link-error').html(`LinkedIn profile already exists with candidate code ${link}`).show().css('color', 'red');
                            $('#linkedin').focus();
                        } else {
                            $('#link-error').text('LinkedIn profile is unique').show().css('color', 'green');
                        }
                    },
                    error: function (error) {
                        $('#link-error').text('An error occurred while checking LinkedIn profile.').show().css('color', 'red');
                    }
                });
            }
        }
    });
    
    $('#email_id').on('input', function() {
        const email = $(this).val().trim();
        const user_token_payroll = localStorage.getItem('user_token_payroll');       
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email === '') {
            $('#email-error').text('Email cannot be empty!').show().css('color', 'red');
            $('#email_id').focus();
            return;
        }
        if (!emailPattern.test(email)) {
            $('#email-error').text('Invalid email format!').show().css('color', 'red');
            $(this).focus();
            return;
        }
    
        $.ajax({
            url: 'login_handle.php',
            type: 'POST',
            data: {
                email: email,
                type: 'check_email'                
            },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                if (response.exists) {
                    const link = `<a href="can_edit?ss_id=${user_token_payroll}&can_id=${response.can_code}">${response.can_code}</a>`;
                    $('#email-error').html(`Email already exists with candidate code ${link}`).show().css('color', 'red');
                    $('#email_id').focus();
                } else {
                    $('#email-error').text('Email Id is unique').show().css('color', 'green');
                }
            },
            error: function(error) {
                $('#email-error').text('An error occurred while checking email.').show().css('color', 'red');
            }
        });
    });
    
    $('#alternate_email_id').on('input', function() {
        const email = $(this).val().trim();
        const user_token_payroll = localStorage.getItem('user_token_payroll');      
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email === '') {
            $('#alternate-email-error').text('Email cannot be empty!').show().css('color', 'red');
            $('#alternate_email_id').focus();
            return;
        }
        if (!emailPattern.test(email)) {
            $('#alternate-email-error').text('Invalid email format!').show().css('color', 'red');
            $(this).focus();
            return;
        }
    
        $.ajax({
            url: 'login_handle.php',
            type: 'POST',
            data: {
                email: email,
                type: 'check_email'                
            },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                if (response.exists) {
                    const link = `<a href="can_edit?ss_id=${user_token_payroll}&can_id=${response.can_code}">${response.can_code}</a>`;
                    $('#alternate-email-error').html(`Email already exists with candidate code ${link}`).show().css('color', 'red');
                    $('#alternate_email_id').focus();
                } else {
                    $('#alternate-email-error').text('Email Id is unique').show().css('color', 'green');
                }
            },
            error: function(error) {
                $('#alternate-email-error').text('An error occurred while checking email.').show().css('color', 'red');
            }
        });
    });
    
    $('#add_candidate').on('submit', function (e) {
        e.preventDefault(); 
    
        let type = 'add_candidate';
        const user_token = $('#user_token').val();
        let user_code = $('#user_code').val();
        let user_name = $('#user_name').val();
        let candidateName = $('#candidate_name').val(); 
        let linkedin = $('#linkedin').val();
        let languages = [];
        $('input[name="languages[]"]:checked').each(function() {
            languages.push($(this).val());
        });
        let languagesString = languages.join(',');
        

        let roleAddressed = $('#role_addressed').val();
        let currentLocation = $('#current_location').val();
        let preferredLocation = $('#preferred_location').val();
        let currentPosition = $('#current_position').val();
        let experience = $('#experience').val();
        let noticePeriod = $('#notice_period').val();
        let currentEmployer = $('#current_employer').val();
        let currentSalary = $('#current_salary').val();
        let expectedSalary = $('#expected_salary').val();
        let canJoin = $('#can_join').val();
        let currentDailyRate = $('#current_daily_rate').val();
        let expectedDailyRate = $('#expected_daily_rate').val();

        let currentWorkingStatus = $('input[name="current_working_status"]:checked').val() || '';
        let currentAgency = $('#current_agency').val();
        var lead_type_role = $("#lead_type_role").is(":checked") ? 1 : 0;
        let leadType = $('input[name="lead_type"]:checked').val() || '';
        let workAuthStatus = $('input[name="work_auth_status"]:checked').val() || '';
        let followUp = $('input[name="follow_up"]:checked').val() || '';
        let followUpDate = $('#follow_up_date').val();
        
        let consent = $('#consent')[0].files[0];
        let candidateCv = $('#candidate_cv')[0].files[0];
        let consultancyCv = $('#consultancy_cv')[0].files[0];        
        let extraDetails = $('#extra_details').val();
        let faceToFace = $('#face_to_face').val();        
        
        let skills = [];
        $('input[name="skill_set[]"]:checked').each(function() {
            skills.push($(this).val());
        });
        
        // Convert skills array to comma-separated string, or use empty string if no skills
        if (skills.length === 0) {
            skills = '';
        } else {
            skills = skills.join(', '); // Convert array to comma-separated string if not empty
        }
        
        
        let textAreaData = {};
        $('textarea').each(function() {
            let id = $(this).data('id'); // Get the unique ID from data-id attribute
            let value = $(this).val();   // Get the value of the textarea
            if (id) { // Check if id is defined
                // Initialize array if not already
                if (!textAreaData[id]) {
                    textAreaData[id] = [];
                }
                // Add the value to the array for this ID
                if (value) {
                    textAreaData[id].push(value);
                }
            }
        });
        let textAreaDataJson = JSON.stringify(textAreaData);


        let contactDetails = $('#contact_details').val().trim();
        let alternateContactDetails = $('#alternate_contact_details').val().trim();
        let phonePattern = /^\+\d{1,3}-\d{9,12}$/;  // Format: +CountryCode-Number

        // Validate Primary Contact
        if (contactDetails !== "") {
            if (!phonePattern.test(contactDetails)) {
                $('#mobile-error').text('Invalid format! Use +<country_code>-<number> like +31-1212121212.').show();
                $('#contact_details').focus();
                return false;
            } else {
                $('#mobile-error').hide();
            }
        } else {
            $('#mobile-error').hide();
        }

        // Validate Alternate Contact
        if (alternateContactDetails !== "") {
            if (!phonePattern.test(alternateContactDetails)) {
                $('#alternate-mobile-error').text('Invalid format! Use +<country_code>-<number> like +91-1212121212.').show();
                $('#alternate_contact_details').focus();
                return false;
            } else {
                $('#alternate-mobile-error').hide();
            }
        } else {
            $('#alternate-mobile-error').hide();
        }

    
        const email = $('#email_id').val().trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (candidateName === '') {            
            $('#candidate_name').focus();
            alert('Name must have a value!');
            return;
        }
        if (email === '') {
            $('#email-error').text('Email cannot be empty!').show().css('color', 'red');
            $('#email_id').focus();
            return;
        }
        if (!emailPattern.test(email)) {
            $('#email-error').text('Invalid email format!').show().css('color', 'red');
            $('#email_id').focus();
            return;
        }

        const alternate_email = $('#alternate_email_id').val().trim();
        const alternateemailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (alternate_email !== '' && !alternateemailPattern.test(alternate_email)) {
            $('#alternate-email-error').text('Invalid email format!').show().css('color', 'red');
            $('#alternate_email_id').focus();
            return;
        } else {
            $('#alternate-email-error').hide(); // Hide error if valid or empty
        }
        
    
        const formData = new FormData();
        formData.append('type', type);
        formData.append('user_token', user_token);
        formData.append('user_code', user_code);
        formData.append('user_name', user_name);
        formData.append('email_id', email);
        formData.append('alternate_email', alternate_email);
        formData.append('contact_details', contactDetails);
        formData.append('alternate_contact_details', alternateContactDetails);
        formData.append('candidate_name', candidateName);
        formData.append('linkedin', linkedin);
        formData.append('languages', languagesString);
        formData.append('role_addressed', roleAddressed);
        formData.append('current_location', currentLocation);
        formData.append('preferred_location', preferredLocation);
        formData.append('current_position', currentPosition);
        formData.append('experience', experience);
        formData.append('notice_period', noticePeriod);
        formData.append('current_employer', currentEmployer);
        formData.append('current_salary', currentSalary);
        formData.append('expected_salary', expectedSalary);
        formData.append('can_join', canJoin);
        formData.append('current_daily_rate', currentDailyRate);
        formData.append('expected_daily_rate', expectedDailyRate);
        formData.append('current_working_status', currentWorkingStatus);
        formData.append('current_agency', currentAgency);        
        formData.append('lead_type_role', lead_type_role);
        formData.append('lead_type', leadType);
        formData.append('work_auth_status', workAuthStatus);
        formData.append('follow_up', followUp);
        formData.append('follow_up_date', followUpDate);
        formData.append('extra_details', extraDetails);
        formData.append('face_to_face', faceToFace);
        formData.append('skills', skills);
        formData.append('textAreaData', textAreaDataJson);
        
        if (consent) formData.append('consent', consent);
        if (candidateCv) formData.append('candidate_cv', candidateCv);
        if (consultancyCv) formData.append('consultancy_cv', consultancyCv);
        
        $.ajax({
            url: 'login_handle.php',
            type: 'POST',
            data: {
                email: email,
                type: 'check_email'
            },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    const link = `<a href="can_edit?ss_id=${user_token}&can_id=${response.can_code}">${response.can_code}</a>`;
                    $('#email-error').html(`Email already exists with candidate code ${link}`).show().css('color', 'red');
                    $('#email_id').focus();
                }  else {
                    $('#email-error').hide();
                    $.ajax({
                        url: 'login_handle.php', 
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function (data) {
                            if (data === 'success') {
                                alert("Candidate added successfully!");
                                window.location.reload();
                            } else {
                                alert(data);
                            }
                        },
                        error: function (error) {
                            alert("An error occurred: " + error);
                        },
                    });
                }
            },
            error: function (error) {
                alert("An error occurred: " + error);
            },
        });
    });









  });