<?php
/**
 * BankReconciliationService - Handle Bank Reconciliation
 * 
 * Manages bank reconciliation, matching book balance with bank statement
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class BankReconciliationService {
    private $db;
    private $database_name;
    
    public function __construct($database_connection, $database_name = null) {
        $this->db = $database_connection;
        $this->database_name = $database_name;
    }
    
    /**
     * Create bank reconciliation
     * 
     * @param array $reconciliation_data Reconciliation details
     * @return array ['success' => bool, 'reconciliation_id' => int, 'error' => string]
     */
    public function createReconciliation($reconciliation_data) {
        try {
            $periodid = intval($reconciliation_data['periodid']);
            $bank_account_id = intval($reconciliation_data['bank_account_id']);
            $reconciliation_date = $reconciliation_data['reconciliation_date'];
            $bank_statement_balance = floatval($reconciliation_data['bank_statement_balance']);
            $book_balance = floatval($reconciliation_data['book_balance']);
            $outstanding_deposits = floatval($reconciliation_data['outstanding_deposits'] ?? 0);
            $outstanding_withdrawals = floatval($reconciliation_data['outstanding_withdrawals'] ?? 0);
            $bank_charges = floatval($reconciliation_data['bank_charges'] ?? 0);
            $bank_interest = floatval($reconciliation_data['bank_interest'] ?? 0);
            $reconciled_by = intval($reconciliation_data['reconciled_by']);
            $notes = $reconciliation_data['notes'] ?? '';
            
            // Calculate reconciled balance
            // Bank statement + outstanding deposits - outstanding withdrawals
            $adjusted_bank_balance = $bank_statement_balance + $outstanding_deposits - $outstanding_withdrawals;
            
            // Book balance + bank interest - bank charges
            $adjusted_book_balance = $book_balance + $bank_interest - $bank_charges;
            
            $reconciled_balance = $adjusted_bank_balance;
            $variance = $adjusted_bank_balance - $adjusted_book_balance;
            $is_balanced = abs($variance) < 0.01;
            
            // Insert reconciliation record
            $sql = "INSERT INTO coop_bank_reconciliation 
                    (periodid, bank_account_id, reconciliation_date, bank_statement_balance, book_balance,
                     outstanding_deposits, outstanding_withdrawals, bank_charges, bank_interest,
                     reconciled_balance, is_balanced, variance, reconciled_by, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $periodid,
                $bank_account_id,
                $reconciliation_date,
                $bank_statement_balance,
                $book_balance,
                $outstanding_deposits,
                $outstanding_withdrawals,
                $bank_charges,
                $bank_interest,
                $reconciled_balance,
                $is_balanced,
                $variance,
                $reconciled_by,
                $notes
            ]);
            
            $reconciliation_id = $this->db->lastInsertId();
            
            // If there are bank charges or interest, create adjusting journal entries
            if ($bank_charges > 0 || $bank_interest > 0) {
                $this->createAdjustingEntries($periodid, $bank_account_id, $bank_charges, $bank_interest, $reconciled_by);
            }
            
            return [
                'success' => true,
                'reconciliation_id' => $reconciliation_id,
                'is_balanced' => $is_balanced,
                'variance' => $variance,
                'adjusted_bank_balance' => $adjusted_bank_balance,
                'adjusted_book_balance' => $adjusted_book_balance
            ];
            
        } catch (Exception $e) {
            error_log("BankReconciliationService::createReconciliation - Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create adjusting journal entries for bank charges and interest
     */
    private function createAdjustingEntries($periodid, $bank_account_id, $bank_charges, $bank_interest, $user_id) {
        require_once('AccountingEngine.php');
        $engine = new AccountingEngine($this->db, $this->database_name);
        
        // Bank charges entry
        if ($bank_charges > 0) {
            $lines = [
                [
                    'account_id' => 72, // Bank Charges (6009)
                    'debit_amount' => $bank_charges,
                    'credit_amount' => 0,
                    'description' => 'Bank charges per bank statement'
                ],
                [
                    'account_id' => $bank_account_id,
                    'debit_amount' => 0,
                    'credit_amount' => $bank_charges,
                    'description' => 'Bank charges deducted'
                ]
            ];
            
            $result = $engine->createJournalEntry(
                $periodid,
                date('Y-m-d'),
                'adjustment',
                'Bank charges - Reconciliation adjustment',
                $lines,
                $user_id,
                'BANK-RECON-CHARGES'
            );
            
            if ($result['success']) {
                $engine->postEntry($result['entry_id']);
            }
        }
        
        // Bank interest entry
        if ($bank_interest > 0) {
            $lines = [
                [
                    'account_id' => $bank_account_id,
                    'debit_amount' => $bank_interest,
                    'credit_amount' => 0,
                    'description' => 'Bank interest earned'
                ],
                [
                    'account_id' => 59, // Other Income (4299) or create Bank Interest Income account
                    'debit_amount' => 0,
                    'credit_amount' => $bank_interest,
                    'description' => 'Bank interest income'
                ]
            ];
            
            $result = $engine->createJournalEntry(
                $periodid,
                date('Y-m-d'),
                'adjustment',
                'Bank interest - Reconciliation adjustment',
                $lines,
                $user_id,
                'BANK-RECON-INTEREST'
            );
            
            if ($result['success']) {
                $engine->postEntry($result['entry_id']);
            }
        }
    }
    
    /**
     * Get reconciliation history
     * 
     * @param int $bank_account_id Bank account ID
     * @param int $limit Number of records to retrieve
     * @return array Reconciliation records
     */
    public function getReconciliationHistory($bank_account_id = null, $limit = 10) {
        $sql = "SELECT 
                    br.*,
                    a.account_code,
                    a.account_name,
                    pp.PayrollPeriod
                FROM coop_bank_reconciliation br
                JOIN coop_accounts a ON br.bank_account_id = a.id
                LEFT JOIN tbpayrollperiods pp ON br.periodid = pp.id";
        
        $params = [];
        if ($bank_account_id) {
            $sql .= " WHERE br.bank_account_id = ?";
            $params[] = $bank_account_id;
        }
        
        $sql .= " ORDER BY br.reconciliation_date DESC, br.id DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $records = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $records[] = $row;
        }
        
        return $records;
    }
    
    /**
     * Get book balance for bank account
     * 
     * @param int $bank_account_id Bank account ID
     * @param int $periodid Period ID
     * @return float Book balance
     */
    public function getBookBalance($bank_account_id, $periodid) {
        require_once('AccountBalanceCalculator.php');
        $calculator = new AccountBalanceCalculator($this->db, $this->database_name);
        
        $balance = $calculator->getAccountBalance($bank_account_id, $periodid);
        return $balance['balance'];
    }
}
