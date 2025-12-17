<?php
/**
 * AccountingEngine - Core Double-Entry Bookkeeping Engine
 * 
 * Handles journal entry creation, validation, posting, and reversals
 * Ensures debits = credits for all transactions
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class AccountingEngine {
    private $db;
    private $database_name;
    
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
     * Create a new journal entry
     * 
     * @param int $periodid Period ID
     * @param string $entry_date Entry date (YYYY-MM-DD)
     * @param string $entry_type Entry type (manual, system, member_transaction, etc.)
     * @param string $description Entry description
     * @param array $lines Array of journal entry lines
     * @param int $created_by User ID who created the entry
     * @param string $source_document Optional source document reference
     * @return array ['success' => bool, 'entry_id' => int, 'entry_number' => string, 'error' => string]
     */
    public function createJournalEntry($periodid, $entry_date, $entry_type, $description, $lines, $created_by, $source_document = null) {
        try {
            // Validate lines
            $validation = $this->validateJournalLines($lines);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }
            
            // Calculate total amount
            $total_amount = $validation['total_amount'];
            
            // Generate entry number
            $entry_number = $this->generateEntryNumber($periodid);
            
            // Begin transaction
            mysqli_begin_transaction($this->db);
            
            try {
                // Insert journal entry header
                $sql = "INSERT INTO coop_journal_entries 
                        (entry_number, entry_date, periodid, entry_type, source_document, description, total_amount, created_by, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
                
                $stmt = mysqli_prepare($this->db, $sql);
                mysqli_stmt_bind_param($stmt, "ssisssdi", 
                    $entry_number, $entry_date, $periodid, $entry_type, 
                    $source_document, $description, $total_amount, $created_by
                );
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to create journal entry: " . mysqli_stmt_error($stmt));
                }
                
                $entry_id = mysqli_insert_id($this->db);
                mysqli_stmt_close($stmt);
                
                // Insert journal entry lines
                $line_number = 1;
                foreach ($lines as $line) {
                    $this->insertJournalLine($entry_id, $line_number, $line);
                    $line_number++;
                }
                
                // Commit transaction
                mysqli_commit($this->db);
                
                return [
                    'success' => true,
                    'entry_id' => $entry_id,
                    'entry_number' => $entry_number,
                    'total_amount' => $total_amount
                ];
                
            } catch (Exception $e) {
                mysqli_rollback($this->db);
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("AccountingEngine::createJournalEntry - Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Post a journal entry (make it permanent)
     * Updates period balances
     * 
     * @param int $entry_id Journal entry ID
     * @return array ['success' => bool, 'error' => string]
     */
    public function postEntry($entry_id) {
        try {
            // Get entry details
            $entry = $this->getJournalEntry($entry_id);
            if (!$entry) {
                return ['success' => false, 'error' => 'Journal entry not found'];
            }
            
            if ($entry['status'] !== 'draft') {
                return ['success' => false, 'error' => 'Only draft entries can be posted'];
            }
            
            // Begin transaction
            mysqli_begin_transaction($this->db);
            
            try {
                // Update entry status
                $sql = "UPDATE coop_journal_entries SET status = 'posted' WHERE id = ?";
                $stmt = mysqli_prepare($this->db, $sql);
                mysqli_stmt_bind_param($stmt, "i", $entry_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                // Get all lines for this entry
                $lines = $this->getJournalEntryLines($entry_id);
                
                // Update period balances for each account
                foreach ($lines as $line) {
                    $this->updatePeriodBalance(
                        $entry['periodid'],
                        $line['account_id'],
                        $line['debit_amount'],
                        $line['credit_amount']
                    );
                }
                
                // Log to audit trail
                $this->logAuditTrail(
                    $entry['created_by'],
                    'post',
                    'coop_journal_entries',
                    $entry_id,
                    json_encode(['status' => 'draft']),
                    json_encode(['status' => 'posted'])
                );
                
                mysqli_commit($this->db);
                
                return ['success' => true];
                
            } catch (Exception $e) {
                mysqli_rollback($this->db);
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("AccountingEngine::postEntry - Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Reverse a journal entry
     * Creates a new entry with opposite debits/credits
     * 
     * @param int $entry_id Original entry ID to reverse
     * @param int $user_id User performing the reversal
     * @param string $reason Reason for reversal
     * @return array ['success' => bool, 'reversing_entry_id' => int, 'error' => string]
     */
    public function reverseEntry($entry_id, $user_id, $reason) {
        try {
            // Get original entry
            $original = $this->getJournalEntry($entry_id);
            if (!$original) {
                return ['success' => false, 'error' => 'Original entry not found'];
            }
            
            if ($original['status'] !== 'posted') {
                return ['success' => false, 'error' => 'Only posted entries can be reversed'];
            }
            
            if ($original['is_reversed']) {
                return ['success' => false, 'error' => 'Entry has already been reversed'];
            }
            
            // Get original lines
            $original_lines = $this->getJournalEntryLines($entry_id);
            
            // Create reversing lines (swap debits and credits)
            $reversing_lines = [];
            foreach ($original_lines as $line) {
                $reversing_lines[] = [
                    'account_id' => $line['account_id'],
                    'debit_amount' => $line['credit_amount'],
                    'credit_amount' => $line['debit_amount'],
                    'description' => 'REVERSAL: ' . $line['description'],
                    'reference_type' => $line['reference_type'],
                    'reference_id' => $line['reference_id']
                ];
            }
            
            // Create reversing entry
            $result = $this->createJournalEntry(
                $original['periodid'],
                date('Y-m-d'),
                'adjustment',
                "REVERSAL: " . $original['description'] . " - Reason: " . $reason,
                $reversing_lines,
                $user_id,
                "REV-" . $original['entry_number']
            );
            
            if (!$result['success']) {
                return $result;
            }
            
            // Post the reversing entry
            $post_result = $this->postEntry($result['entry_id']);
            if (!$post_result['success']) {
                return $post_result;
            }
            
            // Mark original as reversed
            $sql = "UPDATE coop_journal_entries SET is_reversed = TRUE, reversed_by_entry_id = ? WHERE id = ?";
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $result['entry_id'], $entry_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            return [
                'success' => true,
                'reversing_entry_id' => $result['entry_id'],
                'reversing_entry_number' => $result['entry_number']
            ];
            
        } catch (Exception $e) {
            error_log("AccountingEngine::reverseEntry - Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validate journal entry lines
     * Ensures debits = credits
     * 
     * @param array $lines Journal entry lines
     * @return array ['valid' => bool, 'error' => string, 'total_amount' => float]
     */
    private function validateJournalLines($lines) {
        if (empty($lines)) {
            return ['valid' => false, 'error' => 'Journal entry must have at least one line'];
        }
        
        if (count($lines) < 2) {
            return ['valid' => false, 'error' => 'Journal entry must have at least two lines (debit and credit)'];
        }
        
        $total_debits = 0;
        $total_credits = 0;
        
        foreach ($lines as $line) {
            // Validate required fields
            if (!isset($line['account_id']) || empty($line['account_id'])) {
                return ['valid' => false, 'error' => 'Account ID is required for all lines'];
            }
            
            $debit = isset($line['debit_amount']) ? floatval($line['debit_amount']) : 0;
            $credit = isset($line['credit_amount']) ? floatval($line['credit_amount']) : 0;
            
            // Ensure each line is either debit OR credit, not both
            if ($debit > 0 && $credit > 0) {
                return ['valid' => false, 'error' => 'Each line must be either debit OR credit, not both'];
            }
            
            if ($debit == 0 && $credit == 0) {
                return ['valid' => false, 'error' => 'Each line must have either a debit or credit amount'];
            }
            
            $total_debits += $debit;
            $total_credits += $credit;
        }
        
        // Check if debits = credits (allow small rounding difference)
        $difference = abs($total_debits - $total_credits);
        if ($difference > 0.01) {
            return [
                'valid' => false, 
                'error' => sprintf(
                    'Debits (%.2f) must equal credits (%.2f). Difference: %.2f',
                    $total_debits,
                    $total_credits,
                    $difference
                )
            ];
        }
        
        return [
            'valid' => true,
            'total_amount' => $total_debits
        ];
    }
    
    /**
     * Insert a journal entry line
     * 
     * @param int $entry_id Journal entry ID
     * @param int $line_number Line number
     * @param array $line Line data
     */
    private function insertJournalLine($entry_id, $line_number, $line) {
        $sql = "INSERT INTO coop_journal_entry_lines 
                (journal_entry_id, line_number, account_id, debit_amount, credit_amount, description, reference_type, reference_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($this->db, $sql);
        
        $debit = isset($line['debit_amount']) ? floatval($line['debit_amount']) : 0;
        $credit = isset($line['credit_amount']) ? floatval($line['credit_amount']) : 0;
        $description = isset($line['description']) ? $line['description'] : '';
        $reference_type = isset($line['reference_type']) ? $line['reference_type'] : null;
        $reference_id = isset($line['reference_id']) ? $line['reference_id'] : null;
        
        mysqli_stmt_bind_param($stmt, "iiiddssi",
            $entry_id,
            $line_number,
            $line['account_id'],
            $debit,
            $credit,
            $description,
            $reference_type,
            $reference_id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to insert journal line: " . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Update period balance for an account
     * 
     * @param int $periodid Period ID
     * @param int $account_id Account ID
     * @param float $debit_amount Debit amount to add
     * @param float $credit_amount Credit amount to add
     */
    private function updatePeriodBalance($periodid, $account_id, $debit_amount, $credit_amount) {
        // Check if balance record exists
        $sql = "SELECT id FROM coop_period_balances WHERE periodid = ? AND account_id = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $periodid, $account_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($exists) {
            // Update existing balance
            $sql = "UPDATE coop_period_balances 
                    SET period_debit = period_debit + ?, 
                        period_credit = period_credit + ?,
                        closing_debit = opening_debit + period_debit + ?,
                        closing_credit = opening_credit + period_credit + ?
                    WHERE periodid = ? AND account_id = ?";
            
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, "ddddii",
                $debit_amount,
                $credit_amount,
                $debit_amount,
                $credit_amount,
                $periodid,
                $account_id
            );
        } else {
            // Insert new balance record
            $sql = "INSERT INTO coop_period_balances 
                    (periodid, account_id, opening_debit, opening_credit, period_debit, period_credit, closing_debit, closing_credit) 
                    VALUES (?, ?, 0, 0, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, "iidddd",
                $periodid,
                $account_id,
                $debit_amount,
                $credit_amount,
                $debit_amount,
                $credit_amount
            );
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update period balance: " . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Generate unique entry number
     * Format: JE-YYYY-NNNN (e.g., JE-2024-0001)
     * 
     * @param int $periodid Period ID
     * @return string Entry number
     */
    private function generateEntryNumber($periodid) {
        // Get period details
        $sql = "SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $period = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Extract year from period (assuming format contains year)
        $year = date('Y');
        if ($period && preg_match('/(\d{4})/', $period['PayrollPeriod'], $matches)) {
            $year = $matches[1];
        }
        
        // Get next sequence number for this year
        $sql = "SELECT MAX(CAST(SUBSTRING(entry_number, -4) AS UNSIGNED)) as max_num 
                FROM coop_journal_entries 
                WHERE entry_number LIKE ?";
        
        $pattern = "JE-{$year}-%";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "s", $pattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        $next_num = ($row['max_num'] ?? 0) + 1;
        
        return sprintf("JE-%s-%04d", $year, $next_num);
    }
    
    /**
     * Get journal entry by ID
     * 
     * @param int $entry_id Entry ID
     * @return array|null Entry data
     */
    public function getJournalEntry($entry_id) {
        $sql = "SELECT * FROM coop_journal_entries WHERE id = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $entry_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $entry = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $entry;
    }
    
    /**
     * Get journal entry lines
     * 
     * @param int $entry_id Entry ID
     * @return array Array of lines
     */
    public function getJournalEntryLines($entry_id) {
        $sql = "SELECT jel.*, a.account_code, a.account_name 
                FROM coop_journal_entry_lines jel
                JOIN coop_accounts a ON jel.account_id = a.id
                WHERE jel.journal_entry_id = ?
                ORDER BY jel.line_number";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $entry_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $lines = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $lines[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $lines;
    }
    
    /**
     * Log action to audit trail
     * 
     * @param int $user_id User ID
     * @param string $action_type Action type
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @param string $old_values Old values (JSON)
     * @param string $new_values New values (JSON)
     */
    private function logAuditTrail($user_id, $action_type, $table_name, $record_id, $old_values = null, $new_values = null) {
        $sql = "INSERT INTO coop_audit_trail 
                (user_id, action_type, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ississss",
            $user_id,
            $action_type,
            $table_name,
            $record_id,
            $old_values,
            $new_values,
            $ip_address,
            $user_agent
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Get account details
     * 
     * @param int $account_id Account ID
     * @return array|null Account data
     */
    public function getAccount($account_id) {
        $sql = "SELECT * FROM coop_accounts WHERE id = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $account_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $account = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $account;
    }
    
    /**
     * Get account by code
     * 
     * @param string $account_code Account code
     * @return array|null Account data
     */
    public function getAccountByCode($account_code) {
        $sql = "SELECT * FROM coop_accounts WHERE account_code = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "s", $account_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $account = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $account;
    }
}
