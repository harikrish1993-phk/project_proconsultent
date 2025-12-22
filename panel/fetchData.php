<?php

require 'db_conn.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

                    require 'PHPMailer/src/Exception.php';
                    require 'PHPMailer/src/PHPMailer.php';
                    require 'PHPMailer/src/SMTP.php';

if ($_POST["type"] == 'fetchData') {

  
    $sql = "SELECT * FROM jobs JOIN user ON jobs.created_by = user.user_code WHERE job_status = '1' ORDER BY jobs.id DESC LIMIT 25;";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        
        $jobsArray = array();

        while ($row = $result->fetch_assoc()) {
            
            $jobsArray[] = $row;
        }

        
        header("Content-Type: application/json");
        echo json_encode($jobsArray);
    } else {
        echo "No jobs found.";
    }

    
    
    
}

if ($_POST["type"] == 'fetchAllData') {
    
    $page = $_POST["page"];
    $postsPerPage = $_POST["postsPerPage"];

    
    $offset = ($page - 1) * $postsPerPage;

    
    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM jobs JOIN user ON jobs.created_by = user.user_code WHERE jobs.job_status = '1' ORDER BY jobs.created DESC, jobs.job_refno LIMIT ?, ?;";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $postsPerPage);
    $stmt->execute();

    
    $result = $stmt->get_result();
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    
    $totalRows = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
    $totalPages = ceil($totalRows / $postsPerPage);

    
    $response = array(
        "data" => $data,
        "totalPages" => $totalPages
    );

    
    header("Content-Type: application/json");
    echo json_encode($response);
}

