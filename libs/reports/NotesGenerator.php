<?php
/**
 * NotesGenerator - Generate Notes to the Account
 * 
 * Generates detailed supporting notes for financial statements
 * 
 * @version 1.0
 * @author Cooperative Management System
 */
class NotesGenerator {
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
     * Generate Note 1: Member Loan Account
     */
    public function generateNote1($periodid) {
        $opening = $this->getPreviousPeriodBalance(6, $periodid); // Member Loans
        
        // Loans disbursed = debits to member loan account
        $sql = "SELECT SUM(jel.debit_amount) as total
                FROM coop_journal_entry_lines jel
                JOIN coop_journal_entries je ON jel.journal_entry_id = je.id
                WHERE je.periodid = ? AND je.status = 'posted' AND jel.account_id = 6";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $loans_disbursed = floatval($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
        
        // Loans recovered = credits to member loan account
        $sql = "SELECT SUM(jel.credit_amount) as total
                FROM coop_journal_entry_lines jel
                JOIN coop_journal_entries je ON jel.journal_entry_id = je.id
                WHERE je.periodid = ? AND je.status = 'posted' AND jel.account_id = 6";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $loans_recovered = floatval($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
        
        $closing = $opening + $loans_disbursed - $loans_recovered;
        
        return [
            'opening_balance' => $opening,
            'loans_disbursed' => $loans_disbursed,
            'loans_recovered' => $loans_recovered,
            'closing_balance' => $closing
        ];
    }
    
    /**
     * Generate Note 2: Members Fund (Shares & Savings)
     */
    public function generateNote2($periodid) {
        // Shares
        $shares = [
            'opening' => $this->getPreviousPeriodBalance(33, $periodid), // Ordinary Shares
            'contributions' => $this->getPeriodCredits(33, $periodid),
            'withdrawals' => $this->getPeriodDebits(33, $periodid)
        ];
        $shares['closing'] = $shares['opening'] + $shares['contributions'] - $shares['withdrawals'];
        
        // Ordinary Savings
        $savings = [
            'opening' => $this->getPreviousPeriodBalance(37, $periodid), // Ordinary Savings
            'contributions' => $this->getPeriodCredits(37, $periodid),
            'withdrawals' => $this->getPeriodDebits(37, $periodid)
        ];
        $savings['closing'] = $savings['opening'] + $savings['contributions'] - $savings['withdrawals'];
        
        // Special Savings
        $special = [
            'opening' => $this->getPreviousPeriodBalance(38, $periodid), // Special Savings
            'contributions' => $this->getPeriodCredits(38, $periodid),
            'withdrawals' => $this->getPeriodDebits(38, $periodid)
        ];
        $special['closing'] = $special['opening'] + $special['contributions'] - $special['withdrawals'];
        
        return [
            'shares' => $shares,
            'savings' => $savings,
            'special_savings' => $special,
            'total_members_fund' => $shares['closing'] + $savings['closing'] + $special['closing']
        ];
    }
    
    /**
     * Generate Note 3-5: Reserve Funds
     */
    public function generateReserveNotes($periodid) {
        $reserves = [];
        
        $reserve_accounts = [
            'statutory_reserve' => 40, // 3301
            'general_reserve' => 41, // 3302
            'education_fund' => 42, // 3303
            'welfare_fund' => 43, // 3304
            'building_fund' => 44 // 3305
        ];
        
        foreach ($reserve_accounts as $name => $account_id) {
            $reserves[$name] = [
                'opening' => $this->getPreviousPeriodBalance($account_id, $periodid),
                'transfers_in' => $this->getPeriodCredits($account_id, $periodid),
                'expenses_paid' => $this->getPeriodDebits($account_id, $periodid)
            ];
            $reserves[$name]['closing'] = $reserves[$name]['opening'] + 
                                         $reserves[$name]['transfers_in'] - 
                                         $reserves[$name]['expenses_paid'];
        }
        
        return $reserves;
    }
    
    /**
     * Generate Note 7: Membership Strength
     */
    public function generateNote7($periodid) {
        // Get period date range
        $periodQuery = "SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?";
        $stmt = mysqli_prepare($this->db, $periodQuery);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $period = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Count members at start of period
        $prevPeriod = $periodid - 1;
        $sql = "SELECT COUNT(DISTINCT memberid) as count
                FROM tlb_mastertransaction
                WHERE periodid <= ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $prevPeriod);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $opening_members = intval($row['count'] ?? 0);
        mysqli_stmt_close($stmt);
        
        // Count members at end of period
        $sql = "SELECT COUNT(DISTINCT memberid) as count
                FROM tlb_mastertransaction
                WHERE periodid <= ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $closing_members = intval($row['count'] ?? 0);
        mysqli_stmt_close($stmt);
        
        $new_members = $closing_members - $opening_members;
        
        return [
            'opening_members' => $opening_members,
            'new_members' => max(0, $new_members),
            'exited_members' => max(0, -$new_members),
            'closing_members' => $closing_members
        ];
    }
    
    /**
     * Get previous period closing balance
     */
    private function getPreviousPeriodBalance($account_id, $current_periodid) {
        $sql = "SELECT closing_debit, closing_credit, normal_balance
                FROM coop_period_balances pb
                JOIN coop_accounts a ON pb.account_id = a.id
                WHERE pb.account_id = ? AND pb.periodid < ?
                ORDER BY pb.periodid DESC
                LIMIT 1";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $account_id, $current_periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$row) return 0;
        
        $debit = floatval($row['closing_debit']);
        $credit = floatval($row['closing_credit']);
        
        return ($row['normal_balance'] == 'debit') ? $debit - $credit : $credit - $debit;
    }
    
    /**
     * Get period credits for account
     */
    private function getPeriodCredits($account_id, $periodid) {
        $sql = "SELECT COALESCE(period_credit, 0) as total
                FROM coop_period_balances
                WHERE account_id = ? AND periodid = ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $account_id, $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return floatval($row['total'] ?? 0);
    }
    
    /**
     * Get period debits for account
     */
    private function getPeriodDebits($account_id, $periodid) {
        $sql = "SELECT COALESCE(period_debit, 0) as total
                FROM coop_period_balances
                WHERE account_id = ? AND periodid = ?";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $account_id, $periodid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return floatval($row['total'] ?? 0);
    }
}
