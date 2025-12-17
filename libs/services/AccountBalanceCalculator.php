<?php
/**
 * AccountBalanceCalculator - Calculate Account Balances and Generate Reports
 * 
 * Handles balance calculations, trial balance, and accounting equation verification
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class AccountBalanceCalculator {
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
     * Get account balance for a specific period
     * 
     * @param int $account_id Account ID
     * @param int $periodid Period ID
     * @param string $as_of_date Optional specific date (YYYY-MM-DD)
     * @return array ['balance' => float, 'debit' => float, 'credit' => float, 'normal_balance' => string]
     */
    public function getAccountBalance($account_id, $periodid, $as_of_date = null) {
        try {
            // Get account details
            $account = $this->getAccount($account_id);
            if (!$account) {
                return [
                    'balance' => 0,
                    'debit' => 0,
                    'credit' => 0,
                    'normal_balance' => 'debit',
                    'error' => 'Account not found'
                ];
            }
            
            // Get period balance
            $sql = "SELECT opening_debit, opening_credit, period_debit, period_credit, closing_debit, closing_credit
                    FROM coop_period_balances
                    WHERE periodid = ? AND account_id = ?";
            
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $periodid, $account_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $balance = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if (!$balance) {
                // No transactions yet, return zero
                return [
                    'balance' => 0,
                    'debit' => 0,
                    'credit' => 0,
                    'normal_balance' => $account['normal_balance']
                ];
            }
            
            // Calculate total debit and credit
            $total_debit = floatval($balance['opening_debit']) + floatval($balance['period_debit']);
            $total_credit = floatval($balance['opening_credit']) + floatval($balance['period_credit']);
            
            // Calculate net balance based on normal balance
            if ($account['normal_balance'] == 'debit') {
                $net_balance = $total_debit - $total_credit;
            } else {
                $net_balance = $total_credit - $total_debit;
            }
            
            return [
                'balance' => $net_balance,
                'debit' => $total_debit,
                'credit' => $total_credit,
                'normal_balance' => $account['normal_balance'],
                'opening_debit' => floatval($balance['opening_debit']),
                'opening_credit' => floatval($balance['opening_credit']),
                'period_debit' => floatval($balance['period_debit']),
                'period_credit' => floatval($balance['period_credit'])
            ];
            
        } catch (Exception $e) {
            error_log("AccountBalanceCalculator::getAccountBalance - Error: " . $e->getMessage());
            return [
                'balance' => 0,
                'debit' => 0,
                'credit' => 0,
                'normal_balance' => 'debit',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate trial balance for a period
     * 
     * @param int $periodid Period ID
     * @param bool $include_zero_balances Include accounts with zero balances
     * @return array ['accounts' => array, 'totals' => array, 'is_balanced' => bool]
     */
    public function getTrialBalance($periodid, $include_zero_balances = false) {
        try {
            $sql = "SELECT 
                        a.id,
                        a.account_code,
                        a.account_name,
                        a.account_type,
                        a.normal_balance,
                        COALESCE(pb.opening_debit, 0) as opening_debit,
                        COALESCE(pb.opening_credit, 0) as opening_credit,
                        COALESCE(pb.period_debit, 0) as period_debit,
                        COALESCE(pb.period_credit, 0) as period_credit,
                        COALESCE(pb.closing_debit, 0) as closing_debit,
                        COALESCE(pb.closing_credit, 0) as closing_credit
                    FROM coop_accounts a
                    LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                    WHERE a.is_active = TRUE
                    ORDER BY a.account_code";
            
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, "i", $periodid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $accounts = [];
            $total_debit = 0;
            $total_credit = 0;
            
            while ($row = mysqli_fetch_assoc($result)) {
                // Calculate balance
                $total_dr = floatval($row['opening_debit']) + floatval($row['period_debit']);
                $total_cr = floatval($row['opening_credit']) + floatval($row['period_credit']);
                
                // Determine debit or credit balance
                $debit_balance = 0;
                $credit_balance = 0;
                
                if ($row['normal_balance'] == 'debit') {
                    $net = $total_dr - $total_cr;
                    if ($net >= 0) {
                        $debit_balance = $net;
                    } else {
                        $credit_balance = abs($net);
                    }
                } else {
                    $net = $total_cr - $total_dr;
                    if ($net >= 0) {
                        $credit_balance = $net;
                    } else {
                        $debit_balance = abs($net);
                    }
                }
                
                // Skip zero balances if requested
                if (!$include_zero_balances && $debit_balance == 0 && $credit_balance == 0) {
                    continue;
                }
                
                $accounts[] = [
                    'account_id' => $row['id'],
                    'account_code' => $row['account_code'],
                    'account_name' => $row['account_name'],
                    'account_type' => $row['account_type'],
                    'debit_balance' => $debit_balance,
                    'credit_balance' => $credit_balance
                ];
                
                $total_debit += $debit_balance;
                $total_credit += $credit_balance;
            }
            
            mysqli_stmt_close($stmt);
            
            // Check if trial balance balances
            $difference = abs($total_debit - $total_credit);
            $is_balanced = $difference < 0.01; // Allow for rounding
            
            return [
                'accounts' => $accounts,
                'totals' => [
                    'debit' => $total_debit,
                    'credit' => $total_credit,
                    'difference' => $total_debit - $total_credit
                ],
                'is_balanced' => $is_balanced
            ];
            
        } catch (Exception $e) {
            error_log("AccountBalanceCalculator::getTrialBalance - Error: " . $e->getMessage());
            return [
                'accounts' => [],
                'totals' => ['debit' => 0, 'credit' => 0, 'difference' => 0],
                'is_balanced' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify accounting equation: Assets = Liabilities + Equity
     * 
     * @param int $periodid Period ID
     * @return array ['valid' => bool, 'assets' => float, 'liabilities' => float, 'equity' => float, 'difference' => float]
     */
    public function verifyAccountingEquation($periodid) {
        try {
            $assets = $this->getTotalByType($periodid, 'asset');
            $liabilities = $this->getTotalByType($periodid, 'liability');
            $equity = $this->getTotalByType($periodid, 'equity');
            
            $difference = $assets - ($liabilities + $equity);
            $is_valid = abs($difference) < 0.01;
            
            return [
                'valid' => $is_valid,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'liabilities_plus_equity' => $liabilities + $equity,
                'difference' => $difference
            ];
            
        } catch (Exception $e) {
            error_log("AccountBalanceCalculator::verifyAccountingEquation - Error: " . $e->getMessage());
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get total balance for account type
     * 
     * @param int $periodid Period ID
     * @param string $account_type Account type (asset, liability, equity, revenue, expense)
     * @return float Total balance
     */
    public function getTotalByType($periodid, $account_type) {
        $sql = "SELECT 
                    a.normal_balance,
                    SUM(COALESCE(pb.opening_debit, 0) + COALESCE(pb.period_debit, 0)) as total_debit,
                    SUM(COALESCE(pb.opening_credit, 0) + COALESCE(pb.period_credit, 0)) as total_credit
                FROM coop_accounts a
                LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                WHERE a.account_type = ? AND a.is_active = TRUE
                GROUP BY a.normal_balance";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "is", $periodid, $account_type);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $total = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $debit = floatval($row['total_debit']);
            $credit = floatval($row['total_credit']);
            
            if ($row['normal_balance'] == 'debit') {
                $total += ($debit - $credit);
            } else {
                $total += ($credit - $debit);
            }
        }
        
        mysqli_stmt_close($stmt);
        return $total;
    }
    
    /**
     * Get total balance for account category
     * 
     * @param int $periodid Period ID
     * @param string $account_category Account category
     * @return float Total balance
     */
    public function getTotalByCategory($periodid, $account_category) {
        $sql = "SELECT 
                    a.normal_balance,
                    SUM(COALESCE(pb.opening_debit, 0) + COALESCE(pb.period_debit, 0)) as total_debit,
                    SUM(COALESCE(pb.opening_credit, 0) + COALESCE(pb.period_credit, 0)) as total_credit
                FROM coop_accounts a
                LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                WHERE a.account_category = ? AND a.is_active = TRUE
                GROUP BY a.normal_balance";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "is", $periodid, $account_category);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $total = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $debit = floatval($row['total_debit']);
            $credit = floatval($row['total_credit']);
            
            if ($row['normal_balance'] == 'debit') {
                $total += ($debit - $credit);
            } else {
                $total += ($credit - $debit);
            }
        }
        
        mysqli_stmt_close($stmt);
        return $total;
    }
    
    /**
     * Aggregate control account balances
     * Ensures control account = sum of sub-accounts
     * 
     * @param int $periodid Period ID
     * @return array ['control_accounts' => array, 'mismatches' => array]
     */
    public function aggregateControlAccounts($periodid) {
        try {
            // Get all control accounts
            $sql = "SELECT id, account_code, account_name FROM coop_accounts WHERE is_control_account = TRUE";
            $result = mysqli_query($this->db, $sql);
            
            $control_accounts = [];
            $mismatches = [];
            
            while ($control = mysqli_fetch_assoc($result)) {
                // Get control account balance
                $control_balance = $this->getAccountBalance($control['id'], $periodid);
                
                // Get sum of sub-accounts
                $sub_accounts_total = $this->getSumOfSubAccounts($control['id'], $periodid);
                
                $difference = abs($control_balance['balance'] - $sub_accounts_total);
                $matches = $difference < 0.01;
                
                $control_data = [
                    'account_code' => $control['account_code'],
                    'account_name' => $control['account_name'],
                    'control_balance' => $control_balance['balance'],
                    'sub_accounts_total' => $sub_accounts_total,
                    'difference' => $control_balance['balance'] - $sub_accounts_total,
                    'matches' => $matches
                ];
                
                $control_accounts[] = $control_data;
                
                if (!$matches) {
                    $mismatches[] = $control_data;
                }
            }
            
            return [
                'control_accounts' => $control_accounts,
                'mismatches' => $mismatches,
                'all_match' => empty($mismatches)
            ];
            
        } catch (Exception $e) {
            error_log("AccountBalanceCalculator::aggregateControlAccounts - Error: " . $e->getMessage());
            return [
                'control_accounts' => [],
                'mismatches' => [],
                'all_match' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get sum of sub-account balances
     * 
     * @param int $parent_account_id Parent account ID
     * @param int $periodid Period ID
     * @return float Sum of sub-account balances
     */
    private function getSumOfSubAccounts($parent_account_id, $periodid) {
        $sql = "SELECT id, normal_balance FROM coop_accounts WHERE parent_id = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $parent_account_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $total = 0;
        while ($sub = mysqli_fetch_assoc($result)) {
            $balance = $this->getAccountBalance($sub['id'], $periodid);
            $total += $balance['balance'];
            
            // Recursively add sub-sub-accounts
            $total += $this->getSumOfSubAccounts($sub['id'], $periodid);
        }
        
        mysqli_stmt_close($stmt);
        return $total;
    }
    
    /**
     * Get account details
     * 
     * @param int $account_id Account ID
     * @return array|null Account data
     */
    private function getAccount($account_id) {
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
     * Get account summary by type for a period
     * 
     * @param int $periodid Period ID
     * @return array Account type summaries
     */
    public function getAccountSummaryByType($periodid) {
        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        $summary = [];
        
        foreach ($types as $type) {
            $summary[$type] = $this->getTotalByType($periodid, $type);
        }
        
        return $summary;
    }
    
    /**
     * Get detailed account balances for a period
     * 
     * @param int $periodid Period ID
     * @param string $account_type Optional filter by type
     * @return array Detailed account balances
     */
    public function getDetailedAccountBalances($periodid, $account_type = null) {
        $sql = "SELECT 
                    a.id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    a.normal_balance,
                    COALESCE(pb.opening_debit, 0) as opening_debit,
                    COALESCE(pb.opening_credit, 0) as opening_credit,
                    COALESCE(pb.period_debit, 0) as period_debit,
                    COALESCE(pb.period_credit, 0) as period_credit
                FROM coop_accounts a
                LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                WHERE a.is_active = TRUE";
        
        if ($account_type) {
            $sql .= " AND a.account_type = ?";
        }
        
        $sql .= " ORDER BY a.account_code";
        
        $stmt = mysqli_prepare($this->db, $sql);
        
        if ($account_type) {
            mysqli_stmt_bind_param($stmt, "is", $periodid, $account_type);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $periodid);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $accounts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $total_debit = floatval($row['opening_debit']) + floatval($row['period_debit']);
            $total_credit = floatval($row['opening_credit']) + floatval($row['period_credit']);
            
            if ($row['normal_balance'] == 'debit') {
                $balance = $total_debit - $total_credit;
            } else {
                $balance = $total_credit - $total_debit;
            }
            
            $accounts[] = [
                'account_id' => $row['id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'account_type' => $row['account_type'],
                'balance' => $balance,
                'opening_debit' => floatval($row['opening_debit']),
                'opening_credit' => floatval($row['opening_credit']),
                'period_debit' => floatval($row['period_debit']),
                'period_credit' => floatval($row['period_credit']),
                'total_debit' => $total_debit,
                'total_credit' => $total_credit
            ];
        }
        
        mysqli_stmt_close($stmt);
        return $accounts;
    }
}
