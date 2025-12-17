<?php

require_once('Connections/coop.php');
require_once('libs/services/AccountBalanceCalculator.php');
require_once('includes/header.php');

// Initialize calculator
$balanceCalculator = new AccountBalanceCalculator($coop, $database);

// Get periods for dropdown
$periods = [];
$periodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC";
$periodResult = mysqli_query($coop, $periodQuery);
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

// Get selected period
$selectedPeriod = isset($_GET['periodid']) ? intval($_GET['periodid']) : (count($periods) > 0 ? $periods[0]['id'] : 0);
$selectedPeriodName = '';
foreach ($periods as $period) {
    if ($period['id'] == $selectedPeriod) {
        $selectedPeriodName = $period['PayrollPeriod'];
        break;
    }
}

// Get trial balance data
$trialBalance = null;
$accountingEquation = null;
$includeZeroBalances = isset($_GET['include_zero']) ? true : false;

if ($selectedPeriod > 0) {
    $trialBalance = $balanceCalculator->getTrialBalance($selectedPeriod, $includeZeroBalances);
    $accountingEquation = $balanceCalculator->verifyAccountingEquation($selectedPeriod);
}
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-blue-900">📊 Trial Balance</h1>
                <p class="text-gray-600 mt-1">Verify your books are balanced</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">As of</p>
                <p class="text-lg font-semibold text-blue-900"><?php echo date('d M Y'); ?></p>
            </div>
        </div>
    </div>

    <!-- Period Selection & Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Select Period <span class="text-red-500">*</span>
                </label>
                <select name="periodid"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    onchange="this.form.submit()">
                    <option value="">-- Select Period --</option>
                    <?php foreach ($periods as $period): ?>
                    <option value="<?php echo $period['id']; ?>"
                        <?php echo ($period['id'] == $selectedPeriod) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="include_zero" value="1"
                        <?php echo $includeZeroBalances ? 'checked' : ''; ?>
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        onchange="this.form.submit()">
                    <span class="ml-2 text-sm text-gray-700">Include zero balances</span>
                </label>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="window.print()"
                    class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
                <button type="button" onclick="exportToExcel()"
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </button>
            </div>
        </form>
    </div>

    <?php if ($selectedPeriod > 0 && $trialBalance): ?>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Balance Status -->
        <div
            class="bg-white rounded-lg shadow-md p-6 border-l-4 <?php echo $trialBalance['is_balanced'] ? 'border-green-500' : 'border-red-500'; ?>">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Balance Status</p>
                    <p
                        class="text-2xl font-bold <?php echo $trialBalance['is_balanced'] ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $trialBalance['is_balanced'] ? '✓ Balanced' : '✗ Out of Balance'; ?>
                    </p>
                </div>
                <div class="text-4xl">
                    <?php echo $trialBalance['is_balanced'] ? '✅' : '❌'; ?>
                </div>
            </div>
            <?php if (!$trialBalance['is_balanced']): ?>
            <p class="text-xs text-red-600 mt-2">
                Difference: ₦<?php echo number_format(abs($trialBalance['totals']['difference']), 2); ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Total Debits -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Debits</p>
                    <p class="text-2xl font-bold text-blue-900">
                        ₦<?php echo number_format($trialBalance['totals']['debit'], 2); ?>
                    </p>
                </div>
                <div class="text-4xl">📈</div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Assets & Expenses
            </p>
        </div>

        <!-- Total Credits -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Credits</p>
                    <p class="text-2xl font-bold text-purple-900">
                        ₦<?php echo number_format($trialBalance['totals']['credit'], 2); ?>
                    </p>
                </div>
                <div class="text-4xl">📉</div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Liabilities, Equity & Revenue
            </p>
        </div>
    </div>

    <!-- Accounting Equation Verification -->
    <?php if ($accountingEquation): ?>
    <div
        class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg shadow-md p-6 mb-6 border <?php echo $accountingEquation['valid'] ? 'border-green-300' : 'border-red-300'; ?>">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <?php echo $accountingEquation['valid'] ? '✅' : '❌'; ?>
            Accounting Equation: Assets = Liabilities + Equity
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600 mb-1">Assets</p>
                <p class="text-xl font-bold text-blue-900">
                    ₦<?php echo number_format($accountingEquation['assets'], 2); ?></p>
            </div>
            <div class="flex items-center justify-center text-2xl font-bold text-gray-400">=</div>
            <div class="bg-white rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600 mb-1">Liabilities</p>
                <p class="text-xl font-bold text-red-900">
                    ₦<?php echo number_format($accountingEquation['liabilities'], 2); ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600 mb-1">+ Equity</p>
                <p class="text-xl font-bold text-green-900">
                    ₦<?php echo number_format($accountingEquation['equity'], 2); ?></p>
            </div>
        </div>
        <?php if (!$accountingEquation['valid']): ?>
        <div class="mt-4 p-3 bg-red-100 border border-red-300 rounded-lg">
            <p class="text-sm text-red-800">
                ⚠️ Warning: Accounting equation doesn't balance. Difference:
                ₦<?php echo number_format(abs($accountingEquation['difference']), 2); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Trial Balance Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
            <h2 class="text-xl font-bold">Trial Balance Report</h2>
            <p class="text-sm text-blue-100">Period: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full" id="trialBalanceTable">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Code</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Account Name</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Type</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Debit (₦)</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Credit (₦)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                        $current_type = '';
                        foreach ($trialBalance['accounts'] as $account): 
                            // Add section header when type changes
                            if ($current_type != $account['account_type']) {
                                $current_type = $account['account_type'];
                                $type_label = strtoupper($current_type);
                                ?>
                    <tr class="bg-gray-50">
                        <td colspan="5" class="px-6 py-2 text-sm font-bold text-gray-700">
                            <?php echo $type_label; ?>
                        </td>
                    </tr>
                    <?php } ?>

                    <tr class="hover:bg-blue-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($account['account_code']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <?php echo htmlspecialchars($account['account_name']); ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($account['account_type']) {
                                            case 'asset': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'liability': echo 'bg-red-100 text-red-800'; break;
                                            case 'equity': echo 'bg-green-100 text-green-800'; break;
                                            case 'revenue': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'expense': echo 'bg-orange-100 text-orange-800'; break;
                                        }
                                        ?>">
                                <?php echo ucfirst($account['account_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono text-gray-900">
                            <?php if ($account['debit_balance'] > 0): ?>
                            <?php echo number_format($account['debit_balance'], 2); ?>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono text-gray-900">
                            <?php if ($account['credit_balance'] > 0): ?>
                            <?php echo number_format($account['credit_balance'], 2); ?>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-sm font-bold uppercase">
                            Total:
                        </td>
                        <td class="px-6 py-4 text-right text-lg font-bold font-mono">
                            <?php echo number_format($trialBalance['totals']['debit'], 2); ?>
                        </td>
                        <td class="px-6 py-4 text-right text-lg font-bold font-mono">
                            <?php echo number_format($trialBalance['totals']['credit'], 2); ?>
                        </td>
                    </tr>
                    <?php if ($trialBalance['totals']['difference'] != 0): ?>
                    <tr class="bg-red-600">
                        <td colspan="3" class="px-6 py-3 text-right text-sm font-bold">
                            DIFFERENCE:
                        </td>
                        <td colspan="2" class="px-6 py-3 text-right text-lg font-bold font-mono">
                            <?php echo number_format(abs($trialBalance['totals']['difference']), 2); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="mt-6 text-center text-sm text-gray-600">
        <p>Generated on <?php echo date('d M Y, h:i A'); ?> •
            <?php echo count($trialBalance['accounts']); ?> accounts displayed</p>
    </div>

    <?php else: ?>
    <!-- No Data Message -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
        <div class="flex items-center">
            <div class="text-yellow-400 text-3xl mr-4">⚠️</div>
            <div>
                <h3 class="text-lg font-semibold text-yellow-800">No Data Available</h3>
                <p class="text-yellow-700 mt-1">Please select a period to view the trial balance.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function exportToExcel() {
    const table = document.getElementById('trialBalanceTable');
    if (!table) return;

    // Get period name for filename
    const periodSelect = document.querySelector('select[name="periodid"]');
    const periodName = periodSelect.options[periodSelect.selectedIndex].text;

    // Convert table to CSV
    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let row of rows) {
        let cols = [];
        const cells = row.querySelectorAll('td, th');
        for (let cell of cells) {
            let text = cell.innerText.replace(/"/g, '""');
            cols.push('"' + text + '"');
        }
        csv.push(cols.join(','));
    }

    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], {
        type: 'text/csv;charset=utf-8;'
    });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', 'Trial_Balance_' + periodName.replace(/[^a-z0-9]/gi, '_') + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Print styles
const style = document.createElement('style');
style.textContent = `
    @media print {
        .no-print { display: none !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
`;
document.head.appendChild(style);
</script>

<?php require_once('includes/footer.php'); ?>