// Send to candidate
$to = $email;
$subject = "Application Received - " . $job['job_title'];
$message = "Dear $candidate_name,

Thank you for applying for the position of {$job['job_title']}. 
We have received your application and will review it shortly.

Application Reference: $application_code

Best regards,
ProConsultancy Team";

mail($to, $subject, $message);

// Send to recruiter (get from job assignment)
// TODO: Implement recruiter notification