if ($_POST["type"] == 'uploadcv') {  
          
    
    
        $job_refno = $_POST["job_refno"];
        $created_by = $_POST["created_by"];
        $emp_name = $_POST["emp_name"];
        $emp_email = $_POST["emp_email"];
        $emp_mob = $_POST["emp_mob"];

        
        $target_dir = "uploadedcv/"; 
        $original_file_name = basename($_FILES["emp_file"]["name"]);
        $ext = pathinfo($original_file_name, PATHINFO_EXTENSION);

        
        $timestamp = time(); 
        $unique_filename = $timestamp . '_' . uniqid() . '.' . $ext;
        $target_file = $target_dir . $unique_filename;

        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        
        if (file_exists($target_file)) {
            echo "Sorry, a file with the same name already exists.";
            $uploadOk = 0;
        }

        
        if ($_FILES["emp_file"]["size"] > 2 * 1024 * 1024) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        
        $allowedFileTypes = ["pdf", "doc", "docx"];
        if (!in_array($ext, $allowedFileTypes)) {
            echo "Sorry, only PDF, DOC, and DOCX files are allowed.";
            $uploadOk = 0;
        }

       
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        } else {
            
            if (move_uploaded_file($_FILES["emp_file"]["tmp_name"], $target_file)) {
                
                $file_path = $target_file;

                
                $sql = "INSERT INTO submittedcv (job_refno, created_by, emp_name, emp_email, emp_mob, emp_file_url)
                        VALUES ('$job_refno', '$created_by', '$emp_name', '$emp_email', '$emp_mob', '$file_path')";

                if ($conn->query($sql) === TRUE) {
                    
                    $email_template = file_get_contents('../cv_direct_mail.html');

                   
                    $email_template = str_replace('{{user_name}}', $emp_name, $email_template);
                    $email_template = str_replace('{{user_mobile}}', $emp_mob, $email_template);
                    $email_template = str_replace('{{user_email}}', $emp_email, $email_template);
                    $email_template = str_replace('{{user_interest}}', $created_by, $email_template);
                    $email_template = str_replace('{{user_file}}', $file_path, $email_template);

                        $mail = new PHPMailer;
                        $mail->isSMTP();
                        //$mail->SMTPDebug = 2;
                        $mail->Host = 'smtp.hostinger.com';
                        $mail->Port = 587;
                        $mail->SMTPAuth = true;
                        $mail->Username = '<?php echo COMPANY_EMAIL; ?>';
                        $mail->Password = 'Amsterdam123#';
                        $mail->setFrom('<?php echo COMPANY_EMAIL; ?>', 'test consultancy');
                        $mail->addReplyTo('<?php echo COMPANY_EMAIL; ?>', 'test consultancy');  
                        $to = $emp_email; 
                        $mail->addAddress($to);
                        $query = "SELECT `cc`, `bcc` FROM user_mail WHERE type = 'user_mail_cv'";
                        $result = mysqli_query($conn, $query);
                        if ($result) {
                            $row = mysqli_fetch_assoc($result);                            
                            $ccEmail = isset($row['cc']) ? $row['cc'] : '';
                            if (!empty($ccEmail)) {
                                $mail->addCC($ccEmail);
                            }
                            $bccEmail = isset($row['bcc']) ? $row['bcc'] : '';
                            if (!empty($bccEmail)) {
                                $mail->addBCC($bccEmail);
                            }                        
                           
                        } else {
                            echo "Error: " . mysqli_error($conn); // Handle query execution error
                        }
                        $mail->isHTML(true);
                        $mail->Subject = 'Your CV has been submitted successfully - proconsultancy.be';
                        $mail->msgHTML($email_template);
                        $mail->Body = $email_template;
                        if ($mail->send()) {
                            echo "CV submitted. We will get back to you in 24 hours.";
                        } else {
                            echo "Mailer Error: " . $mail->ErrorInfo;
                        }
                   
                    
                    


                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    
    
    
} 

if ($_POST["type"] == 'uploadcv_ref') {  
          
   
   
        $job_refno = $_POST["job_refno"];
        $created_by = $_POST["created_by"];
        $emp_name = $_POST["emp_name"];
        $emp_email = $_POST["emp_email"];
        $emp_mob = $_POST["emp_mob"];
        $created_email = $_POST["created_email"];
        $created_name = $_POST["created_name"];
        $emp_job = $_POST["emp_job"];

       
        $target_dir = "uploadedcv/";
        $original_file_name = basename($_FILES["emp_file"]["name"]);
        $ext = pathinfo($original_file_name, PATHINFO_EXTENSION);

        
        $timestamp = time(); 
        $unique_filename = $timestamp . '_' . uniqid() . '.' . $ext;
        $target_file = $target_dir . $unique_filename;

        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        
        if (file_exists($target_file)) {
            echo "Sorry, a file with the same name already exists.";
            $uploadOk = 0;
        }

        
        if ($_FILES["emp_file"]["size"] > 2 * 1024 * 1024) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        
        $allowedFileTypes = ["pdf", "doc", "docx"];
        if (!in_array($ext, $allowedFileTypes)) {
            echo "Sorry, only PDF, DOC, and DOCX files are allowed.";
            $uploadOk = 0;
        }

      
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        } else {
            
            if (move_uploaded_file($_FILES["emp_file"]["tmp_name"], $target_file)) {
              
                $file_path = $target_file;

                $sql = "INSERT INTO submittedcv (job_refno, created_by, emp_name, emp_email, emp_mob, emp_file_url)
                        VALUES ('$job_refno', '$created_by', '$emp_name', '$emp_email', '$emp_mob', '$file_path')";

                if ($conn->query($sql) === TRUE) {
                    
                    $email_template = file_get_contents('../cv_ref_mail.html');

                    
                    $email_template = str_replace('{{user_name}}', $emp_name, $email_template);
                    $email_template = str_replace('{{user_mobile}}', $emp_mob, $email_template);
                    $email_template = str_replace('{{user_email}}', $emp_email, $email_template);
                    $email_template = str_replace('{{user_interest}}', $job_refno, $email_template);
                    $email_template = str_replace('{{user_file}}', $file_path, $email_template);
                    $email_template = str_replace('{{emp_name}}', $created_name, $email_template);
                    $email_template = str_replace('{{job_post}}', $emp_job, $email_template);
               
                        $mail = new PHPMailer;
                        $mail->isSMTP();
                        //$mail->SMTPDebug = 2;
                        $mail->Host = 'smtp.hostinger.com';
                        $mail->Port = 587;
                        $mail->SMTPAuth = true;
                        $mail->Username = '<?php echo COMPANY_EMAIL; ?>';
                        $mail->Password = 'Amsterdam123#';
                        $mail->setFrom('<?php echo COMPANY_EMAIL; ?>', 'test consultancy');
                        $mail->addReplyTo('<?php echo COMPANY_EMAIL; ?>', 'test consultancy');  
                        $to = $emp_email; 
                        $mail->addAddress($to);
                        $query = "SELECT `cc`, `bcc` FROM user_mail WHERE type = 'user_mail_cv'";
                        $result = mysqli_query($conn, $query);
                        if ($result) {
                            $row = mysqli_fetch_assoc($result);                            
                            $ccEmail = isset($row['cc']) ? $row['cc'] : '';
                            if (!empty($ccEmail)) {
                                $mail->addCC($ccEmail);
                            }
                            $bccEmail = isset($row['bcc']) ? $row['bcc'] : '';
                            if (!empty($bccEmail)) {
                                $mail->addBCC($bccEmail);
                            }                        
                           
                        } else {
                            echo "Error: " . mysqli_error($conn); // Handle query execution error
                        }
                        $mail->isHTML(true);
                        $mail->Subject = 'Your CV has been submitted successfully - proconsultancy.be';
                        $mail->msgHTML($email_template);
                        $mail->Body = $email_template;
                        if ($mail->send()) {
                            echo "CV submitted. We will get back to you in 24 hours.";
                        } else {
                            echo "Mailer Error: " . $mail->ErrorInfo;
                        }


                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    
    
    
} 

if ($_POST["type"] == 'contact_data') {
        
        $qry_name = $_POST["qry_name"];
        $qry_mail = $_POST["qry_mail"];
        $qry_subject = $_POST["qry_subject"];
        $qry_msg = $_POST["qry_msg"];

        
        if (empty($qry_name) || empty($qry_mail) || empty($qry_subject) || empty($qry_msg)) {
            echo "All fields are required.";
            exit;
        }       

        
        $sql = "INSERT INTO queries (qry_name, qry_mail, qry_subject, qry_msg) 
                VALUES ('$qry_name', '$qry_mail', '$qry_subject', '$qry_msg')";

        if ($conn->query($sql) === TRUE) {
            
                   
                    $email_template = file_get_contents('../contact_email.html');
                    $email_template = str_replace('{{user_name}}', $qry_name, $email_template);
                    $email_template = str_replace('{{user_mail}}', $qry_mail, $email_template);
                    $email_template = str_replace('{{user_subject}}', $qry_subject, $email_template);
                    $email_template = str_replace('{{user_message}}', $qry_msg, $email_template);                   
                    
                   

                        $mail = new PHPMailer;
                        $mail->isSMTP();
                        //$mail->SMTPDebug = 2;
                        $mail->Host = 'smtp.hostinger.com';
                        $mail->Port = 587;
                        $mail->SMTPAuth = true;
                        $mail->Username = '<?php echo COMPANY_EMAIL; ?>';
                        $mail->Password = 'Amsterdam123#';
                        $mail->setFrom('<?php echo COMPANY_EMAIL; ?>', 'test consultancy');
                        $mail->addReplyTo('<?php echo COMPANY_EMAIL; ?>', 'test consultancy');  
                        $to = $qry_mail; 
                        $mail->addAddress($to);
                        $query = "SELECT `cc`, `bcc` FROM user_mail WHERE type = 'user_mail_contact'";
                        $result = mysqli_query($conn, $query);
                        if ($result) {
                            $row = mysqli_fetch_assoc($result);                            
                            $ccEmail = isset($row['cc']) ? $row['cc'] : '';
                            if (!empty($ccEmail)) {
                                $mail->addCC($ccEmail);
                            }
                            $bccEmail = isset($row['bcc']) ? $row['bcc'] : '';
                            if (!empty($bccEmail)) {
                                $mail->addBCC($bccEmail);
                            }                        
                           
                        } else {
                            echo "Error: " . mysqli_error($conn); // Handle query execution error
                        }
                        $mail->isHTML(true);
                        $mail->Subject = 'proconsultancy.be - Thanks for Connecting with us!';
                        $mail->msgHTML($email_template);
                        $mail->Body = $email_template;
                        if ($mail->send()) {
                            echo "Thank you for contacting us. We will get back to you in 24 hours.";
                        } else {
                            echo "Mailer Error: " . $mail->ErrorInfo;
                        }

        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

       
        

}          
    
if ($_POST["type"] == 'fetchDataatjobpage') {

  
      $sql = "SELECT * FROM jobs,user where jobs.created_by=user.user_code and job_status='1' order by jobs.id desc limit 5";
      $result = $conn->query($sql);
  
      if ($result->num_rows > 0) {
         
          $jobsArray = array();
  
          while ($row = $result->fetch_assoc()) {             
              $jobsArray[] = $row;
          }
  
          
          header("Content-Type: application/json");
          echo json_encode($jobsArray);
      } else {
          echo "No jobs found.";
      }
  
      
      
      
  }









?>
