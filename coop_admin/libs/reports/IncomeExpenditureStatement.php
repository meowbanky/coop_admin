<?php
/**
 * IncomeExpenditureStatement - Generate Income & Expenditure Statement
 * 
 * Generates complete Income & Expenditure Statement (Profit & Loss)
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class IncomeExpenditureStatement {
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
     * Generate Income & Expenditure Statement
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
            error_log("IncomeExpenditureStatement::generateStatement - Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate statement for a single period
     */
    private function generateForPeriod($periodid) {
        // REVENUE SECTION
        $revenue = [
            'entrance_fee' => $this->getAccountTotal(49, $periodid), // 4101
            'interest_charges' => $this->getAccountTotal(50, $periodid), // 4102
            'other_income' => $this->getAccountCategoryTotal('other_revenue', $periodid)
        ];
        $revenue['total_revenue'] = array_sum($revenue);
        
        // COST OF SALES
        $cost_of_sales = $this->getAccountCategoryTotal('cost_of_sales', $periodid);
        
        // GROSS PROFIT
        $gross_profit = $revenue['total_revenue'] - $cost_of_sales;
        
        // OVERHEAD EXPENSES (Detailed breakdown)
        $overhead = [
            'professional_services' => $this->getAccountTotal(64, $periodid), // 6001
            'printing_stationery' => $this->getAccountTotal(65, $periodid), // 6002
            'telephone' => $this->getAccountTotal(66, $periodid), // 6003
            'internet' => $this->getAccountTotal(67, $periodid), // 6004
            'transport_travelling' => $this->getAccountTotal(68, $periodid), // 6005
            'sundry_expenses' => $this->getAccountTotal(69, $periodid), // 6006
            'advertisement' => $this->getAccountTotal(70, $periodid), // 6007
            'office_expenses' => $this->getAccountTotal(71, $periodid), // 6008
            'bank_charges' => $this->getAccountTotal(72, $periodid), // 6009
            'fueling' => $this->getAccountTotal(73, $periodid), // 6010
            'salary_cost' => $this->getAccountTotal(74, $periodid), // 6011
            'training' => $this->getAccountTotal(75, $periodid), // 6012
            'entertainment' => $this->getAccountTotal(76, $periodid), // 6013
            'agm_expenses' => $this->getAccountTotal(77, $periodid), // 6014
            'award' => $this->getAccountTotal(78, $periodid), // 6015
            'sitting_allowance' => $this->getAccountTotal(79, $periodid), // 6016
            'discount_allowed' => $this->getAccountTotal(80, $periodid), // 6017
            'depreciation' => $this->getAccountTotal(81, $periodid) // 6018
        ];
        $overhead['total_expenses'] = array_sum($overhead);
        
        // SURPLUS (DEFICIT)
        $surplus = $gross_profit - $overhead['total_expenses'];
        
        // Get appropriation if exists
        $appropriation_sql = "SELECT * FROM coop_appropriation WHERE periodid = ?";
        $stmt = mysqli_prepare($this->db, $appropriation_sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $appropriation_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        $appropriation = [
            'dividend' => floatval($appropriation_data['dividend_amount'] ?? 0),
            'interest_to_members' => floatval($appropriation_data['interest_to_members'] ?? 0),
            'reserve_fund' => floatval($appropriation_data['reserve_fund'] ?? 0),
            'bonus' => floatval($appropriation_data['bonus_amount'] ?? 0),
            'education_fund' => floatval($appropriation_data['education_fund'] ?? 0),
            'honorarium' => floatval($appropriation_data['honorarium'] ?? 0),
            'general_reserve' => floatval($appropriation_data['general_reserve'] ?? 0),
            'welfare_fund' => floatval($appropriation_data['welfare_fund'] ?? 0)
        ];
        $appropriation['total_appropriation'] = array_sum($appropriation);
        
        // NET PROFIT BROUGHT DOWN
        $net_profit_bd = $surplus - $appropriation['total_appropriation'];
        
        return [
            'revenue' => $revenue,
            'cost_of_sales' => $cost_of_sales,
            'gross_profit' => $gross_profit,
            'overhead' => $overhead,
            'surplus' => $surplus,
            'appropriation' => $appropriation,
            'net_profit_bd' => $net_profit_bd
        ];
    }
    
    /**
     * Get total for specific account
     */
    private function getAccountTotal($account_id, $periodid) {
        $sql = "SELECT 
                    a.normal_balance,
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
        
        $debit = floatval($row['period_debit']);
        $credit = floatval($row['period_credit']);
        
        // For revenue/expense, we want the net movement
        if ($row['normal_balance'] == 'credit') {
            return $credit - $debit;
        } else {
            return $debit - $credit;
        }
    }
    
    /**
     * Get total for account category
     */
    private function getAccountCategoryTotal($category, $periodid) {
        $sql = "SELECT 
                    a.normal_balance,
                    SUM(COALESCE(pb.period_debit, 0)) as total_debit,
                    SUM(COALESCE(pb.period_credit, 0)) as total_credit
                FROM coop_accounts a
                LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
                WHERE a.account_category = ? AND a.is_active = TRUE
                GROUP BY a.normal_balance";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "is", $periodid, $category);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $total = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $debit = floatval($row['total_debit']);
            $credit = floatval($row['total_credit']);
            
            if ($row['normal_balance'] == 'credit') {
                $total += ($credit - $debit);
            } else {
                $total += ($debit - $credit);
            }
        }
        
        mysqli_stmt_close($stmt);
        return $total;
    }
}
