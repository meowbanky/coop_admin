<?php

require_once('Connections/coop.php');
require_once('libs/services/AccountBalanceCalculator.php');
require_once('includes/header.php');

// Get all accounts for dropdown
$accountsQuery = "SELECT id, account_code, account_name, account_type 
                  FROM coop_accounts 
                  WHERE is_active = TRUE 
                  ORDER BY account_code";
$accountsResult = mysqli_query($coop, $accountsQuery);
$accounts = [];
if ($accountsResult) {
    while ($row = mysqli_fetch_assoc($accountsResult)) {
        $accounts[] = $row;
    }
}

// Get periods
$periods = [];
$periodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC";
$periodResult = mysqli_query($coop, $periodQuery);
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

// Get parameters
$selectedAccount = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
$selectedPeriod = isset($_GET['periodid']) ? intval($_GET['periodid']) : 0;

// Get account details and transactions
$accountDetails = null;
$transactions = [];
$runningBalance = 0;

if ($selectedAccount > 0 && $selectedPeriod > 0) {
    // Get account details
    $sql = "SELECT * FROM coop_accounts WHERE id = ?";
    $stmt = mysqli_prepare($coop, $sql);
    mysqli_stmt_bind_param($stmt, "i", $selectedAccount);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $accountDetails = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Get opening balance
    $calculator = new AccountBalanceCalculator($coop, $database);
    $balance = $calculator->getAccountBalance($selectedAccount, $selectedPeriod);
    $runningBalance = floatval($balance['opening_debit'] ?? 0) - floatval($balance['opening_credit'] ?? 0);
    
    // Get all transactions affecting this account
    $sql = "SELECT 
                je.entry_number,
                je.entry_date,
                je.description as entry_description,
                jel.line_number,
                jel.debit_amount,
                jel.credit_amount,
                jel.description as line_description
            FROM coop_journal_entry_lines jel
            JOIN coop_journal_entries je ON jel.journal_entry_id = je.id
            WHERE jel.account_id = ? 
            AND je.periodid = ?
            AND je.status = 'posted'
            ORDER BY je.entry_date ASC, je.id ASC, jel.line_number ASC";
    
    $stmt = mysqli_prepare($coop, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $selectedAccount, $selectedPeriod);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $debit = floatval($row['debit_amount']);
        $credit = floatval($row['credit_amount']);
        
        if ($accountDetails['normal_balance'] == 'debit') {
            $runningBalance += $debit - $credit;
        } else {
            $runningBalance += $credit - $debit;
        }
        
        $row['running_balance'] = $runningBalance;
        $transactions[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold text-blue-900">📖 General Ledger</h1>
        <p class="text-gray-600 mt-1">Detailed account activity and running balance</p>
    </div>

    <!-- Selection Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Select Account <span class="text-red-500">*</span>
                </label>
                <select name="account_id" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                    <option value="">-- Select Account --</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>" <?php echo ($account['id'] == $selectedAccount) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Period <span class="text-red-500">*</span>
                </label>
                <select name="periodid" class="w-full border border-gray-300 rounded-lg px-4 py-2" required>
                    <option value="">-- Select Period --</option>
                    <?php foreach ($periods as $period): ?>
                        <option value="<?php echo $period['id']; ?>" <?php echo ($period['id'] == $selectedPeriod) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    <i class="fa fa-search mr-1"></i> View Ledger
                </button>
            </div>
        </form>
    </div>

    <?php if ($accountDetails && $selectedPeriod > 0): ?>

        <!-- Account Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Account Code</p>
                    <p class="text-lg font-bold text-blue-900"><?php echo htmlspecialchars($accountDetails['account_code']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Account Name</p>
                    <p class="text-lg font-bold"><?php echo htmlspecialchars($accountDetails['account_name']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Normal Balance</p>
                    <p class="text-lg font-bold"><?php echo strtoupper($accountDetails['normal_balance']); ?></p>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                <h2 class="text-xl font-bold">Account Activity</h2>
                <p class="text-sm text-blue-100"><?php echo count($transactions); ?> transactions</p>
            </div>

            <?php if (count($transactions) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Entry #</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Description</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Debit (₦)</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Credit (₦)</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Balance (₦)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($transactions as $tx): ?>
                                <tr class="hover:bg-blue-50">
                                    <td class="px-4 py-3 whitespace-nowrap"><?php echo date('d M Y', strtotime($tx['entry_date'])); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-blue-600">
                                        <a href="coop_journal_entries.php?search=<?php echo urlencode($tx['entry_number']); ?>" class="hover:underline">
                                            <?php echo htmlspecialchars($tx['entry_number']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php echo htmlspecialchars($tx['line_description'] ?: $tx['entry_description']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono <?php echo $tx['debit_amount'] > 0 ? 'text-blue-600 font-semibold' : 'text-gray-400'; ?>">
                                        <?php echo $tx['debit_amount'] > 0 ? number_format($tx['debit_amount'], 2) : '-'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono <?php echo $tx['credit_amount'] > 0 ? 'text-purple-600 font-semibold' : 'text-gray-400'; ?>">
                                        <?php echo $tx['credit_amount'] > 0 ? number_format($tx['credit_amount'], 2) : '-'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-gray-900">
                                        <?php echo number_format($tx['running_balance'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-100">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right font-bold">Final Balance:</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-lg text-blue-900">
                                    <?php echo number_format(end($transactions)['running_balance'], 2); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No Transactions</h3>
                    <p class="text-gray-600">No posted transactions found for this account in the selected period.</p>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
            <p class="text-yellow-800">Please select an account and period to view the general ledger.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer.php'); ?>

