<?php

/**
 * AccountBalanceCalculator - PDO Version
 *
 * Handles balance calculations, trial balance, and accounting equation verification
 *
 * @version 2.1
 */
class AccountBalanceCalculator
{
    private PDO $conn;

    public function __construct(PDO $conn, $database_name = null)
    {
        $this->conn = $conn;
    }

    /* ===================== ACCOUNT BALANCE ===================== */

    public function getAccountBalance(int $account_id, int $periodid): array
    {
        try {
            $account = $this->getAccount($account_id);
            if (!$account) {
                return [
                    'balance'        => 0,
                    'debit'          => 0,
                    'credit'         => 0,
                    'opening_debit'  => 0,
                    'opening_credit' => 0,
                    'normal_balance' => 'debit',
                    'error'          => 'Account not found'
                ];
            }

            $stmt = $this->conn->prepare("
                SELECT opening_debit, opening_credit, period_debit, period_credit
                FROM coop_period_balances
                WHERE periodid = :periodid AND account_id = :account_id
            ");

            $stmt->execute([
                ':periodid'   => $periodid,
                ':account_id' => $account_id
            ]);

            $balance = $stmt->fetch();

            if (!$balance) {
                return [
                    'balance'        => 0,
                    'debit'          => 0,
                    'credit'         => 0,
                    'opening_debit'  => 0,
                    'opening_credit' => 0,
                    'normal_balance' => $account['normal_balance']
                ];
            }

            $opening_debit  = (float)$balance['opening_debit'];
            $opening_credit = (float)$balance['opening_credit'];
            $total_debit    = $opening_debit  + (float)$balance['period_debit'];
            $total_credit   = $opening_credit + (float)$balance['period_credit'];

            $net_balance = ($account['normal_balance'] === 'debit')
                ? ($total_debit - $total_credit)
                : ($total_credit - $total_debit);

            return [
                'balance'        => $net_balance,
                'debit'          => $total_debit,
                'credit'         => $total_credit,
                'opening_debit'  => $opening_debit,
                'opening_credit' => $opening_credit,
                'normal_balance' => $account['normal_balance']
            ];

        } catch (Throwable $e) {
            error_log($e->getMessage());
            return ['balance' => 0, 'opening_debit' => 0, 'opening_credit' => 0, 'error' => $e->getMessage()];
        }
    }

    /* ===================== TRIAL BALANCE ===================== */

    /**
     * @param int  $periodid
     * @param bool $includeZeroBalances  Include accounts with zero net balance
     * @return array {
     *   accounts: [{account_code, account_name, account_type, debit_balance, credit_balance}],
     *   totals:   {debit, credit, difference},
     *   is_balanced: bool
     * }
     */
    public function getTrialBalance(int $periodid, bool $includeZeroBalances = false): array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*,
                   COALESCE(pb.opening_debit,0)  opening_debit,
                   COALESCE(pb.opening_credit,0) opening_credit,
                   COALESCE(pb.period_debit,0)   period_debit,
                   COALESCE(pb.period_credit,0)  period_credit
            FROM coop_accounts a
            LEFT JOIN coop_period_balances pb
                ON a.id = pb.account_id AND pb.periodid = :periodid
            WHERE a.is_active = 1
            ORDER BY a.account_code
        ");

        $stmt->execute([':periodid' => $periodid]);

        $accounts     = [];
        $total_debit  = 0;
        $total_credit = 0;

        while ($row = $stmt->fetch()) {
            $dr = (float)$row['opening_debit']  + (float)$row['period_debit'];
            $cr = (float)$row['opening_credit'] + (float)$row['period_credit'];

            $debit_balance  = 0;
            $credit_balance = 0;

            if ($row['normal_balance'] === 'debit') {
                $net = $dr - $cr;
                $net >= 0 ? $debit_balance = $net : $credit_balance = abs($net);
            } else {
                $net = $cr - $dr;
                $net >= 0 ? $credit_balance = $net : $debit_balance = abs($net);
            }

            if (!$includeZeroBalances && $debit_balance == 0 && $credit_balance == 0) {
                continue;
            }

            $accounts[] = [
                'account_code'   => $row['account_code'],
                'account_name'   => $row['account_name'],
                'account_type'   => $row['account_type'],
                'debit_balance'  => $debit_balance,
                'credit_balance' => $credit_balance,
            ];

            $total_debit  += $debit_balance;
            $total_credit += $credit_balance;
        }

        $difference = $total_debit - $total_credit;

        return [
            'accounts'    => $accounts,
            'totals'      => [
                'debit'      => $total_debit,
                'credit'     => $total_credit,
                'difference' => $difference,
            ],
            'is_balanced' => abs($difference) < 0.01,
        ];
    }

    /* ===================== ACCOUNTING EQUATION ===================== */

    public function verifyAccountingEquation(int $periodid): array
    {
        $assets      = $this->getTotalByType($periodid, 'asset');
        $liabilities = $this->getTotalByType($periodid, 'liability');
        $equity      = $this->getTotalByType($periodid, 'equity');

        $difference = $assets - ($liabilities + $equity);

        return [
            'valid'       => abs($difference) < 0.01,
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'equity'      => $equity,
            'difference'  => $difference
        ];
    }

    /* ===================== TOTAL BY TYPE ===================== */

    public function getTotalByType(int $periodid, string $type): float
    {
        $stmt = $this->conn->prepare("
            SELECT a.normal_balance,
                   SUM(COALESCE(pb.opening_debit,0)  + COALESCE(pb.period_debit,0))  debit,
                   SUM(COALESCE(pb.opening_credit,0) + COALESCE(pb.period_credit,0)) credit
            FROM coop_accounts a
            LEFT JOIN coop_period_balances pb
                ON a.id = pb.account_id AND pb.periodid = :periodid
            WHERE a.account_type = :type
            GROUP BY a.normal_balance
        ");

        $stmt->execute([
            ':periodid' => $periodid,
            ':type'     => $type
        ]);

        $total = 0;
        while ($row = $stmt->fetch()) {
            $total += ($row['normal_balance'] === 'debit')
                ? $row['debit'] - $row['credit']
                : $row['credit'] - $row['debit'];
        }

        return (float)$total;
    }

    /* ===================== HELPERS ===================== */

    private function getAccount(int $account_id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM coop_accounts WHERE id = :id");
        $stmt->execute([':id' => $account_id]);
        return $stmt->fetch() ?: null;
    }
}
