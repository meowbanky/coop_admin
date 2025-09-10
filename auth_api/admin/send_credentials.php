<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/EmailConfig.php';
require_once __DIR__ .'/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/credential_sender.log');

class BatchCredentialSender {
    private $conn;
    private $batchSize = 100;
    private $lockFile;

    public function __construct() {
        $this->lockFile = __DIR__ . '/credential_sender.lock';
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->ensureTrackingTableExists();
    }

    private function ensureTrackingTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS email_batch_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            last_processed_id INT NOT NULL,
            batch_number INT NOT NULL,
            processed_count INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        try {
            $this->conn->exec($sql);
        } catch (\PDOException $e) {
            error_log("Failed to create tracking table: " . $e->getMessage());
        }
    }

    private function isLocked() {
        if (file_exists($this->lockFile)) {
            $lockTime = filectime($this->lockFile);
            // If lock file is older than 30 minutes, we can remove it
            if (time() - $lockTime > 1800) {
                unlink($this->lockFile);
                return false;
            }
            return true;
        }
        return false;
    }

    private function lock() {
        touch($this->lockFile);
    }

    private function unlock() {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }

    private function generatePassword($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $password;
    }

    private function getLastProcessedId() {
        try {
            $sql = "SELECT last_processed_id FROM email_batch_tracking 
                    ORDER BY id DESC LIMIT 1";
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['last_processed_id'] : 0;
        } catch (\PDOException $e) {
            error_log("Error getting last processed ID: " . $e->getMessage());
            return 0;
        }
    }

    private function getNextBatchNumber() {
        try {
            $sql = "SELECT MAX(batch_number) as last_batch FROM email_batch_tracking";
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['last_batch'] ?? 0) + 1;
        } catch (\PDOException $e) {
            error_log("Error getting next batch number: " . $e->getMessage());
            return 1;
        }
    }

    private function getNextBatch() {
        try {
            $lastProcessedId = $this->getLastProcessedId();

            $sql = "SELECT e.staff_id, e.EMAIL, e.NAME 
                    FROM employee e 
                    WHERE e.EMAIL IS NOT NULL 
                    AND e.EMAIL != '' 
                    AND e.STATUSCD = 'A'
                    AND e.staff_id > :last_id
                    ORDER BY e.staff_id
                    LIMIT :batch_size";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':last_id', $lastProcessedId, PDO::PARAM_INT);
            $stmt->bindValue(':batch_size', $this->batchSize, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error getting next batch: " . $e->getMessage());
            return [];
        }
    }

    private function updateUserPassword($staffId, $password) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO tbl_users (staff_id, password_hash, plain_password) 
                    VALUES (:staff_id, :password_hash, :plain_password)
                    ON DUPLICATE KEY UPDATE 
                    password_hash = :password_hash,
                    plain_password = :plain_password";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':staff_id' => $staffId,
                ':password_hash' => $passwordHash,
                ':plain_password' => $password
            ]);
        } catch (\PDOException $e) {
            error_log("Error updating password for staff ID {$staffId}: " . $e->getMessage());
            return false;
        }
    }

    private function sendCredentialEmail($email, $password, $name) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = EmailConfig::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = EmailConfig::SMTP_USERNAME;
            $mail->Password = EmailConfig::SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = EmailConfig::SMTP_PORT;

            $mail->setFrom(EmailConfig::SMTP_FROM, 'OOUTH Password Reset');
            $mail->addAddress($email);
            $mail->addCC('Bankole.adesoji@gmail.com');

            $mail->isHTML(true);
            $mail->Subject = 'OOUTH Mobile App Login Credentials';

            $emailBody = $this->getEmailTemplate($name, $email, $password);
            $mail->Body = $emailBody;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error sending email to {$email}: " . $e->getMessage());
            return false;
        }
    }

    private function getEmailTemplate($name, $email, $password) {
        return "
        <p>Dear {$name},</p>

        <p>We are excited to introduce the new OOUTH Mobile App that allows you to:</p>
        <ul>
            <li>View your payslips</li>
            <li>Access your employee profile</li>
            <li>Update your information</li>
            <li>And much more!</li>
        </ul>

        <p>Your login credentials for the mobile app are:</p>
        <p><strong>Username:</strong> {$email}</p>
        <p><strong>Password:</strong> {$password}</p>

        <p>To get started:</p>
        <ol>
            <li>Download the OOUTH Mobile App from: <a href='https://oouthsalary.com.ng/download.html'>Download App</a></li>
            <li>Install the app on your Android device</li>
            <li>Log in using your email and password above</li>
        </ol>

        Don't have an android phone? Don't worry about it, use the <a href='https://oouth-e2a0e.web.app/'>Web version</a> 
        <br>Once it opens, you may want to click the install button

        <p>For security reasons, please change your password after your first login.</p>

        <p>If you have any questions or need assistance, please contact the HR department.</p>

        <p>Best regards,<br>OOUTH HR Team</p>";
    }

    private function recordBatchProgress($lastProcessedId, $processedCount) {
        try {
            $batchNumber = $this->getNextBatchNumber();
            $sql = "INSERT INTO email_batch_tracking 
                    (last_processed_id, batch_number, processed_count, start_time, end_time, status) 
                    VALUES 
                    (:last_id, :batch_num, :count, :start_time, :end_time, :status)";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':last_id' => $lastProcessedId,
                ':batch_num' => $batchNumber,
                ':count' => $processedCount,
                ':start_time' => date('Y-m-d H:i:s', strtotime('now')),
                ':end_time' => date('Y-m-d H:i:s', strtotime('now')),
                ':status' => 'completed'
            ]);
        } catch (\PDOException $e) {
            error_log("Error recording batch progress: " . $e->getMessage());
            return false;
        }
    }

    public function processBatch() {
        if ($this->isLocked()) {
            error_log("Process is locked. Another instance may be running.");
            return;
        }

        $this->lock();
        $startTime = microtime(true);
        $processed = 0;
        $successful = 0;
        $lastProcessedId = 0;

        try {
            $employees = $this->getNextBatch();

            if (empty($employees)) {
                error_log("No more employees to process. Starting over from the beginning.");
                // Reset the tracking to start over
                $this->recordBatchProgress(0, 0);
                return;
            }

            foreach ($employees as $employee) {
                $processed++;
                $lastProcessedId = $employee['staff_id'];
                $password = $this->generatePassword();

                if ($this->updateUserPassword($employee['staff_id'], $password)) {
                    if ($this->sendCredentialEmail($employee['EMAIL'], $password, $employee['NAME'])) {
                        $successful++;
                        error_log("Successfully processed staff ID: {$employee['staff_id']} in batch");
                    } else {
                        error_log("Failed to send email for staff ID: {$employee['staff_id']}");
                    }
                } else {
                    error_log("Failed to update password for staff ID: {$employee['staff_id']}");
                }

//                usleep(250000); // 0.25 second delay
                usleep(1000000); // 1 second delay
            }

            // Record the batch progress
            $this->recordBatchProgress($lastProcessedId, $processed);

        } catch (\Exception $e) {
            error_log("Batch processing error: " . $e->getMessage());
        } finally {
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            error_log("Batch processing completed. Processed: $processed, Successful: $successful, Last ID: $lastProcessedId, Duration: $duration seconds");
            $this->unlock();
        }
    }
}

// Execute the batch process
$sender = new BatchCredentialSender();
$sender->processBatch();