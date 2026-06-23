<?php
/**
 * NotesGenerator - Generate Notes to the Accounts
 *
 * Generates supporting notes for financial statements per Nigerian cooperative standards.
 *
 * @version 2.0 - PDO port
 */
class NotesGenerator
{
    private PDO $db;

    public function __construct($database_connection, $database_name = null)
    {
        $this->db = $database_connection;
    }

    /* -------- NOTE 1: Member Loan Account -------- */

    public function generateNote1(int $periodid): array
    {
        $opening = $this->getPreviousPeriodBalance(6, $periodid);

        $stmt = $this->db->prepare("
            SELECT SUM(jel.debit_amount) as total
            FROM coop_journal_entry_lines jel
            JOIN coop_journal_entries je ON jel.journal_entry_id = je.id
            WHERE je.periodid = ? AND je.status = 'posted' AND jel.account_id = 6
        ");
        $stmt->execute([$periodid]);
        $loans_disbursed = (float)($stmt->fetchColumn() ?? 0);

        $stmt = $this->db->prepare("
            SELECT SUM(jel.credit_amount) as total
            FROM coop_journal_entry_lines jel
            JOIN coop_journal_entries je ON jel.journal_entry_id = je.id
            WHERE je.periodid = ? AND je.status = 'posted' AND jel.account_id = 6
        ");
        $stmt->execute([$periodid]);
        $loans_recovered = (float)($stmt->fetchColumn() ?? 0);

        $closing = $opening + $loans_disbursed - $loans_recovered;

        return [
            'opening_balance'  => $opening,
            'loans_disbursed'  => $loans_disbursed,
            'loans_recovered'  => $loans_recovered,
            'closing_balance'  => $closing,
        ];
    }

    /* -------- NOTE 2: Members Fund (Shares & Savings) -------- */

    public function generateNote2(int $periodid): array
    {
        $shares  = $this->buildMovementNote(33, $periodid);
        $savings = $this->buildMovementNote(37, $periodid);
        $special = $this->buildMovementNote(38, $periodid);

        return [
            'shares'             => $shares,
            'savings'            => $savings,
            'special_savings'    => $special,
            'total_members_fund' => $shares['closing'] + $savings['closing'] + $special['closing'],
        ];
    }

    /* -------- NOTE 3-6: Reserve Funds -------- */

    public function generateReserveNotes(int $periodid): array
    {
        $reserve_accounts = [
            'statutory_reserve' => 40,
            'general_reserve'   => 41,
            'education_fund'    => 42,
            'welfare_fund'      => 43,
            'building_fund'     => 44,
        ];

        $reserves = [];
        foreach ($reserve_accounts as $name => $account_id) {
            $note = $this->buildMovementNote($account_id, $periodid);
            $note['expenses_paid'] = $note['withdrawals'];
            $note['transfers_in']  = $note['contributions'];
            $reserves[$name]       = $note;
        }

        return $reserves;
    }

    /* -------- NOTE 7: Membership Strength -------- */

    public function generateNote7(int $periodid): array
    {
        $prevPeriod = $periodid - 1;

        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT memberid) FROM tlb_mastertransaction WHERE periodid <= ?"
        );
        $stmt->execute([$prevPeriod]);
        $opening_members = (int)($stmt->fetchColumn() ?? 0);

        $stmt->execute([$periodid]);
        $closing_members = (int)($stmt->fetchColumn() ?? 0);

        $diff = $closing_members - $opening_members;

        return [
            'opening_members' => $opening_members,
            'new_members'     => max(0,  $diff),
            'exited_members'  => max(0, -$diff),
            'closing_members' => $closing_members,
        ];
    }

    /* -------- NOTE 8: Fixed Assets Register -------- */

    public function generateFixedAssetsNote(int $periodid): array
    {
        $asset_pairs = [
            'Land & Buildings' => [11, 15],
            'Furniture & Fittings' => [12, 16],
            'Office Equipment' => [13, 17],
            'Computers & IT' => [14, 18],
            'Motor Vehicles' => [15, 19],
        ];

        $rows = [];
        $total_cost = 0;
        $total_depn = 0;

        foreach ($asset_pairs as $label => [$cost_id, $depn_id]) {
            $cost = $this->getClosingBalance($cost_id, $periodid);
            $depn = $this->getClosingBalance($depn_id, $periodid);
            $nbv  = $cost - $depn;

            if ($cost == 0 && $depn == 0) continue;

            $rows[]      = compact('label', 'cost', 'depn', 'nbv');
            $total_cost += $cost;
            $total_depn += $depn;
        }

        return [
            'rows'       => $rows,
            'total_cost' => $total_cost,
            'total_depn' => $total_depn,
            'total_nbv'  => $total_cost - $total_depn,
        ];
    }

    /* -------- Helpers -------- */

    private function buildMovementNote(int $account_id, int $periodid): array
    {
        $opening       = $this->getPreviousPeriodBalance($account_id, $periodid);
        $contributions = $this->getPeriodCredits($account_id, $periodid);
        $withdrawals   = $this->getPeriodDebits($account_id, $periodid);

        return [
            'opening'       => $opening,
            'contributions' => $contributions,
            'withdrawals'   => $withdrawals,
            'closing'       => $opening + $contributions - $withdrawals,
        ];
    }

    private function getPreviousPeriodBalance(int $account_id, int $current_periodid): float
    {
        $stmt = $this->db->prepare("
            SELECT pb.closing_debit, pb.closing_credit, a.normal_balance
            FROM coop_period_balances pb
            JOIN coop_accounts a ON pb.account_id = a.id
            WHERE pb.account_id = ? AND pb.periodid < ?
            ORDER BY pb.periodid DESC
            LIMIT 1
        ");
        $stmt->execute([$account_id, $current_periodid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return 0.0;

        $dr = (float)$row['closing_debit'];
        $cr = (float)$row['closing_credit'];

        return ($row['normal_balance'] === 'debit') ? $dr - $cr : $cr - $dr;
    }

    private function getClosingBalance(int $account_id, int $periodid): float
    {
        $stmt = $this->db->prepare("
            SELECT a.normal_balance,
                   COALESCE(pb.opening_debit,0)  + COALESCE(pb.period_debit,0)  AS total_dr,
                   COALESCE(pb.opening_credit,0) + COALESCE(pb.period_credit,0) AS total_cr
            FROM coop_accounts a
            LEFT JOIN coop_period_balances pb ON a.id = pb.account_id AND pb.periodid = ?
            WHERE a.id = ?
        ");
        $stmt->execute([$periodid, $account_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return 0.0;

        return ($row['normal_balance'] === 'debit')
            ? (float)$row['total_dr'] - (float)$row['total_cr']
            : (float)$row['total_cr'] - (float)$row['total_dr'];
    }

    private function getPeriodCredits(int $account_id, int $periodid): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(period_credit, 0) FROM coop_period_balances WHERE account_id = ? AND periodid = ?"
        );
        $stmt->execute([$account_id, $periodid]);
        return (float)($stmt->fetchColumn() ?? 0);
    }

    private function getPeriodDebits(int $account_id, int $periodid): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(period_debit, 0) FROM coop_period_balances WHERE account_id = ? AND periodid = ?"
        );
        $stmt->execute([$account_id, $periodid]);
        return (float)($stmt->fetchColumn() ?? 0);
    }
}
