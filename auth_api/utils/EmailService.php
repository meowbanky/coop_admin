<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/../../vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../../');
$dotenv->load();

class EmailService {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        // Configure PHPMailer
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'];        // mail.emmaggi.com
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME']; // coopoouth@emmaggi.com
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;                       // Correct port

            // Add these additional settings
            $this->mailer->SMTPDebug = 0;                    // Enable detailed debug output
            $this->mailer->From = $_ENV['SMTP_FROM'];        // Set sender email
            $this->mailer->FromName = "OOUTH COOP";          // Set sender name

            // Set timeout values
            $this->mailer->Timeout = 30;                     // Timeout for SMTP connection
            $this->mailer->SMTPKeepAlive = true;            // Keep connection alive

            // Enable debug logging
            $this->mailer->Debugoutput = function($str, $level) {
                error_log("SMTP Debug: $str");
            };

            // Verify connection
            if (!$this->mailer->smtpConnect()) {
                throw new Exception("SMTP Connection Failed: " . $this->mailer->ErrorInfo);
            }

        } catch (Exception $e) {
            error_log("SMTP Configuration Error: " . $e->getMessage());
            error_log("Full Error Info: " . print_r($this->mailer->ErrorInfo, true));
            throw $e;
        }
    }

    public function sendOTP($email, $otp) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->addBCC('bankole.adesoji@gmail.com');
            $this->mailer->addBCC('oluwaflorencetoyin@gmail.com');

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Password Reset OTP';

            // HTML email body
            $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #333;'>Password Reset Request</h2>
                    <p>You have requested to reset your password. Please use the following OTP code to proceed:</p>
                    <div style='background-color: #f5f5f5; padding: 15px; text-align: center; margin: 20px 0;'>
                        <h1 style='color: #007bff; letter-spacing: 5px;'>$otp</h1>
                    </div>
                    <p>This OTP will expire in 15 minutes.</p>
                    <p>If you didn't request this password reset, please ignore this email or contact support if you have concerns.</p>
                    <hr style='margin: 20px 0;'>
                    <p style='color: #666; font-size: 12px;'>This is an automated message, please do not reply.</p>
                </div>
            ";

            // Plain text alternative
            $textBody = "
                Password Reset Request\n
                You have requested to reset your password. Please use the following OTP code to proceed:\n
                $otp\n
                This OTP will expire in 15 minutes.\n
                If you didn't request this password reset, please ignore this email or contact support if you have concerns.
            ";

            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody;

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Error sending OTP email: {$e->getMessage()}");
            return false;
        }
    }

    public function sendApprovalNotification($staffEmail, $staffName, $changes) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($staffEmail);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Profile Changes Approved';

            // HTML email body
            $changesHtml = '';
            foreach ($changes as $field => $value) {
                $changesHtml .= "<li>$field: $value</li>";
            }

            $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #333;'>Profile Changes Approved</h2>
                    <p>Dear $staffName,</p>
                    <p>Your profile changes have been approved.</p>
                    <div style='background-color: #f5f5f5; padding: 15px; margin: 20px 0;'>
                        <h3>Changes:</h3>
                        <ul>
                            $changesHtml
                        </ul>
                    </div>
                    <hr style='margin: 20px 0;'>
                    <p style='color: #666; font-size: 12px;'>This is an automated message, please do not reply.</p>
                </div>
            ";

            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $this->createTextVersion($htmlBody);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Error sending approval email: {$e->getMessage()}");
            return false;
        }
    }

    public function sendRejectionNotification($staffEmail, $staffName, $reason) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($staffEmail);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Profile Changes Rejected';

            $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #333;'>Profile Changes Rejected</h2>
                    <p>Dear $staffName,</p>
                    <p>Your profile changes have been rejected.</p>
                    <div style='background-color: #f5f5f5; padding: 15px; margin: 20px 0;'>
                        <h3>Reason:</h3>
                        <p>$reason</p>
                    </div>
                    <hr style='margin: 20px 0;'>
                    <p style='color: #666; font-size: 12px;'>This is an automated message, please do not reply.</p>
                </div>
            ";

            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $this->createTextVersion($htmlBody);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Error sending rejection email: {$e->getMessage()}");
            return false;
        }
    }

    private function createTextVersion($html) {
        // Simple HTML to text conversion
        $text = strip_tags($html);
        $text = str_replace('&nbsp;', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}