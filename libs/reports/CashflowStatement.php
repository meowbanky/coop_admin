<?php
/**
 * CashflowStatement - Generate Cashflow Statement
 * 
 * Generates complete Cashflow Statement showing operating, investing, and financing activities
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class CashflowStatement {
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
     * Generate Cashflow Statement
     * 
     * @param int $periodid Period ID
     * @param array $comparative_periods Optional array of period IDs for comparison
     * @return array Statement data
     */
    public function generateStatement($periodid, $comparative_periods = []) {
        try {
            $periods = array_merge([$periodid], $comparative_periods);
            $statement = [];
            
            foreach ($periods as $period) {
                $statement[$period] = $this->generateForPeriod($period);
            }
            
            return [
                'success' => true,
                'statement' => $statement,
                'periods' => $periods
            ];
            
        } catch (Exception $e) {
            error_log("CashflowStatement::generateStatement - Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate cashflow statement for a single period
     */
    private function generateForPeriod($periodid) {
        // Get net profit from income statement
        $net_profit = $this->getNetProfit($periodid);
        
        // OPERATING ACTIVITIES
        $operating = [];
        $operating['net_profit'] = $net_profit;
        
        // Add back: Depreciation (non-cash expense)
        $operating['depreciation'] = $this->getAccountPeriodTotal(81, $periodid); // 6018
        
        // Add: Accrued income
        $operating['accrued_income'] = 0; // Can be calculated if needed
        
        // Working Capital Changes
        $operating['working_capital'] = [
            'member_loans' => $this->getWorkingCapitalChange(6, $periodid), // 1110
            'receivables' => $this->getWorkingCapitalChange(7, $periodid), // 1120
            'payables' => $this->getWorkingCapitalChange(22, $periodid), // 2101
            'other_payables' => $this->getWorkingCapitalChange(23, $periodid), // 2102
            'inventory' => $this->getWorkingCapitalChange(8, $periodid) // 1130
        ];
        
        $operating['total_working_capital'] = array_sum($operating['working_capital']);
        $operating['net_cashflow_operating'] = $operating['net_profit'] + 
                                               $operating['depreciation'] + 
                                               $operating['accrued_income'] + 
                                               $operating['total_working_capital'];
        
        // INVESTING ACTIVITIES
        $investing = [];
        $investing['fixed_asset_purchases'] = $this->getFixedAssetPurchases($periodid);
        $investing['fixed_asset_proceeds'] = $this->getFixedAssetProceeds($periodid);
        $investing['loan_invested'] = 0; // External loans given
        $investing['loan_repayment_received'] = 0; // External loan repayments received
        
        $investing['net_cashflow_investing'] = $investing['fixed_asset_proceeds'] + 
                                               $investing['loan_repayment_received'] -
                                               $investing['fixed_asset_purchases'] - 
                                               $investing['loan_invested'];
        
        // FINANCING ACTIVITIES
        $financing = [];
        $financing['members_fund_change'] = $this->getMembersFundChange($periodid);
        $financing['borrowed_loans_change'] = $this->getBorrowedLoansChange($periodid);
        
        $financing['net_cashflow_financing'] = $financing['members_fund_change'] + 
                                               $financing['borrowed_loans_change'];
        
        // SUMMARY
        $net_cashflow = $operating['net_cashflow_operating'] + 
                       $investing['net_cashflow_investing'] + 
                       $financing['net_cashflow_financing'];
        
        $cash_beginning = $this->getCashBalance($periodid, 'opening');
        $cash_ending = $this->getCashBalance($periodid, 'closing');
        
        return [
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'net_cashflow' => $net_cashflow,
            'cash_beginning' => $cash_beginning,
            'cash_ending' => $cash_ending,
            'verification' => abs(($cash_beginning + $net_cashflow) - $cash_ending) < 0.01
        ];
    }
    
    /**
     * Get net profit for period (Revenue - Expenses)
     */
    private function getNetProfit($periodid) {
        // Revenue
        $revenue_sql = "SELECT 
                        SUM(COALESCE(pb.period_credit, 0) - COALESCE(pb.period_debit, 0)) as total
                    FROM coop_accounts a
                    LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                    WHERE a.account_type = 'revenue' AND a.is_active = TRUE";
        
        $stmt = mysqli_prepare($this->db, $revenue_sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $revenue = floatval($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
        
        // Expenses
        $expense_sql = "SELECT 
                        SUM(COALESCE(pb.period_debit, 0) - COALESCE(pb.period_credit, 0)) as total
                    FROM coop_accounts a
                    LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                    WHERE a.account_type = 'expense' AND a.is_active = TRUE";
        
        $stmt = mysqli_prepare($this->db, $expense_sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $expense = floatval($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
        
        return $revenue - $expense;
    }
    
    /**
     * Get working capital change (increase in asset = cash out, increase in liability = cash in)
     */
    private function getWorkingCapitalChange($account_id, $periodid) {
        $sql = "SELECT 
                    a.normal_balance,
                    a.account_type,
                    COALESCE(pb.period_debit, 0) as period_debit,
                    COALESCE(pb.period_credit, 0) as period_credit
                FROM coop_accounts a
                LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                WHERE a.id = ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $periodid, $account_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$row) return 0;
        
        $change = floatval($row['period_debit']) - floatval($row['period_credit']);
        
        // For assets: increase = cash out (negative), decrease = cash in (positive)
        if ($row['account_type'] == 'asset') {
            return -$change;
        }
        
        // For liabilities: increase = cash in (positive), decrease = cash out (negative)
        if ($row['account_type'] == 'liability') {
            return $change;
        }
        
        return 0;
    }
    
    /**
     * Get fixed asset purchases (from journal entries)
     */
    private function getFixedAssetPurchases($periodid) {
        // Sum of debits to fixed asset accounts (1200 series)
        $sql = "SELECT SUM(jel.debit_amount) as total
                FROM coop_journal_entry_lines jel
                JOIN coop_journal_entries je ON jel.journal_entry_id = je.id
                JOIN coop_accounts a ON jel.account_id = a.id
                WHERE je.periodid = ? 
                AND je.status = 'posted'
                AND a.account_code LIKE '12%'
                AND a.account_code NOT LIKE '121%'"; // Exclude depreciation accounts
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return floatval($row['total'] ?? 0);
    }
    
    /**
     * Get fixed asset disposal proceeds
     */
    private function getFixedAssetProceeds($periodid) {
        // Sum of credits to fixed asset accounts (disposals)
        $sql = "SELECT SUM(jel.credit_amount) as total
                FROM coop_journal_entry_lines jel
                JOIN coop_journal_entries je ON jel.journal_entry_id = je.id
                JOIN coop_accounts a ON jel.account_id = a.id
                WHERE je.periodid = ? 
                AND je.status = 'posted'
                AND a.account_code LIKE '12%'
                AND a.account_code NOT LIKE '121%'"; // Exclude depreciation accounts
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return floatval($row['total'] ?? 0);
    }
    
    /**
     * Get members fund change (shares + savings)
     */
    private function getMembersFundChange($periodid) {
        $sql = "SELECT 
                    SUM(COALESCE(pb.period_credit, 0) - COALESCE(pb.period_debit, 0)) as total
                FROM coop_accounts a
                LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                WHERE a.account_code LIKE '31%'"; // Members fund accounts
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return floatval($row['total'] ?? 0);
    }
    
    /**
     * Get borrowed loans change
     */
    private function getBorrowedLoansChange($periodid) {
        $sql = "SELECT 
                    SUM(COALESCE(pb.period_credit, 0) - COALESCE(pb.period_debit, 0)) as total
                FROM coop_accounts a
                LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                WHERE a.account_code LIKE '22%'"; // Non-current liabilities
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return floatval($row['total'] ?? 0);
    }
    
    /**
     * Get cash balance (cash + bank)
     */
    private function getCashBalance($periodid, $balance_type = 'closing') {
        $balance_field = ($balance_type == 'opening') ? 'opening_debit' : 'closing_debit';
        
        // Cash (1101) + Bank Main (1102) + Bank Savings (1103)
        $sql = "SELECT 
                    SUM(COALESCE(pb.{$balance_field}, 0)) as total
                FROM coop_accounts a
                LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                WHERE a.id IN (2, 3, 4)"; // Cash and bank accounts
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return floatval($row['total'] ?? 0);
    }
    
    /**
     * Get account period total
     */
    private function getAccountPeriodTotal($account_id, $periodid) {
        $sql = "SELECT 
                    COALESCE(pb.period_debit, 0) as period_debit,
                    COALESCE(pb.period_credit, 0) as period_credit
                FROM coop_period_balances pb
                WHERE pb.account_id = ? AND pb.periodid = ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $account_id, $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$row) return 0;
        
        // For expenses, return debit
        return floatval($row['period_debit']);
    }
}
