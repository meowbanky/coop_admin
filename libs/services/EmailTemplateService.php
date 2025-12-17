<?php
class EmailTemplateService {
    private $db;
    private $database_name;
    
    public function __construct($database_connection, $database_name = null) {
        $this->db = $database_connection;
        $this->database_name = $database_name;
        if ($database_name) {
            mysqli_select_db($this->db, $database_name);
        }
    }
    
    /**
     * Generate transaction summary email for a member
     */
    public function generateTransactionSummaryEmail($memberId, $periodId) {
        // Get member information
        $memberQuery = "SELECT CoopID as memberid, CONCAT(LastName, ', ', FirstName, ' ', IFNULL(MiddleName, '')) as name, EmailAddress 
                       FROM tblemployees WHERE CoopID = ?";
        $stmt = mysqli_prepare($this->db, $memberQuery);
        mysqli_stmt_bind_param($stmt, "s", $memberId);
        mysqli_stmt_execute($stmt);
        $member = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);
        
        if (!$member || !$member['EmailAddress']) {
            return false;
        }
        
        // Get period information
        $periodQuery = "SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?";
        $stmt = mysqli_prepare($this->db, $periodQuery);
        mysqli_stmt_bind_param($stmt, "i", $periodId);
        mysqli_stmt_execute($stmt);
        $period = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);
        
        // Get transaction summary
        $transactionQuery = "SELECT 
            SUM(entryFee) as entryFee,
            SUM(savings) as savings,
            SUM(shares) as shares,
            SUM(interestPaid) as interestPaid,
            SUM(loanAmount) as loanAmount,
            SUM(loanRepayment) as loanRepayment,
            SUM(interest) as interest,
            (SUM(entryFee) + SUM(savings) + SUM(shares) + SUM(interestPaid) + SUM(loanRepayment)) as total
            FROM tlb_mastertransaction 
            WHERE memberid = ? AND periodid = ?";
        $stmt = mysqli_prepare($this->db, $transactionQuery);
        mysqli_stmt_bind_param($stmt, "ii", $memberId, $periodId);
        mysqli_stmt_execute($stmt);
        $transaction = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);
        
        // Get account balances
        $balanceQuery = "SELECT 
            SUM(savings) as savingsBalance,
            SUM(shares) as sharesBalance,
            SUM(loanAmount) - SUM(loanRepayment) as loanBalance,
            SUM(interest) - SUM(interestPaid) as interestBalance
            FROM tlb_mastertransaction 
            WHERE memberid = ? AND periodid <= ?";
        $stmt = mysqli_prepare($this->db, $balanceQuery);
        mysqli_stmt_bind_param($stmt, "ii", $memberId, $periodId);
        mysqli_stmt_execute($stmt);
        $balances = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);
        
        // Generate email content
        $subject = "Monthly Transaction Summary - " . $period['PayrollPeriod'];
        $messageBody = $this->generateTransactionEmailTemplate($member, $period, $transaction, $balances);
        
        return [
            'recipient_email' => $member['EmailAddress'],
            'recipient_name' => $member['name'],
            'subject' => $subject,
            'message_body' => $messageBody,
            'metadata' => [
                'member_id' => $memberId,
                'period_id' => $periodId,
                'period_name' => $period['PayrollPeriod'],
                'transaction_total' => $transaction['total']
            ]
        ];
    }
    
    /**
     * Generate email template HTML
     */
    private function generateTransactionEmailTemplate($member, $period, $transaction, $balances) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Monthly Transaction Summary</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
                .transaction-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .transaction-table th, .transaction-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
                .transaction-table th { background: #e2e8f0; font-weight: bold; }
                .balance-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .balance-table th, .balance-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
                .balance-table th { background: #f1f5f9; font-weight: bold; }
                .total { font-weight: bold; background: #dbeafe; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Monthly Transaction Summary</h1>
                    <p>Period: ' . htmlspecialchars($period['PayrollPeriod']) . '</p>
                </div>
                
                <div class="content">
                    <p>Dear ' . htmlspecialchars($member['name']) . ',</p>
                    
                    <p>Please find below your transaction summary for the period ' . htmlspecialchars($period['PayrollPeriod']) . ':</p>
                    
                    <h3>Period Transactions</h3>
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Transaction Type</th>
                                <th>Amount (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Entry Fee</td>
                                <td>' . number_format($transaction['entryFee'] ?? 0, 2) . '</td>
                            </tr>
                            <tr>
                                <td>Savings</td>
                                <td>' . number_format($transaction['savings'] ?? 0, 2) . '</td>
                            </tr>
                            <tr>
                                <td>Shares</td>
                                <td>' . number_format($transaction['shares'] ?? 0, 2) . '</td>
                            </tr>
                            <tr>
                                <td>Interest Paid</td>
                                <td>' . number_format($transaction['interestPaid'] ?? 0, 2) . '</td>
                            </tr>
                            <tr>
                                <td>Loan Repayment</td>
                                <td>' . number_format($transaction['loanRepayment'] ?? 0, 2) . '</td>
                            </tr>
                            <tr class="total">
                                <td><strong>Total Contribution</strong></td>
                                <td><strong>₦' . number_format($transaction['total'] ?? 0, 2) . '</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3>Current Account Balances</h3>
                    <table class="balance-table">
                        <thead>
                            <tr>
                                <th>Account Type</th>
                                <th>Balance (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Savings Balance</td>
                                <td>' . number_format($balances['savingsBalance'] ?? 0, 2) . '</td>
                            </tr>
                            <tr>
                                <td>Shares Balance</td>
                                <td>' . number_format($balances['sharesBalance'] ?? 0, 2) . '</td>
                            </tr>
                            <tr>
                                <td>Loan Balance</td>
                                <td>' . number_format($balances['loanBalance'] ?? 0, 2) . '</td>
                            </tr>
                            <tr>
                                <td>Unpaid Interest</td>
                                <td>' . number_format($balances['interestBalance'] ?? 0, 2) . '</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p>If you have any questions about your account, please contact our office.</p>
                    
                    <p>Thank you for your continued membership.</p>
                    
                    <!-- Mobile App Download Section -->
                    <div style="margin: 30px 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; text-align: center;">
                        <h3 style="color: white; margin-bottom: 10px;">📱 Download Our Mobile App</h3>
                        <p style="color: white; margin-bottom: 15px;">Access your account on the go!</p>
                        <a href="https://cov.emmaggi.com/download.html" 
                           style="display: inline-block; background: white; color: #667eea; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">
                            Download APK
                        </a>
                    </div>
                </div>
                
                <div class="footer">
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; ' . date('Y') . ' Your Cooperative Society. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Queue a single email
     */
    public function queueEmail($memberId, $periodId, $recipientEmail, $recipientName, $subject, $messageBody, $scheduledAt = null, $metadata = null) {
        require_once('EmailQueueManager.php');
        $queueManager = new EmailQueueManager($this->db, $this->database_name);
        
        return $queueManager->addToQueue(
            $memberId,
            $periodId,
            'transaction_summary',
            $recipientEmail,
            $recipientName,
            $subject,
            $messageBody,
            2, // Normal priority
            $scheduledAt, // Can be null for immediate or specific datetime
            $metadata
        );
    }
    /**
     * Queue transaction summary emails for all members in a period
     */
    public function queueTransactionSummaryEmails($periodId) {
        require_once('EmailQueueManager.php');
        $queueManager = new EmailQueueManager($this->db, $this->database_name);
        
        // Get all members with transactions in this period
        $membersQuery = "SELECT DISTINCT memberid FROM tlb_mastertransaction WHERE periodid = ?";
        $stmt = mysqli_prepare($this->db, $membersQuery);
        mysqli_stmt_bind_param($stmt, "i", $periodId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $queued = 0;
        $failed = 0;
        
        while ($member = mysqli_fetch_assoc($result)) {
            $emailData = $this->generateTransactionSummaryEmail($member['memberid'], $periodId);
            
            if ($emailData) {
                $queueId = $queueManager->addToQueue(
                    $member['memberid'],
                    $periodId,
                    'transaction_summary',
                    $emailData['recipient_email'],
                    $emailData['recipient_name'],
                    $emailData['subject'],
                    $emailData['message_body'],
                    2, // Normal priority
                    null, // Send immediately
                    $emailData['metadata']
                );
                
                if ($queueId) {
                    $queued++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }
        
        mysqli_stmt_close($stmt);
        
        return ['queued' => $queued, 'failed' => $failed];
    }
}
