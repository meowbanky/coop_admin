<?php

require_once('Connections/coop.php');
require_once('libs/reports/IncomeExpenditureStatement.php');
require_once('libs/reports/BalanceSheet.php');
require_once('libs/reports/CashflowStatement.php');
require_once('includes/header.php');

// Get all periods
$periods = [];
$periodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC";
$periodResult = mysqli_query($coop, $periodQuery);
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

// Get selected periods (up to 5)
$selectedPeriods = [];
for ($i = 1; $i <= 5; $i++) {
    if (isset($_GET["period{$i}"]) && $_GET["period{$i}"] > 0) {
        $selectedPeriods[] = intval($_GET["period{$i}"]);
    }
}

// Default to last 3 periods if none selected
if (empty($selectedPeriods) && count($periods) >= 3) {
    $selectedPeriods = array_slice(array_column($periods, 'id'), 0, 3);
}

$statementType = isset($_GET['statement']) ? $_GET['statement'] : 'income';

// Generate comparative reports
$comparativeData = null;
if (!empty($selectedPeriods)) {
    $incomeGenerator = new IncomeExpenditureStatement($coop, $database);
    $balanceGenerator = new BalanceSheet($coop, $database);
    $cashflowGenerator = new CashflowStatement($coop, $database);
    
    if ($statementType == 'income') {
        $comparativeData = $incomeGenerator->generateStatement($selectedPeriods[0], array_slice($selectedPeriods, 1));
    } elseif ($statementType == 'balance') {
        $comparativeData = $balanceGenerator->generateStatement($selectedPeriods[0], array_slice($selectedPeriods, 1));
    } elseif ($statementType == 'cashflow') {
        $comparativeData = $cashflowGenerator->generateStatement($selectedPeriods[0], array_slice($selectedPeriods, 1));
    }
}

// Get period names
$periodNames = [];
foreach ($selectedPeriods as $pid) {
    foreach ($periods as $p) {
        if ($p['id'] == $pid) {
            $periodNames[$pid] = $p['PayrollPeriod'];
            break;
        }
    }
}
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-blue-900">📊 Comparative Financial Reports</h1>
                <p class="text-gray-600 mt-1">Multi-period comparison and trend analysis</p>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                    <i class="fa fa-print mr-1"></i> Print
                </button>
                <button onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    <i class="fa fa-file-excel mr-1"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- Period Selection -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET">
            <div class="grid grid-cols-1 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Statement Type</label>
                    <select name="statement" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="income" <?php echo ($statementType == 'income') ? 'selected' : ''; ?>>Income & Expenditure</option>
                        <option value="balance" <?php echo ($statementType == 'balance') ? 'selected' : ''; ?>>Balance Sheet</option>
                        <option value="cashflow" <?php echo ($statementType == 'cashflow') ? 'selected' : ''; ?>>Cashflow Statement</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Period <?php echo $i; ?></label>
                            <select name="period<?php echo $i; ?>" class="w-full border rounded px-2 py-1 text-sm">
                                <option value="">None</option>
                                <?php foreach ($periods as $period): ?>
                                    <option value="<?php echo $period['id']; ?>" 
                                            <?php echo (isset($selectedPeriods[$i-1]) && $selectedPeriods[$i-1] == $period['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                <i class="fa fa-chart-line mr-1"></i> Generate Comparative Report
            </button>
        </form>
    </div>

    <!-- Comparative Report -->
    <?php if ($comparativeData && $comparativeData['success']): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-700 text-white">
                <h2 class="text-2xl font-bold">
                    <?php 
                    echo $statementType == 'income' ? 'Income & Expenditure - Comparative' : 
                        ($statementType == 'balance' ? 'Balance Sheet - Comparative' : 'Cashflow - Comparative');
                    ?>
                </h2>
                <p class="text-sm text-blue-100">Multi-period comparison</p>
            </div>

            <div class="overflow-x-auto">
                <?php if ($statementType == 'income'): ?>
                    <!-- INCOME STATEMENT COMPARISON -->
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Item</th>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700"><?php echo htmlspecialchars($periodNames[$pid]); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- REVENUE -->
                            <tr class="bg-purple-50">
                                <td colspan="<?php echo count($selectedPeriods) + 1; ?>" class="px-4 py-2 font-bold text-gray-900">REVENUE</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Entrance Fee</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['revenue']['entrance_fee'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Interest Charges</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['revenue']['interest_charges'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Other Income</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['revenue']['other_income'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr class="bg-purple-100 font-bold">
                                <td class="px-4 py-2">TOTAL REVENUE</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['revenue']['total_revenue'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            
                            <!-- EXPENSES -->
                            <tr class="bg-orange-50">
                                <td colspan="<?php echo count($selectedPeriods) + 1; ?>" class="px-4 py-2 font-bold text-gray-900">EXPENSES</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Cost of Sales</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['cost_of_sales'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Total Overhead</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['overhead']['total_expenses'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            
                            <!-- SURPLUS -->
                            <tr class="bg-green-100 font-bold text-green-900 text-lg">
                                <td class="px-4 py-3">SURPLUS (DEFICIT)</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-3 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['surplus'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>

                <?php elseif ($statementType == 'balance'): ?>
                    <!-- BALANCE SHEET COMPARISON -->
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Item</th>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700"><?php echo htmlspecialchars($periodNames[$pid]); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr class="bg-blue-50">
                                <td colspan="<?php echo count($selectedPeriods) + 1; ?>" class="px-4 py-2 font-bold">ASSETS</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Non-Current Assets</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['total_non_current_assets'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Current Assets</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['total_current_assets'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr class="bg-blue-100 font-bold">
                                <td class="px-4 py-2">TOTAL ASSETS</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['total_current_assets'] + $comparativeData['statement'][$pid]['total_non_current_assets'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            
                            <tr class="bg-green-50">
                                <td colspan="<?php echo count($selectedPeriods) + 1; ?>" class="px-4 py-2 font-bold">EQUITY</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Members Fund</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['total_members_fund'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Reserves</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['total_reserves'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 pl-8">Retained Earnings</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['retained_earnings'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr class="bg-green-100 font-bold">
                                <td class="px-4 py-2">TOTAL EQUITY</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['total_equity'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>

                <?php elseif ($statementType == 'cashflow'): ?>
                    <!-- CASHFLOW COMPARISON -->
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Activity</th>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700"><?php echo htmlspecialchars($periodNames[$pid]); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="px-4 py-2 font-semibold">Operating Activities</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['operating']['net_cashflow_operating'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-semibold">Investing Activities</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['investing']['net_cashflow_investing'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-semibold">Financing Activities</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-2 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['financing']['net_cashflow_financing'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr class="bg-teal-100 font-bold">
                                <td class="px-4 py-3">NET CASHFLOW</td>
                                <?php foreach ($selectedPeriods as $pid): ?>
                                    <td class="px-4 py-3 text-right font-mono">₦<?php echo number_format($comparativeData['statement'][$pid]['net_cashflow'], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
            <p class="text-yellow-800">Please select at least 2 periods for comparison.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function exportToExcel() {
    const table = document.querySelector('table');
    if (!table) return;
    
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
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'Comparative_Report_' + new Date().getTime() + '.csv';
    link.click();
}
</script>

<?php require_once('includes/footer.php'); ?>

