<?php
/**
 * PeriodClosingProcessor - Handle Period Closing Operations
 * 
 * Manages period closing, surplus appropriation, and period locking
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class PeriodClosingProcessor {
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
     * Check if period can be closed
     * 
     * @param int $periodid Period ID
     * @return array ['can_close' => bool, 'issues' => array, 'warnings' => array]
     */
    public function validatePeriodForClosing($periodid) {
        $issues = [];
        $warnings = [];
        
        // Check if already closed
        $sql = "SELECT COUNT(*) as count FROM coop_period_balances 
                WHERE periodid = ? AND is_closed = TRUE LIMIT 1";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($row['count'] > 0) {
            $issues[] = "Period is already closed";
        }
        
        // Check for draft journal entries
        $sql = "SELECT COUNT(*) as count FROM coop_journal_entries 
                WHERE periodid = ? AND status = 'draft'";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($row['count'] > 0) {
            $warnings[] = "{$row['count']} draft journal entries will not be included";
        }
        
        // Check trial balance
        require_once('AccountBalanceCalculator.php');
        $calculator = new AccountBalanceCalculator($this->db, $this->database_name);
        $trialBalance = $calculator->getTrialBalance($periodid);
        
        if (!$trialBalance['is_balanced']) {
            $issues[] = "Trial balance is out of balance by ₦" . number_format(abs($trialBalance['totals']['difference']), 2);
        }
        
        // Check accounting equation
        $equation = $calculator->verifyAccountingEquation($periodid);
        if (!$equation['valid']) {
            $issues[] = "Accounting equation doesn't balance (Assets ≠ Liabilities + Equity)";
        }
        
        return [
            'can_close' => (count($issues) == 0),
            'issues' => $issues,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Close period
     * 
     * @param int $periodid Period ID
     * @param int $user_id User performing the closing
     * @param array $appropriation_data Appropriation amounts
     * @return array ['success' => bool, 'error' => string, 'entries_created' => array]
     */
    public function closePeriod($periodid, $user_id, $appropriation_data = []) {
        try {
            // Validate
            $validation = $this->validatePeriodForClosing($periodid);
            if (!$validation['can_close']) {
                return [
                    'success' => false,
                    'error' => 'Cannot close period',
                    'issues' => $validation['issues']
                ];
            }
            
            mysqli_begin_transaction($this->db);
            
            try {
                $entries_created = [];
                
                // Step 1: Close revenue and expense accounts to retained earnings
                $closing_entry = $this->closeRevenueExpenseAccounts($periodid, $user_id);
                if ($closing_entry) {
                    $entries_created[] = $closing_entry;
                }
                
                // Step 2: Process appropriation if provided
                if (!empty($appropriation_data)) {
                    $appropriation_entry = $this->processAppropriation($periodid, $user_id, $appropriation_data);
                    if ($appropriation_entry) {
                        $entries_created[] = $appropriation_entry;
                    }
                }
                
                // Step 3: Mark period as closed
                $this->markPeriodClosed($periodid, $user_id);
                
                // Step 4: Create opening balances for next period (if exists)
                $this->createOpeningBalancesForNextPeriod($periodid);
                
                mysqli_commit($this->db);
                
                return [
                    'success' => true,
                    'entries_created' => $entries_created,
                    'message' => 'Period closed successfully'
                ];
                
            } catch (Exception $e) {
                mysqli_rollback($this->db);
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("PeriodClosingProcessor::closePeriod - Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Close revenue and expense accounts to retained earnings
     */
    private function closeRevenueExpenseAccounts($periodid, $user_id) {
        require_once('AccountingEngine.php');
        require_once('AccountBalanceCalculator.php');
        
        $engine = new AccountingEngine($this->db, $this->database_name);
        $calculator = new AccountBalanceCalculator($this->db, $this->database_name);
        
        // Get total revenue
        $revenue_total = $calculator->getTotalByType($periodid, 'revenue');
        
        // Get total expenses
        $expense_total = $calculator->getTotalByType($periodid, 'expense');
        
        // Net profit
        $net_profit = $revenue_total - $expense_total;
        
        if ($net_profit == 0) {
            return null; // Nothing to close
        }
        
        $lines = [];
        
        if ($net_profit > 0) {
            // Profit: DR Revenue, CR Retained Earnings
            $lines[] = [
                'account_id' => 47, // Revenue (4000)
                'debit_amount' => $revenue_total,
                'credit_amount' => 0,
                'description' => 'Close revenue accounts'
            ];
            $lines[] = [
                'account_id' => 63, // Expense (6000)
                'debit_amount' => 0,
                'credit_amount' => $expense_total,
                'description' => 'Close expense accounts'
            ];
            $lines[] = [
                'account_id' => 46, // Retained Earnings (3401)
                'debit_amount' => 0,
                'credit_amount' => $net_profit,
                'description' => 'Transfer net profit to retained earnings'
            ];
        } else {
            // Loss: DR Retained Earnings, CR Expense accounts
            $lines[] = [
                'account_id' => 47, // Revenue
                'debit_amount' => $revenue_total,
                'credit_amount' => 0,
                'description' => 'Close revenue accounts'
            ];
            $lines[] = [
                'account_id' => 63, // Expense
                'debit_amount' => 0,
                'credit_amount' => $expense_total,
                'description' => 'Close expense accounts'
            ];
            $lines[] = [
                'account_id' => 46, // Retained Earnings
                'debit_amount' => abs($net_profit),
                'credit_amount' => 0,
                'description' => 'Transfer net loss to retained earnings'
            ];
        }
        
        $result = $engine->createJournalEntry(
            $periodid,
            date('Y-m-d'),
            'closing',
            "Period closing - Transfer revenue and expenses to retained earnings",
            $lines,
            $user_id,
            "CLOSING-{$periodid}"
        );
        
        if ($result['success']) {
            $engine->postEntry($result['entry_id']);
            return $result['entry_number'];
        }
        
        return null;
    }
    
    /**
     * Process appropriation
     */
    private function processAppropriation($periodid, $user_id, $appropriation_data) {
        require_once('AccountingEngine.php');
        $engine = new AccountingEngine($this->db, $this->database_name);
        
        // Save appropriation record
        $sql = "INSERT INTO coop_appropriation 
                (periodid, surplus_amount, dividend_amount, interest_to_members, reserve_fund, 
                 bonus_amount, education_fund, honorarium, general_reserve, welfare_fund, 
                 retained_earnings, is_posted, approved_by, approval_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE, ?, NOW())";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "iddddddddddi",
            $periodid,
            $appropriation_data['surplus_amount'],
            $appropriation_data['dividend'] ?? 0,
            $appropriation_data['interest_to_members'] ?? 0,
            $appropriation_data['reserve_fund'] ?? 0,
            $appropriation_data['bonus'] ?? 0,
            $appropriation_data['education_fund'] ?? 0,
            $appropriation_data['honorarium'] ?? 0,
            $appropriation_data['general_reserve'] ?? 0,
            $appropriation_data['welfare_fund'] ?? 0,
            $appropriation_data['retained_earnings'] ?? 0,
            $user_id
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Create journal entry for appropriation
        $lines = [];
        
        foreach ($appropriation_data as $key => $amount) {
            if ($key == 'surplus_amount' || $amount <= 0) continue;
            
            $account_map = [
                'dividend' => ['dr' => 83, 'cr' => 24], // 7001 → 2103
                'interest_to_members' => ['dr' => 84, 'cr' => 25], // 7002 → 2104
                'reserve_fund' => ['dr' => 85, 'cr' => 40], // 7003 → 3301
                'bonus' => ['dr' => 86, 'cr' => 26], // 7004 → 2105
                'education_fund' => ['dr' => 87, 'cr' => 42], // 7005 → 3303
                'honorarium' => ['dr' => 88, 'cr' => 27], // 7006 → 2106
                'general_reserve' => ['dr' => 89, 'cr' => 41], // 7007 → 3302
                'welfare_fund' => ['dr' => 90, 'cr' => 43] // 7008 → 3304
            ];
            
            if (isset($account_map[$key])) {
                $lines[] = [
                    'account_id' => $account_map[$key]['dr'],
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => str_replace('_', ' ', ucwords($key, '_'))
                ];
                $lines[] = [
                    'account_id' => $account_map[$key]['cr'],
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => str_replace('_', ' ', ucwords($key, '_'))
                ];
            }
        }
        
        if (!empty($lines)) {
            $result = $engine->createJournalEntry(
                $periodid,
                date('Y-m-d'),
                'appropriation',
                "Surplus appropriation for period",
                $lines,
                $user_id,
                "APPROP-{$periodid}"
            );
            
            if ($result['success']) {
                $engine->postEntry($result['entry_id']);
                return $result['entry_number'];
            }
        }
        
        return null;
    }
    
    /**
     * Mark period as closed
     */
    private function markPeriodClosed($periodid, $user_id) {
        $sql = "UPDATE coop_period_balances 
                SET is_closed = TRUE, closed_at = NOW(), closed_by = ?
                WHERE periodid = ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $periodid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Create opening balances for next period
     */
    private function createOpeningBalancesForNextPeriod($periodid) {
        // Get next period
        $sql = "SELECT id FROM tbpayrollperiods 
                WHERE id > ? 
                ORDER BY id ASC 
                LIMIT 1";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $next_period = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$next_period) {
            return; // No next period
        }
        
        $next_periodid = $next_period['id'];
        
        // Copy closing balances to opening balances of next period
        $sql = "INSERT INTO coop_period_balances 
                (periodid, account_id, opening_debit, opening_credit, closing_debit, closing_credit)
                SELECT 
                    ? as periodid,
                    account_id,
                    closing_debit as opening_debit,
                    closing_credit as opening_credit,
                    closing_debit as closing_debit,
                    closing_credit as closing_credit
                FROM coop_period_balances
                WHERE periodid = ?
                ON DUPLICATE KEY UPDATE
                opening_debit = VALUES(opening_debit),
                opening_credit = VALUES(opening_credit)";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $next_periodid, $periodid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Also initialize member accounts for next period
        require_once('MemberAccountManager.php');
        $memberManager = new MemberAccountManager($this->db, $this->database_name);
        $memberManager->initializeNewPeriodBalances($next_periodid, $periodid);
    }
    
    /**
     * Reopen a closed period
     * 
     * @param int $periodid Period ID
     * @param int $user_id User performing the action
     * @param string $reason Reason for reopening
     * @return array ['success' => bool, 'error' => string]
     */
    public function reopenPeriod($periodid, $user_id, $reason) {
        try {
            mysqli_begin_transaction($this->db);
            
            try {
                // Mark as not closed
                $sql = "UPDATE coop_period_balances 
                        SET is_closed = FALSE, closed_at = NULL, closed_by = NULL
                        WHERE periodid = ?";
                
                $stmt = mysqli_prepare($this->db, $sql);
                mysqli_stmt_bind_param($stmt, "i", $periodid);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                // Log action
                $log_sql = "INSERT INTO coop_audit_trail 
                           (user_id, action_type, table_name, record_id, notes)
                           VALUES (?, 'reopen_period', 'coop_period_balances', ?, ?)";
                
                $stmt = mysqli_prepare($this->db, $log_sql);
                mysqli_stmt_bind_param($stmt, "iis", $user_id, $periodid, $reason);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                mysqli_commit($this->db);
                
                return ['success' => true];
                
            } catch (Exception $e) {
                mysqli_rollback($this->db);
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("PeriodClosingProcessor::reopenPeriod - Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get period closing status
     * 
     * @param int $periodid Period ID
     * @return array Period status information
     */
    public function getPeriodStatus($periodid) {
        $sql = "SELECT 
                    is_closed,
                    closed_at,
                    closed_by
                FROM coop_period_balances
                WHERE periodid = ?
                LIMIT 1";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$row) {
            return [
                'is_closed' => false,
                'status' => 'open'
            ];
        }
        
        return [
            'is_closed' => (bool)$row['is_closed'],
            'closed_at' => $row['closed_at'],
            'closed_by' => $row['closed_by'],
            'status' => $row['is_closed'] ? 'closed' : 'open'
        ];
    }
}
