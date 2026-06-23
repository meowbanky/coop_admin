<?php
/**
 * MemberAccountManager - Manage Individual Member Accounts (PDO version)
 *
 * Handles member shares, savings, loans, and reconciliation with control accounts.
 * memberid maps to tblemployees.StaffID (INT).
 *
 * @version 2.0
 */
class MemberAccountManager
{
    private PDO $db;

    private array $CONTROL_ACCOUNTS = [
        'shares'          => 3101,
        'savings'         => 3201,
        'special_savings' => 3202,
        'loan'            => 1110,
    ];

    public function __construct(PDO $db, $database_name = null)
    {
        $this->db = $db;
    }

    /* =================== RECORD TRANSACTION =================== */

    public function recordMemberTransaction(int $memberid, string $account_type, float $amount, int $periodid, string $description = ''): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, opening_balance, debit_amount, credit_amount, closing_balance
                 FROM coop_member_accounts
                 WHERE memberid = ? AND account_type = ? AND periodid = ?"
            );
            $stmt->execute([$memberid, $account_type, $periodid]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($amount >= 0) {
                    $new_credit  = $existing['credit_amount'] + $amount;
                    $new_closing = $existing['opening_balance'] + $new_credit - $existing['debit_amount'];
                    $upd = $this->db->prepare(
                        "UPDATE coop_member_accounts SET credit_amount=?, closing_balance=? WHERE id=?"
                    );
                    $upd->execute([$new_credit, $new_closing, $existing['id']]);
                } else {
                    $debit_amt   = abs($amount);
                    $new_debit   = $existing['debit_amount'] + $debit_amt;
                    $new_closing = $existing['opening_balance'] + $existing['credit_amount'] - $new_debit;
                    $upd = $this->db->prepare(
                        "UPDATE coop_member_accounts SET debit_amount=?, closing_balance=? WHERE id=?"
                    );
                    $upd->execute([$new_debit, $new_closing, $existing['id']]);
                }
            } else {
                $opening_balance = $this->getPreviousPeriodBalance($memberid, $account_type, $periodid);
                $credit_amount   = $amount >= 0 ? $amount    : 0;
                $debit_amount    = $amount <  0 ? abs($amount) : 0;
                $closing_balance = $opening_balance + $credit_amount - $debit_amount;

                $ins = $this->db->prepare(
                    "INSERT INTO coop_member_accounts
                     (memberid, account_type, periodid, opening_balance, debit_amount, credit_amount, closing_balance)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $ins->execute([$memberid, $account_type, $periodid, $opening_balance, $debit_amount, $credit_amount, $closing_balance]);
            }

            return ['success' => true];

        } catch (Exception $e) {
            error_log("MemberAccountManager::recordMemberTransaction - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /* =================== GET BALANCE =================== */

    public function getMemberBalance(int $memberid, string $account_type, int $periodid): float
    {
        $stmt = $this->db->prepare(
            "SELECT closing_balance FROM coop_member_accounts
             WHERE memberid = ? AND account_type = ? AND periodid = ?"
        );
        $stmt->execute([$memberid, $account_type, $periodid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (float)$row['closing_balance']
                    : $this->getPreviousPeriodBalance($memberid, $account_type, $periodid);
    }

    private function getPreviousPeriodBalance(int $memberid, string $account_type, int $current_periodid): float
    {
        $stmt = $this->db->prepare(
            "SELECT closing_balance FROM coop_member_accounts
             WHERE memberid = ? AND account_type = ? AND periodid < ?
             ORDER BY periodid DESC LIMIT 1"
        );
        $stmt->execute([$memberid, $account_type, $current_periodid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['closing_balance'] : 0.0;
    }

    /* =================== GENERATE STATEMENT =================== */

    /**
     * Generate account statement for a member across a range of periods.
     * $memberid = tblemployees.StaffID (int)
     */
    public function generateMemberStatement(int $memberid, int $from_periodid, int $to_periodid): array
    {
        try {
            $member = $this->getMemberDetails($memberid);
            if (!$member) {
                return ['success' => false, 'error' => 'Member not found'];
            }

            $account_types = ['shares', 'savings', 'special_savings', 'loan'];
            $statement     = [];

            foreach ($account_types as $type) {
                $stmt = $this->db->prepare(
                    "SELECT ma.periodid, pp.PayrollPeriod,
                            ma.opening_balance, ma.debit_amount, ma.credit_amount, ma.closing_balance
                     FROM coop_member_accounts ma
                     JOIN tbpayrollperiods pp ON ma.periodid = pp.id
                     WHERE ma.memberid = ? AND ma.account_type = ?
                       AND ma.periodid >= ? AND ma.periodid <= ?
                     ORDER BY ma.periodid"
                );
                $stmt->execute([$memberid, $type, $from_periodid, $to_periodid]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $statement[$type] = $rows;
                }
            }

            return ['success' => true, 'member' => $member, 'statement' => $statement];

        } catch (Exception $e) {
            error_log("MemberAccountManager::generateMemberStatement - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /* =================== ACCOUNT SUMMARY =================== */

    public function getMemberAccountSummary(int $memberid, int $periodid): array
    {
        $types   = ['shares', 'savings', 'special_savings', 'loan'];
        $summary = [];
        foreach ($types as $type) {
            $summary[$type] = $this->getMemberBalance($memberid, $type, $periodid);
        }
        $summary['total_equity'] = $summary['shares'] + $summary['savings'] + $summary['special_savings'];
        $summary['net_position'] = $summary['total_equity'] - $summary['loan'];
        return $summary;
    }

    /* =================== ALL MEMBER BALANCES =================== */

    public function getAllMemberBalances(int $periodid, ?string $account_type = null): array
    {
        $sql = "SELECT ma.memberid,
                       CONCAT(e.LastName, ', ', e.FirstName, ' ', IFNULL(e.MiddleName,'')) as member_name,
                       ma.account_type, ma.opening_balance, ma.debit_amount,
                       ma.credit_amount, ma.closing_balance
                FROM coop_member_accounts ma
                JOIN tblemployees e ON ma.memberid = e.StaffID
                WHERE ma.periodid = ?";

        $params = [$periodid];
        if ($account_type) {
            $sql    .= " AND ma.account_type = ?";
            $params[] = $account_type;
        }
        $sql .= " ORDER BY ma.memberid, ma.account_type";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =================== RECONCILE =================== */

    public function reconcileMemberAccounts(int $periodid): array
    {
        try {
            require_once __DIR__ . '/AccountBalanceCalculator.php';
            $calculator = new AccountBalanceCalculator($this->db);

            $account_types  = ['shares', 'savings', 'special_savings', 'loan'];
            $reconciliation = [];
            $mismatches     = [];

            foreach ($account_types as $type) {
                $stmt = $this->db->prepare(
                    "SELECT SUM(closing_balance) as total FROM coop_member_accounts
                     WHERE account_type = ? AND periodid = ?"
                );
                $stmt->execute([$type, $periodid]);
                $row          = $stmt->fetch(PDO::FETCH_ASSOC);
                $member_total = (float)($row['total'] ?? 0);

                $control_account_id = $this->CONTROL_ACCOUNTS[$type] ?? null;
                if ($control_account_id) {
                    $cb             = $calculator->getAccountBalance($control_account_id, $periodid);
                    $control_total  = $cb['balance'];
                    $difference     = $member_total - $control_total;
                    $matches        = abs($difference) < 0.01;

                    $reconciliation[$type] = [
                        'account_type'  => $type,
                        'member_total'  => $member_total,
                        'control_total' => $control_total,
                        'difference'    => $difference,
                        'matches'       => $matches,
                    ];
                    if (!$matches) {
                        $mismatches[] = $reconciliation[$type];
                    }
                }
            }

            return [
                'reconciliation' => $reconciliation,
                'mismatches'     => $mismatches,
                'all_match'      => empty($mismatches),
            ];

        } catch (Exception $e) {
            error_log("MemberAccountManager::reconcileMemberAccounts - " . $e->getMessage());
            return ['reconciliation' => [], 'mismatches' => [], 'all_match' => false, 'error' => $e->getMessage()];
        }
    }

    /* =================== INIT NEW PERIOD =================== */

    public function initializeNewPeriodBalances(int $new_periodid, int $previous_periodid): array
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO coop_member_accounts
                 (memberid, account_type, periodid, opening_balance, debit_amount, credit_amount, closing_balance)
                 SELECT memberid, account_type, ? AS periodid,
                        closing_balance AS opening_balance,
                        0, 0,
                        closing_balance AS closing_balance
                 FROM coop_member_accounts
                 WHERE periodid = ?"
            );
            $stmt->execute([$new_periodid, $previous_periodid]);

            $rows_affected = $stmt->rowCount();
            $this->db->commit();

            return ['success' => true, 'members_initialized' => $rows_affected];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("MemberAccountManager::initializeNewPeriodBalances - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /* =================== HELPERS =================== */

    private function getMemberDetails(int $memberid): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT StaffID AS memberid,
                    CONCAT(LastName, ', ', FirstName, ' ', IFNULL(MiddleName,'')) AS full_name,
                    EmailAddress,
                    MobileNumber AS Phone
             FROM tblemployees
             WHERE StaffID = ?"
        );
        $stmt->execute([$memberid]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($member && empty($member['Phone'])) {
            $member['Phone'] = '';
        }
        return $member ?: null;
    }
}
