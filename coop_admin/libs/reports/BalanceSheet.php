<?php
/**
 * BalanceSheet - Generate Statement of Financial Position
 * 
 * Generates complete Balance Sheet (Assets, Liabilities, Equity)
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class BalanceSheet {
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
     * Generate Balance Sheet
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
            error_log("BalanceSheet::generateStatement - Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate balance sheet for a single period
     */
    private function generateForPeriod($periodid) {
        // NON-CURRENT ASSETS
        $non_current_assets = [
            'land_buildings' => $this->getAssetNetValue(11, 15, $periodid), // 1201 - 1210
            'furniture' => $this->getAssetNetValue(12, 16, $periodid), // 1202 - 1211
            'equipment' => $this->getAssetNetValue(13, 17, $periodid), // 1203 - 1212
            'computers' => $this->getAssetNetValue(14, 18, $periodid), // 1204 - 1213
            'vehicles' => $this->getAssetNetValue(15, 19, $periodid) // 1205 - 1214
        ];
        $total_non_current_assets = array_sum($non_current_assets);
        
        // CURRENT ASSETS
        $current_assets = [
            'cash' => $this->getAccountBalance(2, $periodid), // 1101
            'bank_main' => $this->getAccountBalance(3, $periodid), // 1102
            'bank_savings' => $this->getAccountBalance(4, $periodid), // 1103
            'member_loans' => $this->getAccountBalance(6, $periodid), // 1110
            'receivables' => $this->getAccountBalance(7, $periodid), // 1120
            'inventory' => $this->getAccountBalance(8, $periodid), // 1130
            'prepaid' => $this->getAccountBalance(9, $periodid) // 1140
        ];
        $current_assets['total_bank'] = $current_assets['bank_main'] + $current_assets['bank_savings'];
        $total_current_assets = array_sum($current_assets);
        
        // CURRENT LIABILITIES
        $current_liabilities = [
            'payables' => $this->getAccountBalance(22, $periodid), // 2101
            'accrued_expenses' => $this->getAccountBalance(23, $periodid), // 2102
            'dividend_payable' => $this->getAccountBalance(24, $periodid), // 2103
            'interest_payable' => $this->getAccountBalance(25, $periodid), // 2104
            'bonus_payable' => $this->getAccountBalance(26, $periodid), // 2105
            'honorarium_payable' => $this->getAccountBalance(27, $periodid) // 2106
        ];
        $total_current_liabilities = array_sum($current_liabilities);
        
        // NON-CURRENT LIABILITIES
        $non_current_liabilities = [
            'long_term_loans' => $this->getAccountBalance(29, $periodid), // 2201
            'borrowed_funds' => $this->getAccountBalance(30, $periodid) // 2202
        ];
        $total_non_current_liabilities = array_sum($non_current_liabilities);
        
        $total_liabilities = $total_current_liabilities + $total_non_current_liabilities;
        
        // NET CURRENT ASSETS
        $net_current_assets = $total_current_assets - $total_current_liabilities;
        
        // TOTAL NET ASSETS
        $net_assets = $total_non_current_assets + $net_current_assets;
        
        // EQUITY - MEMBERS FUND
        $members_fund = [
            'shares' => $this->getAccountBalance(33, $periodid), // 3101
            'entrance_fees' => $this->getAccountBalance(34, $periodid), // 3102
            'ordinary_savings' => $this->getAccountBalance(37, $periodid), // 3201
            'special_savings' => $this->getAccountBalance(38, $periodid) // 3202
        ];
        $total_members_fund = array_sum($members_fund);
        
        // RESERVES
        $reserves = [
            'statutory_reserve' => $this->getAccountBalance(40, $periodid), // 3301
            'general_reserve' => $this->getAccountBalance(41, $periodid), // 3302
            'education_fund' => $this->getAccountBalance(42, $periodid), // 3303
            'welfare_fund' => $this->getAccountBalance(43, $periodid), // 3304
            'building_fund' => $this->getAccountBalance(44, $periodid) // 3305
        ];
        $total_reserves = array_sum($reserves);
        
        // RETAINED EARNINGS
        $retained_earnings = $this->getAccountBalance(46, $periodid); // 3401
        
        // TOTAL EQUITY
        $total_equity = $total_members_fund + $total_reserves + $retained_earnings;
        
        // VERIFICATION
        $difference = $net_assets - $total_equity;
        $is_balanced = abs($difference) < 0.01;
        
        return [
            'non_current_assets' => $non_current_assets,
            'total_non_current_assets' => $total_non_current_assets,
            'current_assets' => $current_assets,
            'total_current_assets' => $total_current_assets,
            'current_liabilities' => $current_liabilities,
            'total_current_liabilities' => $total_current_liabilities,
            'non_current_liabilities' => $non_current_liabilities,
            'total_non_current_liabilities' => $total_non_current_liabilities,
            'total_liabilities' => $total_liabilities,
            'net_current_assets' => $net_current_assets,
            'net_assets' => $net_assets,
            'members_fund' => $members_fund,
            'total_members_fund' => $total_members_fund,
            'reserves' => $reserves,
            'total_reserves' => $total_reserves,
            'retained_earnings' => $retained_earnings,
            'total_equity' => $total_equity,
            'difference' => $difference,
            'is_balanced' => $is_balanced
        ];
    }
    
    /**
     * Get account balance
     */
    private function getAccountBalance($account_id, $periodid) {
        $sql = "SELECT 
                    a.normal_balance,
                    COALESCE(pb.opening_debit, 0) + COALESCE(pb.period_debit, 0) as total_debit,
                    COALESCE(pb.opening_credit, 0) + COALESCE(pb.period_credit, 0) as total_credit
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
        
        $debit = floatval($row['total_debit']);
        $credit = floatval($row['total_credit']);
        
        if ($row['normal_balance'] == 'debit') {
            return $debit - $credit;
        } else {
            return $credit - $debit;
        }
    }
    
    /**
     * Get asset net book value (cost - accumulated depreciation)
     */
    private function getAssetNetValue($asset_account_id, $depreciation_account_id, $periodid) {
        $cost = $this->getAccountBalance($asset_account_id, $periodid);
        $depreciation = $this->getAccountBalance($depreciation_account_id, $periodid);
        return $cost - $depreciation;
    }
    
    /**
     * Get category total
     */
    private function getAccountCategoryTotal($category, $periodid) {
        $sql = "SELECT 
                    a.normal_balance,
                    SUM(COALESCE(pb.opening_debit, 0) + COALESCE(pb.period_debit, 0)) as total_debit,
                    SUM(COALESCE(pb.opening_credit, 0) + COALESCE(pb.period_credit, 0)) as total_credit
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
