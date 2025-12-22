$(document).ready(function () {

    fetchDataHome();
    fetchDataJob();
    fetchJobsBanner();

    $("#cv_ref_data").submit(function (e) { 
        e.preventDefault(); 
        
        $(".error-message").remove();

        
        var job_refno = $("#job_refno").val();
        var created_by = $("#created_by").val();
        var emp_name = $("#emp_name").val();
        var emp_email = $("#emp_email").val();
        var emp_mob = $("#emp_mob").val();
        var emp_file = $("#emp_file")[0].files[0];
        var created_email = $("#created_email").val();
        var created_name = $("#created_name").val();
        var emp_job = $("#emp_job").val();

        
        var isValid = true;

        if (emp_name.trim() === "") {
            isValid = false;
            $("#emp_name").after('<span class="error-message" style="font-size: 14px;color: red;">Name is required</span>');
        }

        var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
        if (!emailPattern.test(emp_email)) {
            isValid = false;
            $("#emp_email").after('<span class="error-message" style="font-size: 14px;color: red;">Invalid email address</span>');
        }

        if (emp_mob.trim() === "") {
            isValid = false;
            $("#emp_mob").after('<span class="error-message" style="color: red;">Mobile no is required</span>');
        }

        if (emp_file) {
            if (emp_file.size > 2 * 1024 * 1024) { // 2MB
                isValid = false;
                $("#emp_file").after('<span class="error-message" style="font-size: 14px;color: red;">File size exceeds 2MB</span>');
            } else {
                var allowedFileTypes = ["application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"];
                if (allowedFileTypes.indexOf(emp_file.type) === -1) {
                    isValid = false;
                    $("#emp_file").after('<span class="error-message" style="font-size: 14px;color: red;">Invalid file type. Only PDF, DOC, and DOCX are allowed.</span>');
                }
            }
        } else {
            isValid = false;
            $("#emp_file").after('<span class="error-message" style="font-size: 14px;color: red;">File is required</span>');
        }

        if (isValid) {
           
           
            var formData = new FormData();
            formData.append("job_refno", job_refno);
            formData.append("created_by", created_by);
            formData.append("emp_name", emp_name);
            formData.append("emp_email", emp_email);
            formData.append("emp_mob", emp_mob);
            formData.append("emp_file", emp_file);
            formData.append("created_email", created_email);
            formData.append("created_name", created_name);
            formData.append("emp_job", emp_job);            
            formData.append("type", 'uploadcv_ref');
            
            
            $.ajax({
                type: "POST",
                url: "panel/fetchData.php", 
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                   
                    if (response) {               
                    alert("Thank you !! Your CV is submitted and we will contact you.");                    
                    window.location.href = "jobs.html";
                    } else {               
                    alert("Please enter valid email");
                    }
                   
                },
                error: function (xhr, status, error) {
                    alert(error);
                   
                },
            });
        }
    });

    $("#form_query").submit(function (e) {
        e.preventDefault();
    
       
        $(".error-message").remove();
    
        
        var qry_name = $("#qry_name").val();
        var qry_mail = $("#qry_mail").val();
        var qry_subject = $("#qry_subject").val();
        var qry_msg = $("#qry_msg").val();
    
        
        var isValid = true;
    
        
        var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
        if (!emailPattern.test(qry_mail)) {
            isValid = false;
            $("#qry_mail").after('<span class="error-message" style="font-size: 14px;color: red;">Invalid email address</span>');
        } 
       
        if (qry_name.trim() === "") {
            isValid = false;
            $("#qry_name").after('<span class="error-message" style="font-size: 14px;color: red;">Name is required</span>');
        }
    
        if (qry_subject.trim() === "") {
            isValid = false;
            $("#qry_subject").after('<span class="error-message" style="font-size: 14px;color: red;">Subject is required</span>');
        }
    
        if (qry_msg.trim() === "") {
            isValid = false;
            $("#qry_msg").after('<span class="error-message" style="font-size: 14px;color: red;">Message is required</span>');
        }
    
        if (isValid) {
            
            var formData = new FormData();
            formData.append("qry_name", qry_name);
            formData.append("qry_mail", qry_mail);
            formData.append("qry_subject", qry_subject);
            formData.append("qry_msg", qry_msg);
            formData.append("type", 'contact_data');
    
           
            $.ajax({
            type: "POST",
            url: "panel/fetchData.php",
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                    if (response) {               
                    alert("Thankyou for contact with us. We will get back to you in 24 hours.");                    
                    window.location.href = "jobs.html";
                    } else {               
                    alert("Please enter valid email");
                    }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: " + error);
                console.log(xhr.responseText); // This line will print the error response
            }


            });
        }
    });


     
    $("#cvForm").submit(function (e) {
        e.preventDefault();
        
        
        var cvName = $("#cvName").val();
        var cvMobile = $("#cvMobile").val();
        var cvEmail = $("#cvEmail").val();
        var cvInterest = $("#cvInterest").val();
        var cvref = 'Direct';
        var cvFile = $("#cvFile")[0].files[0];

        
        var isValid = true;

        if (cvName.trim() === "") {
            isValid = false;
            $("#cvName").after('<span class="error-message" style="color: red;">Name is required</span>');
        }

       
        if (cvMobile.trim() === "") {
            isValid = false;
            $("#cvMobile").after('<span class="error-message" style="color: red;">Mobile no is required</span>');
        }

        var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
        if (!emailPattern.test(cvEmail)) {
            isValid = false;
            $("#cvEmail").after('<span class="error-message" style="color: red;">Invalid email address</span>');
        }

        if (cvInterest.trim() === "") {
            isValid = false;
            $("#cvInterest").after('<span class="error-message" style="color: red;">Interest is required</span>');
        }

        if (cvFile) {
            if (cvFile.size > 2 * 1024 * 1024) { // 2MB
                isValid = false;
                $("#cvFile").after('<span class="error-message" style="font-size: 14px;color: red;">File size exceeds 2MB</span>');
            } else {
                var allowedFileTypes = ["application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"];
                if (allowedFileTypes.indexOf(cvFile.type) === -1) {
                    isValid = false;
                    $("#cvFile").after('<span class="error-message" style="font-size: 14px;color: red;">Invalid file type. Only PDF, DOC, and DOCX are allowed.</span>');
                }
            }
        } else {
            isValid = false;
            $("#cvFile").after('<span class="error-message" style="font-size: 14px;color: red;">File is required</span>');
        }

        

        if (isValid) {
            
            
            var formData = new FormData();
            formData.append("emp_name", cvName);
            formData.append("emp_mob", cvMobile);
            formData.append("emp_email", cvEmail);
            formData.append("created_by", cvInterest);
            formData.append("emp_file", cvFile);
            formData.append("job_refno", cvref);
            formData.append("type", 'uploadcv');


            
            $.ajax({
                type: "POST",
                url: "panel/fetchData.php", 
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    
                    if (response) {               
                    alert("Thank you !! Your CV is submitted and we will contact you.");                    
                    window.location.href = "jobs.html";
                    } else {               
                    alert("Please enter valid email");
                    }
                    
                },
                error: function (xhr, status, error) {
                    alert(error);
                    
                },
            });
        }
    });










    


});




