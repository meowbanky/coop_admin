<?php
/**
 * MemberAccountManager - Manage Individual Member Accounts
 * 
 * Handles member shares, savings, loans, and reconciliation with control accounts
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class MemberAccountManager {
    private $db;
    private $database_name;
    
    // Control account IDs (from chart of accounts)
    private $CONTROL_ACCOUNTS = [
        'shares' => 3101,        // Ordinary Shares control account
        'savings' => 3201,       // Ordinary Savings control account
        'special_savings' => 3202, // Special Savings control account
        'loan' => 1110           // Member Loans control account
    ];
    
    /**
     * Constructor
     * 
     * @param mysqli $database_connection Database connection
     * @param string $database_name Database name (optional)
     */
    public function __construct($database_connection, $database_name = null) {
        $this->db = $database_connection;
        $this->database_name = $database_name;
        
        if ($database_name) {
            mysqli_select_db($this->db, $database_name);
        }
    }
    
    /**
     * Record member transaction
     * Creates/updates member account balance for the period
     * 
     * @param int $memberid Member ID
     * @param string $account_type Account type (shares, savings, special_savings, loan, etc.)
     * @param float $amount Transaction amount (positive for credit, negative for debit)
     * @param int $periodid Period ID
     * @param string $description Transaction description
     * @return array ['success' => bool, 'error' => string]
     */
    public function recordMemberTransaction($memberid, $account_type, $amount, $periodid, $description = '') {
        try {
            // Check if member account record exists for this period
            $sql = "SELECT id, opening_balance, debit_amount, credit_amount, closing_balance 
                    FROM coop_member_accounts 
                    WHERE memberid = ? AND account_type = ? AND periodid = ?";
            
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, "isi", $memberid, $account_type, $periodid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $existing = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($existing) {
                // Update existing record
                if ($amount >= 0) {
                    // Credit (increase)
                    $new_credit = $existing['credit_amount'] + $amount;
                    $new_closing = $existing['opening_balance'] + $new_credit - $existing['debit_amount'];
                    
                    $sql = "UPDATE coop_member_accounts 
                            SET credit_amount = ?, closing_balance = ?
                            WHERE id = ?";
                    
                    $stmt = mysqli_prepare($this->db, $sql);
                    mysqli_stmt_bind_param($stmt, "ddi", $new_credit, $new_closing, $existing['id']);
                } else {
                    // Debit (decrease)
                    $debit_amt = abs($amount);
                    $new_debit = $existing['debit_amount'] + $debit_amt;
                    $new_closing = $existing['opening_balance'] + $existing['credit_amount'] - $new_debit;
                    
                    $sql = "UPDATE coop_member_accounts 
                            SET debit_amount = ?, closing_balance = ?
                            WHERE id = ?";
                    
                    $stmt = mysqli_prepare($this->db, $sql);
                    mysqli_stmt_bind_param($stmt, "ddi", $new_debit, $new_closing, $existing['id']);
                }
            } else {
                // Get opening balance from previous period
                $opening_balance = $this->getPreviousPeriodBalance($memberid, $account_type, $periodid);
                
                // Create new record
                if ($amount >= 0) {
                    $credit_amount = $amount;
                    $debit_amount = 0;
                } else {
                    $credit_amount = 0;
                    $debit_amount = abs($amount);
                }
                
                $closing_balance = $opening_balance + $credit_amount - $debit_amount;
                
                $sql = "INSERT INTO coop_member_accounts 
                        (memberid, account_type, periodid, opening_balance, debit_amount, credit_amount, closing_balance)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($this->db, $sql);
                mysqli_stmt_bind_param($stmt, "isidddd",
                    $memberid, $account_type, $periodid,
                    $opening_balance, $debit_amount, $credit_amount, $closing_balance
                );
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to record member transaction: " . mysqli_stmt_error($stmt));
            }
            
            mysqli_stmt_close($stmt);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("MemberAccountManager::recordMemberTransaction - Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get member balance for a specific account type and period
     * 
     * @param int $memberid Member ID
     * @param string $account_type Account type
     * @param int $periodid Period ID
     * @return float Balance
     */
    public function getMemberBalance($memberid, $account_type, $periodid) {
        $sql = "SELECT closing_balance FROM coop_member_accounts 
                WHERE memberid = ? AND account_type = ? AND periodid = ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "isi", $memberid, $account_type, $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($row) {
            return floatval($row['closing_balance']);
        }
        
        // If no record for this period, get from previous period
        return $this->getPreviousPeriodBalance($memberid, $account_type, $periodid);
    }
    
    /**
     * Get previous period closing balance
     * 
     * @param int $memberid Member ID
     * @param string $account_type Account type
     * @param int $current_periodid Current period ID
     * @return float Previous closing balance
     */
    private function getPreviousPeriodBalance($memberid, $account_type, $current_periodid) {
        $sql = "SELECT closing_balance 
                FROM coop_member_accounts 
                WHERE memberid = ? AND account_type = ? AND periodid < ?
                ORDER BY periodid DESC
                LIMIT 1";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "isi", $memberid, $account_type, $current_periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $row ? floatval($row['closing_balance']) : 0;
    }
    
    /**
     * Generate member statement
     * Shows all transactions for a member across periods
     * 
     * @param int $memberid Member ID
     * @param int $from_periodid Starting period
     * @param int $to_periodid Ending period
     * @return array Member statement data
     */
    public function generateMemberStatement($memberid, $from_periodid, $to_periodid) {
        try {
            // Get member details
            $member = $this->getMemberDetails($memberid);
            if (!$member) {
                return ['success' => false, 'error' => 'Member not found'];
            }
            
            // Get all account types for this member
            $account_types = ['shares', 'savings', 'special_savings', 'loan'];
            $statement = [];
            
            foreach ($account_types as $type) {
                $sql = "SELECT 
                            ma.periodid,
                            pp.PayrollPeriod,
                            ma.opening_balance,
                            ma.debit_amount,
                            ma.credit_amount,
                            ma.closing_balance
                        FROM coop_member_accounts ma
                        JOIN tbpayrollperiods pp ON ma.periodid = pp.id
                        WHERE ma.memberid = ? AND ma.account_type = ?
                        AND ma.periodid >= ? AND ma.periodid <= ?
                        ORDER BY ma.periodid";
                
                $stmt = mysqli_prepare($this->db, $sql);
                mysqli_stmt_bind_param($stmt, "isii", $memberid, $type, $from_periodid, $to_periodid);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                $transactions = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $transactions[] = $row;
                }
                
                mysqli_stmt_close($stmt);
                
                if (!empty($transactions)) {
                    $statement[$type] = $transactions;
                }
            }
            
            return [
                'success' => true,
                'member' => $member,
                'statement' => $statement
            ];
            
        } catch (Exception $e) {
            error_log("MemberAccountManager::generateMemberStatement - Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Reconcile member accounts with control accounts
     * Ensures sum of individual member accounts = control account balance
     * 
     * @param int $periodid Period ID
     * @return array ['reconciled' => array, 'mismatches' => array]
     */
    public function reconcileMemberAccounts($periodid) {
        try {
            require_once('AccountBalanceCalculator.php');
            $calculator = new AccountBalanceCalculator($this->db, $this->database_name);
            
            $account_types = ['shares', 'savings', 'special_savings', 'loan'];
            $reconciliation = [];
            $mismatches = [];
            
            foreach ($account_types as $type) {
                // Get sum of individual member accounts
                $sql = "SELECT SUM(closing_balance) as total 
                        FROM coop_member_accounts 
                        WHERE account_type = ? AND periodid = ?";
                
                $stmt = mysqli_prepare($this->db, $sql);
                mysqli_stmt_bind_param($stmt, "si", $type, $periodid);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                $member_total = floatval($row['total'] ?? 0);
                mysqli_stmt_close($stmt);
                
                // Get control account balance
                $control_account_id = $this->CONTROL_ACCOUNTS[$type] ?? null;
                if ($control_account_id) {
                    $control_balance = $calculator->getAccountBalance($control_account_id, $periodid);
                    $control_total = $control_balance['balance'];
                    
                    $difference = abs($member_total - $control_total);
                    $matches = $difference < 0.01;
                    
                    $reconciliation[$type] = [
                        'account_type' => $type,
                        'member_total' => $member_total,
                        'control_total' => $control_total,
                        'difference' => $member_total - $control_total,
                        'matches' => $matches
                    ];
                    
                    if (!$matches) {
                        $mismatches[] = $reconciliation[$type];
                    }
                }
            }
            
            return [
                'reconciliation' => $reconciliation,
                'mismatches' => $mismatches,
                'all_match' => empty($mismatches)
            ];
            
        } catch (Exception $e) {
            error_log("MemberAccountManager::reconcileMemberAccounts - Error: " . $e->getMessage());
            return [
                'reconciliation' => [],
                'mismatches' => [],
                'all_match' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get member details
     * 
     * @param int $memberid Member ID
     * @return array|null Member data
     */
    private function getMemberDetails($memberid) {
        $sql = "SELECT CoopID as memberid, 
                CONCAT(LastName, ', ', FirstName, ' ', IFNULL(MiddleName, '')) as full_name,
                EmailAddress,
                MobileNumber as Phone
                FROM tblemployees
                WHERE CoopID = ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "s", $memberid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $member = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Add empty phone if not set
        if ($member && empty($member['Phone'])) {
            $member['Phone'] = '';
        }
        return $member;
    }
    
    /**
     * Get all member balances for a period
     * 
     * @param int $periodid Period ID
     * @param string $account_type Optional filter by type
     * @return array Member balances
     */
    public function getAllMemberBalances($periodid, $account_type = null) {
        $sql = "SELECT 
                    ma.memberid,
                    CONCAT(e.LastName, ', ', e.FirstName, ' ', IFNULL(e.MiddleName, '')) as member_name,
                    ma.account_type,
                    ma.opening_balance,
                    ma.debit_amount,
                    ma.credit_amount,
                    ma.closing_balance
                FROM coop_member_accounts ma
                JOIN tblemployees e ON ma.memberid = e.CoopID
                WHERE ma.periodid = ?";
        
        if ($account_type) {
            $sql .= " AND ma.account_type = ?";
        }
        
        $sql .= " ORDER BY ma.memberid, ma.account_type";
        
        $stmt = mysqli_prepare($this->db, $sql);
        
        if ($account_type) {
            mysqli_stmt_bind_param($stmt, "is", $periodid, $account_type);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $periodid);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $members = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $members[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $members;
    }
    
    /**
     * Get member account summary (all types)
     * 
     * @param int $memberid Member ID
     * @param int $periodid Period ID
     * @return array Account summary
     */
    public function getMemberAccountSummary($memberid, $periodid) {
        $account_types = ['shares', 'savings', 'special_savings', 'loan'];
        $summary = [];
        
        foreach ($account_types as $type) {
            $balance = $this->getMemberBalance($memberid, $type, $periodid);
            $summary[$type] = $balance;
        }
        
        $summary['total_equity'] = $summary['shares'] + $summary['savings'] + $summary['special_savings'];
        $summary['net_position'] = $summary['total_equity'] - $summary['loan'];
        
        return $summary;
    }
    
    /**
     * Initialize member accounts for a new period
     * Copies closing balances from previous period as opening balances
     * 
     * @param int $new_periodid New period ID
     * @param int $previous_periodid Previous period ID
     * @return array ['success' => bool, 'members_initialized' => int, 'error' => string]
     */
    public function initializeNewPeriodBalances($new_periodid, $previous_periodid) {
        try {
            mysqli_begin_transaction($this->db);
            
            try {
                $sql = "INSERT INTO coop_member_accounts 
                        (memberid, account_type, periodid, opening_balance, debit_amount, credit_amount, closing_balance)
                        SELECT 
                            memberid, 
                            account_type, 
                            ? as periodid,
                            closing_balance as opening_balance,
                            0 as debit_amount,
                            0 as credit_amount,
                            closing_balance as closing_balance
                        FROM coop_member_accounts
                        WHERE periodid = ?";
                
                $stmt = mysqli_prepare($this->db, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $new_periodid, $previous_periodid);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to initialize period balances: " . mysqli_stmt_error($stmt));
                }
                
                $rows_affected = mysqli_affected_rows($this->db);
                mysqli_stmt_close($stmt);
                
                mysqli_commit($this->db);
                
                return [
                    'success' => true,
                    'members_initialized' => $rows_affected
                ];
                
            } catch (Exception $e) {
                mysqli_rollback($this->db);
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("MemberAccountManager::initializeNewPeriodBalances - Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
