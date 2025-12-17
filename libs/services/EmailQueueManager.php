<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class EmailQueueManager {
    private $db;
    private $maxEmailsPerHour = 50; // Configurable rate limit
    private $batchSize = 10; // Process 10 emails per batch
    private $retryDelay = 300; // 5 minutes between retries
    private $emailConfig;
    
    public function __construct($database_connection, $database_name = null) {
        $this->db = $database_connection;
        if ($database_name) {
            mysqli_select_db($this->db, $database_name);
        }
        
        // Load email configuration from .env file
        $this->loadEnvConfig();
    }
    
    /**
     * Load configuration using EnvConfig class
     */
    private function loadEnvConfig() {
        // Load EnvConfig class if not already loaded
        $envConfigPath = __DIR__ . '/../../config/EnvConfig.php';
        if (file_exists($envConfigPath)) {
            require_once $envConfigPath;
            $this->emailConfig = EnvConfig::getMailConfig();
        } else {
            error_log("EnvConfig.php not found, using default configuration");
            $this->emailConfig = $this->getDefaultConfig();
        }
    }
    
    /**
     * Get default configuration if config file doesn't exist
     */
    private function getDefaultConfig() {
        return [
            'provider' => 'smtp',
            'smtp' => [
                'host' => 'localhost',
                'port' => 25,
                'encryption' => '',
                'auth' => false,
                'username' => '',
                'password' => '',
            ],
            'from' => [
                'email' => 'noreply@localhost',
                'name' => 'Cooperative Society',
            ],
            'options' => [
                'debug' => false,
                'charset' => 'UTF-8',
                'timeout' => 30,
            ],
        ];
    }
    
    /**
     * Add email to queue
     */
    public function addToQueue($memberId, $periodId, $emailType, $recipientEmail, $recipientName, $subject, $messageBody, $priority = 2, $scheduledAt = null, $metadata = null) {
        $metadata = $metadata ? json_encode($metadata) : null;
        
        // Use MySQL's NOW() for immediate scheduling (timezone-safe)
        // Or use provided scheduled time for future scheduling
        if ($scheduledAt === null) {
            $sql = "INSERT INTO tbl_email_queue 
                    (member_id, period_id, email_type, recipient_email, recipient_name, subject, message_body, priority, scheduled_at, metadata) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, "iisssssis", $memberId, $periodId, $emailType, $recipientEmail, $recipientName, $subject, $messageBody, $priority, $metadata);
        } else {
            $sql = "INSERT INTO tbl_email_queue 
                    (member_id, period_id, email_type, recipient_email, recipient_name, subject, message_body, priority, scheduled_at, metadata) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, "iisssssiss", $memberId, $periodId, $emailType, $recipientEmail, $recipientName, $subject, $messageBody, $priority, $scheduledAt, $metadata);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $queueId = mysqli_insert_id($this->db);
            $this->logQueueAction($queueId, 'queued', 'Email added to queue');
            mysqli_stmt_close($stmt);
            return $queueId;
        } else {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    
    /**
     * Process pending emails in batches
     */
    public function processQueue() {
        $processed = 0;
        $failed = 0;
        
        // Check rate limits
        if (!$this->canSendEmails()) {
            error_log("Email rate limit reached. Skipping batch processing.");
            return ['processed' => 0, 'failed' => 0, 'skipped' => 'rate_limit'];
        }
        
        // Get pending emails ordered by priority and scheduled time
        $emails = $this->getPendingEmails($this->batchSize);
        
        if (empty($emails)) {
            return ['processed' => 0, 'failed' => 0, 'skipped' => 'no_pending'];
        }
        
        foreach ($emails as $email) {
            $result = $this->processEmail($email);
            
            if ($result['success']) {
                $processed++;
                $this->updateEmailStatus($email['id'], 'sent', null, date('Y-m-d H:i:s'));
                $this->logQueueAction($email['id'], 'sent', 'Email sent successfully');
                $this->updateRateLimit();
            } else {
                $failed++;
                $this->handleFailedEmail($email, $result['error']);
            }
        }
        
        return ['processed' => $processed, 'failed' => $failed];
    }
    
    /**
     * Get pending emails for processing
     */
    private function getPendingEmails($limit) {
        // Debug: Log current time and query
        error_log("EmailQueueManager: Getting pending emails. Current server time: " . date('Y-m-d H:i:s'));
        
        $sql = "SELECT * FROM tbl_email_queue 
                WHERE status = 'pending' 
                AND scheduled_at <= NOW() 
                ORDER BY priority ASC, scheduled_at ASC, created_at ASC 
                LIMIT ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $emails = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $emails[] = $row;
        }
        
        error_log("EmailQueueManager: Found " . count($emails) . " pending emails");
        
        // Debug: Check if there are pending emails with future scheduled_at
        if (count($emails) == 0) {
            $checkSql = "SELECT COUNT(*) as total, MIN(scheduled_at) as earliest 
                        FROM tbl_email_queue 
                        WHERE status = 'pending'";
            $checkResult = mysqli_query($this->db, $checkSql);
            if ($checkRow = mysqli_fetch_assoc($checkResult)) {
                error_log("EmailQueueManager: Total pending emails: " . $checkRow['total'] . 
                         ", Earliest scheduled_at: " . $checkRow['earliest']);
            }
        }
        
        mysqli_stmt_close($stmt);
        return $emails;
    }
    
    /**
     * Process individual email
     */
    private function processEmail($email) {
        // Mark as processing
        $this->updateEmailStatus($email['id'], 'processing', date('Y-m-d H:i:s'));
        $this->logQueueAction($email['id'], 'processing', 'Starting email processing');
        
        try {
            // Simulate email sending (replace with actual email sending logic)
            $success = $this->sendEmail($email);
            
            if ($success) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to send email'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send actual email using PHPMailer
     */
    private function sendEmail($email) {
        try {
            // Load PHPMailer
            require_once __DIR__ . '/../../mail/mail/vendor/phpmailer/phpmailer/src/Exception.php';
            require_once __DIR__ . '/../../mail/mail/vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../../mail/mail/vendor/phpmailer/phpmailer/src/SMTP.php';
            
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $this->emailConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->emailConfig['username'];
            $mail->Password   = $this->emailConfig['password'];
            $mail->SMTPSecure = $this->emailConfig['encryption'];
            $mail->Port       = $this->emailConfig['port'];
            $mail->CharSet    = 'UTF-8';
            
            // Sender information
            $mail->setFrom($this->emailConfig['from_address'], $this->emailConfig['from_name']);
            
            // Recipient
            $mail->addAddress($email['recipient_email'], $email['recipient_name']);
            
            // Reply-To (use from address)
            $mail->addReplyTo($this->emailConfig['from_address'], $this->emailConfig['from_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $email['subject'];
            $mail->Body    = $email['message_body'];
            
            // Alternative plain text for non-HTML email clients
            $mail->AltBody = strip_tags($email['message_body']);
            
            // Send email
            $success = $mail->send();
            
            if ($success) {
                error_log("Email sent successfully to: {$email['recipient_email']} - Subject: {$email['subject']}");
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            error_log("Email sending exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle failed email with retry logic
     */
    private function handleFailedEmail($email, $error) {
        $retryCount = $email['retry_count'] + 1;
        $maxRetries = $email['max_retries'];
        
        if ($retryCount < $maxRetries) {
            // Schedule for retry
            $nextRetry = date('Y-m-d H:i:s', time() + ($this->retryDelay * $retryCount));
            $this->updateEmailStatus($email['id'], 'pending', null, null, $retryCount, $error);
            $this->logQueueAction($email['id'], 'retry', "Retry #{$retryCount}: {$error}");
        } else {
            // Mark as permanently failed
            $this->updateEmailStatus($email['id'], 'failed', null, null, $retryCount, $error);
            $this->logQueueAction($email['id'], 'failed', "Permanently failed after {$maxRetries} retries: {$error}");
        }
    }
    
    /**
     * Update email status
     */
    private function updateEmailStatus($id, $status, $processedAt = null, $sentAt = null, $retryCount = null, $errorMessage = null) {
        $sql = "UPDATE tbl_email_queue SET status = ?";
        $params = [$status];
        $types = "s";
        
        if ($processedAt !== null) {
            $sql .= ", processed_at = ?";
            $params[] = $processedAt;
            $types .= "s";
        }
        
        if ($sentAt !== null) {
            $sql .= ", sent_at = ?";
            $params[] = $sentAt;
            $types .= "s";
        }
        
        if ($retryCount !== null) {
            $sql .= ", retry_count = ?";
            $params[] = $retryCount;
            $types .= "i";
        }
        
        if ($errorMessage !== null) {
            $sql .= ", error_message = ?";
            $params[] = $errorMessage;
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }
    
    /**
     * Log queue action
     */
    private function logQueueAction($queueId, $action, $message = null) {
        $sql = "INSERT INTO tbl_email_queue_log (queue_id, action, message) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $queueId, $action, $message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Check if we can send emails (rate limiting)
     */
    private function canSendEmails() {
        $currentHour = date('H');
        $currentDate = date('Y-m-d');
        
        $sql = "SELECT emails_sent FROM tbl_email_rate_limit 
                WHERE date = ? AND hour = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "si", $currentDate, $currentHour);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $emailsSent = 0;
        if ($row = mysqli_fetch_assoc($result)) {
            $emailsSent = $row['emails_sent'];
        }
        
        mysqli_stmt_close($stmt);
        
        return $emailsSent < $this->maxEmailsPerHour;
    }
    
    /**
     * Update rate limit counter
     */
    private function updateRateLimit() {
        $currentHour = date('H');
        $currentDate = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO tbl_email_rate_limit (date, hour, emails_sent, last_sent_at) 
                VALUES (?, ?, 1, ?) 
                ON DUPLICATE KEY UPDATE 
                emails_sent = emails_sent + 1, 
                last_sent_at = ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "siss", $currentDate, $currentHour, $now, $now);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats() {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    AVG(CASE WHEN processed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, processed_at) END) as avg_processing_time
                FROM tbl_email_queue 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY status";
        
        $result = mysqli_query($this->db, $sql);
        $stats = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $stats[$row['status']] = [
                'count' => $row['count'],
                'avg_processing_time' => $row['avg_processing_time']
            ];
        }
        
        return $stats;
    }
    
    /**
     * Clean up old processed emails
     */
    public function cleanupOldEmails($daysOld = 30) {
        $sql = "DELETE FROM tbl_email_queue 
                WHERE status IN ('sent', 'failed', 'cancelled') 
                AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $daysOld);
        mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_affected_rows($this->db);
        mysqli_stmt_close($stmt);
        
        return $affectedRows;
    }
    
    /**
     * Cancel pending emails for a specific member/period
     */
    public function cancelPendingEmails($memberId, $periodId) {
        $sql = "UPDATE tbl_email_queue 
                SET status = 'cancelled' 
                WHERE member_id = ? AND period_id = ? AND status = 'pending'";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $memberId, $periodId);
        mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_affected_rows($this->db);
        mysqli_stmt_close($stmt);
        
        return $affectedRows;
    }
}