function fetchDataHome(){
    $.ajax({
        type: "POST",
        url: "panel/fetchData.php",
        data: { type: "fetchData" },
        success: function (response) {
            try {
                // Process the response here directly
                var jobsArray = response;
                
                // Get the container where you want to append the div elements
                var container = $("#jobs-container");

                // Iterate over the array and create dynamic links
                jobsArray.forEach(function (job) {
                    // Create a new div element
                  
                    // Create a new anchor element with the appropriate href
                    var anchor = $("<a>")
                        .addClass("card-1 py-3 fetch")
                        .attr("href", "jobpost.php?ref_no=" + job.job_refno)
                        .attr("style", "padding: 1em;")
                        .attr("data-wow-delay", "0.9s")
                        .text(job.heading);

                    // Append the div to the container
                    container.append(anchor);
                });
            } catch (error) {
               alert("Error processing response: " + error.message);
            }
        },
        error: function () {
            console.log("An error occurred during the AJAX request.");
        }
    });
}

function fetchDataJob(){ 
  
        var container = $("#jobspost-container");
        var pageInfo = $("#pageInfo");
        var prevPage = $("#prevPage");
        var nextPage = $("#nextPage");
        var currentPage = 1;
        var postsPerPage = 10; // Number of posts per page
    
        // Function to fetch and render posts for the given page
        function fetchPosts(page) {
            $.ajax({
                type: "POST",
                url: "panel/fetchData.php",
                data: { type: "fetchAllData", page: page, postsPerPage: postsPerPage },
                success: function (response) {
                    try {
                        var jobsArray = response.data;
    
                        // Clear the container
                        container.empty();
    
                        // Render the job posts as before
                        jobsArray.forEach(function (blog) {

                            var columnDiv = $("<div>").addClass("col-md-6 g-5 wow slideInDown");
                            var blogItemDiv = $("<div>").addClass("blog-item bg-light rounded overflow-hidden");
                            var blogContentDiv = $("<div>").addClass("p-4");
                            
                            // Create an anchor element with the href attribute
                            var blogLink = $("<a>")
                                .attr("href", "jobpost.php?ref_no=" + blog.job_refno)
                                .addClass("text-decoration-none") // Optionally, remove the default link underline
                                .append(
                                    // Create the content inside the anchor
                                    $("<div>").addClass("d-flex mb-3")
                                        .append(`<small class="me-3"><i class="far fa-user text-primary me-2"></i>posted by ${blog.name}</small>`)
                                        .append(`<small><i class="far fa-calendar-alt text-primary me-2"></i>${blog.posted_date}</small>`)
                                )
                                .append(
                                    $("<h4>").addClass("mb-3").text(blog.heading)
                                )
                                .append(
                                    $("<div>").addClass("d-flex mb-3")
                                        .append(`<small class="me-3"><i class="fa fa-map-marker text-primary me-2"></i>${blog.job_location}</small>`)
                                        .append(`<small><i class="fa fa-suitcase text-primary me-2"></i>${blog.experience}</small>`)
                                        
                                        
                                )
                                .append(
                                    $("<button>").addClass("btn btn-primary")
                                    .text("Apply Now") // Add the "Apply Now" text within a <p> tag
                                );;
                            
                            // Append the anchor element to the blog content
                            blogContentDiv.append(blogLink);
                            
                            // Append the entire blog item to the container
                            blogItemDiv.append(blogContentDiv);
                            columnDiv.append(blogItemDiv);
                            
                            // Append the new blog div to the container
                            container.append(columnDiv);
                            


                        });
    
                        // Update pagination info
                        var totalPages = response.totalPages;
                        pageInfo.text(`Page ${currentPage} of ${totalPages}`);
    
                        // Update button states
                        prevPage.prop("disabled", currentPage === 1);
                        nextPage.prop("disabled", currentPage === totalPages);
                    } catch (error) {
                        console.error("Error processing job response: " + error.message);
                    }
                },
                error: function () {
                    console.log("An error occurred during the AJAX request for job data.");
                }
            });
        }
    
        // Initial fetch for the first page
        fetchPosts(currentPage);
    
        // Handle Previous button click
        prevPage.click(function () {
            if (currentPage > 1) {
                currentPage--;
                fetchPosts(currentPage);
            }
        });
    
        // Handle Next button click
        nextPage.click(function () {
            currentPage++;
            fetchPosts(currentPage);
        });

} 


function fetchJobsBanner() {
    $.ajax({
        type: "POST",
        url: "panel/fetchData.php",
        data: { type: "fetchDataatjobpage" },
        success: function (response) {
            try {
                // Process the response here directly
                var jobsArray = response;

                // Get the container where you want to append the div elements
                var container = $("#jobs-banner-container");

                // Iterate over the array and create dynamic links
                jobsArray.forEach(function (job) {
                    // Create a new anchor element
                    var anchor = $("<a>")
                        .addClass("h5 fw-semi-bold bg-light rounded py-2 px-3 mb-2")
                        .attr("href", "jobpost.php?ref_no=" + job.job_refno)
                        .html('<i class="bi bi-arrow-right me-2"></i>' + job.heading);

                    // Append the anchor to the container
                    container.append(anchor);
                });
            } catch (error) {
                alert("Error processing response: " + error.message);
            }
        },
        error: function () {
            console.log("An error occurred during the AJAX request.");
        }
    });
}

         


