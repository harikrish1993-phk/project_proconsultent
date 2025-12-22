<?php
/**
 * Centralized Mailer Class
 * Uses PHPMailer for robust and secure email sending.
 */

namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer classes
require_once __DIR__ . '/../../panel/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../panel/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../panel/PHPMailer/src/SMTP.php';

class Mailer {
    private $mail;

    public function __construct() {
        // Load configuration constants
        if (!defined('SMTP_HOST')) {
            require_once __DIR__ . '/../config/config.php';
        }

        $this->mail = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = SMTP_HOST;
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = SMTP_USERNAME;
            $this->mail->Password   = SMTP_PASSWORD;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use ENCRYPTION_SMTPS for port 465
            $this->mail->Port       = SMTP_PORT;
            $this->mail->CharSet    = 'UTF-8';
            $this->mail->isHTML(true);
            
            // Sender settings
            $this->mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
        } catch (Exception $e) {
            error_log("Mailer initialization error: {$e->getMessage()}");
            // In a real application, you might throw an exception or set a flag
        }
    }

    /**
     * Sends an email using a template file.
     *
     * @param string $toEmail Recipient email address.
     * @param string $toName Recipient name.
     * @param string $subject Email subject.
     * @param string $templatePath Path to the HTML template file.
     * @param array $data Data to replace placeholders in the template.
     * @return bool True on success, false on failure.
     */
    public function sendTemplateEmail(string $toEmail, string $toName, string $subject, string $templatePath, array $data = []): bool {
        try {
            // Clear previous recipients and attachments
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();

            // Recipient
            $this->mail->addAddress($toEmail, $toName);
            
            // Content
            $this->mail->Subject = $subject;
            
            // Load template content
            if (!file_exists($templatePath)) {
                throw new Exception("Email template not found: {$templatePath}");
            }
            $body = file_get_contents($templatePath);

            // Replace placeholders
            foreach ($data as $key => $value) {
                $body = str_replace("{{{$key}}}", htmlspecialchars($value), $body);
            }
            
            // Evaluate PHP in template (for COMPANY_NAME etc.)
            ob_start();
            eval('?>' . $body);
            $finalBody = ob_get_clean();

            $this->mail->Body = $finalBody;
            $this->mail->AltBody = strip_tags($finalBody); // Plain text version

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email failed to send to {$toEmail}. Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Sends a simple text/HTML email.
     *
     * @param string $toEmail Recipient email address.
     * @param string $toName Recipient name.
     * @param string $subject Email subject.
     * @param string $body HTML body content.
     * @return bool True on success, false on failure.
     */
    public function sendSimpleEmail(string $toEmail, string $toName, string $subject, string $body): bool {
        try {
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();

            $this->mail->addAddress($toEmail, $toName);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Simple email failed to send to {$toEmail}. Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
}
