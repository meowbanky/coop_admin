<?php

require_once('Connections/coop.php');
require_once('libs/reports/IncomeExpenditureStatement.php');
require_once('libs/reports/BalanceSheet.php');
require_once('libs/reports/CashflowStatement.php');
require_once('includes/header.php');

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

// Get selected statement type
$statementType = isset($_GET['statement']) ? $_GET['statement'] : 'income';

// Generate statements
$incomeStatement = null;
$balanceSheet = null;
$cashflowStatement = null;

if ($selectedPeriod > 0) {
    $incomeGenerator = new IncomeExpenditureStatement($coop, $database);
    $balanceGenerator = new BalanceSheet($coop, $database);
    $cashflowGenerator = new CashflowStatement($coop, $database);
    
    $incomeStatement = $incomeGenerator->generateStatement($selectedPeriod);
    $balanceSheet = $balanceGenerator->generateStatement($selectedPeriod);
    $cashflowStatement = $cashflowGenerator->generateStatement($selectedPeriod);
}
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-blue-900">📊 Financial Statements</h1>
                <p class="text-gray-600 mt-1">Professional financial reporting</p>
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

    <!-- Period Selection & Statement Type -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Select Period <span class="text-red-500">*</span>
                </label>
                <select name="periodid" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="">-- Select Period --</option>
                    <?php foreach ($periods as $period): ?>
                        <option value="<?php echo $period['id']; ?>" <?php echo ($period['id'] == $selectedPeriod) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Statement Type
                </label>
                <select name="statement" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="income" <?php echo ($statementType == 'income') ? 'selected' : ''; ?>>Income & Expenditure</option>
                    <option value="balance" <?php echo ($statementType == 'balance') ? 'selected' : ''; ?>>Balance Sheet</option>
                    <option value="cashflow" <?php echo ($statementType == 'cashflow') ? 'selected' : ''; ?>>Cashflow Statement</option>
                    <option value="all" <?php echo ($statementType == 'all') ? 'selected' : ''; ?>>All Statements</option>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selectedPeriod > 0): ?>

        <!-- INCOME & EXPENDITURE STATEMENT -->
        <?php if (($statementType == 'income' || $statementType == 'both') && $incomeStatement && $incomeStatement['success']): ?>
            <?php $data = $incomeStatement['statement'][$selectedPeriod]; ?>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6" id="incomeStatement">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-purple-700 text-white">
                    <h2 class="text-2xl font-bold">Income & Expenditure Statement</h2>
                    <p class="text-sm text-purple-100">For the Period: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
                </div>

                <div class="p-6">
                    <!-- REVENUE -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-purple-200">REVENUE</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Entrance Fee</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['revenue']['entrance_fee'], 2); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Interest Charges</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['revenue']['interest_charges'], 2); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Other Incomes</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['revenue']['other_income'], 2); ?></td>
                            </tr>
                            <tr class="bg-purple-50 font-bold">
                                <td class="py-2 px-4">TOTAL REVENUE</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['revenue']['total_revenue'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- COST OF SALES -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-purple-200">COST OF SALES</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Cost of Sales</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['cost_of_sales'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- GROSS PROFIT -->
                    <div class="mb-6">
                        <table class="w-full text-sm">
                            <tr class="bg-green-50 font-bold text-green-900">
                                <td class="py-3 px-4 text-lg">GROSS TRADING PROFIT (LOSS)</td>
                                <td class="py-3 px-4 text-right font-mono text-lg">₦<?php echo number_format($data['gross_profit'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- OVERHEAD EXPENSES -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-purple-200">OVERHEAD</h3>
                        <table class="w-full text-sm">
                            <?php foreach ($data['overhead'] as $key => $amount): ?>
                                <?php if ($key != 'total_expenses'): ?>
                                    <tr class="border-b border-gray-200">
                                        <td class="py-2 px-4"><?php echo str_replace('_', ' ', ucwords($key, '_')); ?></td>
                                        <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($amount, 2); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <tr class="bg-orange-50 font-bold">
                                <td class="py-2 px-4">TOTAL EXPENSES</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['overhead']['total_expenses'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- SURPLUS -->
                    <div class="mb-6">
                        <table class="w-full text-sm">
                            <tr class="bg-gradient-to-r from-blue-50 to-purple-50 font-bold text-blue-900">
                                <td class="py-3 px-4 text-lg">SURPLUS (DEFICIT) FOR THE YEAR</td>
                                <td class="py-3 px-4 text-right font-mono text-lg">₦<?php echo number_format($data['surplus'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- APPROPRIATION -->
                    <?php if ($data['appropriation']['total_appropriation'] > 0): ?>
                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-purple-200">APPROPRIATION OF SURPLUS FUND</h3>
                            <table class="w-full text-sm">
                                <?php foreach ($data['appropriation'] as $key => $amount): ?>
                                    <?php if ($key != 'total_appropriation' && $amount > 0): ?>
                                        <tr class="border-b border-gray-200">
                                            <td class="py-2 px-4"><?php echo str_replace('_', ' ', ucwords($key, '_')); ?></td>
                                            <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($amount, 2); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <tr class="bg-purple-50 font-bold">
                                    <td class="py-2 px-4">TOTAL APPROPRIATION</td>
                                    <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['appropriation']['total_appropriation'], 2); ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- NET PROFIT B/D -->
                    <div>
                        <table class="w-full text-sm">
                            <tr class="bg-gradient-to-r from-green-50 to-blue-50 font-bold text-green-900 border-2 border-green-500">
                                <td class="py-3 px-4 text-lg">NET PROFIT B/D</td>
                                <td class="py-3 px-4 text-right font-mono text-lg">₦<?php echo number_format($data['net_profit_bd'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- BALANCE SHEET -->
        <?php if (($statementType == 'balance' || $statementType == 'both') && $balanceSheet && $balanceSheet['success']): ?>
            <?php $data = $balanceSheet['statement'][$selectedPeriod]; ?>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden" id="balanceSheet">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                    <h2 class="text-2xl font-bold">Statement of Financial Position (Balance Sheet)</h2>
                    <p class="text-sm text-blue-100">As at: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- LEFT COLUMN: ASSETS -->
                        <div>
                            <!-- NON-CURRENT ASSETS -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-blue-200">NON-CURRENT ASSETS</h3>
                                <table class="w-full text-sm">
                                    <?php foreach ($data['non_current_assets'] as $key => $value): ?>
                                        <?php if ($value != 0): ?>
                                            <tr class="border-b border-gray-200">
                                                <td class="py-2 px-2"><?php echo str_replace('_', ' ', ucwords($key, '_')); ?></td>
                                                <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($value, 2); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <tr class="bg-blue-50 font-bold">
                                        <td class="py-2 px-2">TOTAL NON-CURRENT ASSETS</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['total_non_current_assets'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- CURRENT ASSETS -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-blue-200">CURRENT ASSETS</h3>
                                <table class="w-full text-sm">
                                    <tr class="border-b border-gray-200">
                                        <td class="py-2 px-2">Cash on Hand</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['current_assets']['cash'], 2); ?></td>
                                    </tr>
                                    <tr class="border-b border-gray-200">
                                        <td class="py-2 px-2">Bank</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['current_assets']['total_bank'], 2); ?></td>
                                    </tr>
                                    <tr class="border-b border-gray-200">
                                        <td class="py-2 px-2">Member Loans</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['current_assets']['member_loans'], 2); ?></td>
                                    </tr>
                                    <tr class="border-b border-gray-200">
                                        <td class="py-2 px-2">Account Receivables</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['current_assets']['receivables'], 2); ?></td>
                                    </tr>
                                    <tr class="bg-blue-50 font-bold">
                                        <td class="py-2 px-2">TOTAL CURRENT ASSETS</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['total_current_assets'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- TOTAL ASSETS -->
                            <div class="mb-6">
                                <table class="w-full text-sm">
                                    <tr class="bg-gradient-to-r from-blue-100 to-blue-50 font-bold text-blue-900 border-2 border-blue-500">
                                        <td class="py-3 px-2 text-lg">TOTAL ASSETS</td>
                                        <td class="py-3 px-2 text-right font-mono text-lg">₦<?php echo number_format($data['total_current_assets'] + $data['total_non_current_assets'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: LIABILITIES & EQUITY -->
                        <div>
                            <!-- CURRENT LIABILITIES -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-red-200">CURRENT LIABILITIES</h3>
                                <table class="w-full text-sm">
                                    <?php foreach ($data['current_liabilities'] as $key => $value): ?>
                                        <?php if ($value != 0): ?>
                                            <tr class="border-b border-gray-200">
                                                <td class="py-2 px-2"><?php echo str_replace('_', ' ', ucwords($key, '_')); ?></td>
                                                <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($value, 2); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <tr class="bg-red-50 font-bold">
                                        <td class="py-2 px-2">TOTAL CURRENT LIABILITIES</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['total_current_liabilities'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- NET CURRENT ASSETS -->
                            <div class="mb-6">
                                <table class="w-full text-sm">
                                    <tr class="bg-blue-50 font-bold">
                                        <td class="py-2 px-2">NET CURRENT ASSETS</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['net_current_assets'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- NET ASSETS -->
                            <div class="mb-6">
                                <table class="w-full text-sm">
                                    <tr class="bg-gradient-to-r from-green-100 to-green-50 font-bold text-green-900 border-2 border-green-500">
                                        <td class="py-3 px-2 text-lg">NET ASSETS</td>
                                        <td class="py-3 px-2 text-right font-mono text-lg">₦<?php echo number_format($data['net_assets'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- FINANCED BY: EQUITY -->
                            <div class="mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-green-200">FINANCED BY</h3>
                                
                                <!-- Members Fund -->
                                <div class="mb-4">
                                    <p class="font-semibold text-gray-700 mb-2">Members Fund:</p>
                                    <table class="w-full text-sm pl-4">
                                        <tr class="border-b border-gray-200">
                                            <td class="py-2 px-2 pl-4">Ordinary Shares</td>
                                            <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['members_fund']['shares'], 2); ?></td>
                                        </tr>
                                        <tr class="border-b border-gray-200">
                                            <td class="py-2 px-2 pl-4">Ordinary Savings</td>
                                            <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['members_fund']['ordinary_savings'], 2); ?></td>
                                        </tr>
                                        <tr class="border-b border-gray-200">
                                            <td class="py-2 px-2 pl-4">Special Savings</td>
                                            <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['members_fund']['special_savings'], 2); ?></td>
                                        </tr>
                                        <tr class="bg-green-50 font-bold">
                                            <td class="py-2 px-2">Total Members Fund</td>
                                            <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['total_members_fund'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Revenue Reserves -->
                                <div class="mb-4">
                                    <p class="font-semibold text-gray-700 mb-2">Revenue Reserve:</p>
                                    <table class="w-full text-sm pl-4">
                                        <?php foreach ($data['reserves'] as $key => $value): ?>
                                            <?php if ($value != 0): ?>
                                                <tr class="border-b border-gray-200">
                                                    <td class="py-2 px-2 pl-4"><?php echo str_replace('_', ' ', ucwords($key, '_')); ?></td>
                                                    <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($value, 2); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <tr class="bg-green-50 font-bold">
                                            <td class="py-2 px-2">Total Reserves</td>
                                            <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['total_reserves'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Retained Earnings -->
                                <table class="w-full text-sm">
                                    <tr class="border-b border-gray-200">
                                        <td class="py-2 px-2">Retained Profit (Loss)</td>
                                        <td class="py-2 px-2 text-right font-mono">₦<?php echo number_format($data['retained_earnings'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- TOTAL EQUITY -->
                            <div>
                                <table class="w-full text-sm">
                                    <tr class="bg-gradient-to-r from-green-100 to-green-50 font-bold text-green-900 border-2 border-green-500">
                                        <td class="py-3 px-2 text-lg">TOTAL EQUITY</td>
                                        <td class="py-3 px-2 text-right font-mono text-lg">₦<?php echo number_format($data['total_equity'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- ACCURACY CHECK -->
                            <?php if (!$data['is_balanced']): ?>
                                <div class="mt-4 p-4 bg-red-100 border-2 border-red-500 rounded-lg">
                                    <p class="text-red-800 font-bold">⚠️ BALANCE CHECK FAILED</p>
                                    <p class="text-sm text-red-700">Difference: ₦<?php echo number_format(abs($data['difference']), 2); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 p-4 bg-green-100 border-2 border-green-500 rounded-lg">
                                    <p class="text-green-800 font-bold">✓ BALANCE SHEET VERIFIED</p>
                                    <p class="text-sm text-green-700">Assets = Liabilities + Equity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- CASHFLOW STATEMENT -->
        <?php if (($statementType == 'cashflow' || $statementType == 'all') && $cashflowStatement && $cashflowStatement['success']): ?>
            <?php $data = $cashflowStatement['statement'][$selectedPeriod]; ?>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6" id="cashflowStatement">
                <div class="px-6 py-4 bg-gradient-to-r from-teal-600 to-teal-700 text-white">
                    <h2 class="text-2xl font-bold">Cashflow Statement</h2>
                    <p class="text-sm text-teal-100">For the Period: <?php echo htmlspecialchars($selectedPeriodName); ?></p>
                </div>

                <div class="p-6">
                    <!-- OPERATING ACTIVITIES -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-teal-200">NET CASHFLOW FROM OPERATING ACTIVITIES</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Net Profit</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['operating']['net_profit'], 2); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4 pl-8">Add: Depreciation</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['operating']['depreciation'], 2); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4 pl-8">Add: Accrued Income</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['operating']['accrued_income'], 2); ?></td>
                            </tr>
                            <tr class="bg-gray-50 font-semibold">
                                <td class="py-2 px-4 pl-4" colspan="2">Increase/(Decrease) in Working Capital:</td>
                            </tr>
                            <?php foreach ($data['operating']['working_capital'] as $key => $value): ?>
                                <?php if ($value != 0): ?>
                                    <tr class="border-b border-gray-200">
                                        <td class="py-2 px-4 pl-12"><?php echo str_replace('_', ' ', ucwords($key, '_')); ?></td>
                                        <td class="py-2 px-4 text-right font-mono"><?php echo ($value >= 0 ? '₦' : '-₦') . number_format(abs($value), 2); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <tr class="bg-teal-50 font-bold text-teal-900">
                                <td class="py-3 px-4">NET CASHFLOW FROM OPERATING ACTIVITIES</td>
                                <td class="py-3 px-4 text-right font-mono">₦<?php echo number_format($data['operating']['net_cashflow_operating'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- INVESTING ACTIVITIES -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-teal-200">NET CASHFLOW FROM INVESTING ACTIVITIES</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Fixed Asset Purchases</td>
                                <td class="py-2 px-4 text-right font-mono">-₦<?php echo number_format($data['investing']['fixed_asset_purchases'], 2); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Fixed Asset Proceeds</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['investing']['fixed_asset_proceeds'], 2); ?></td>
                            </tr>
                            <tr class="bg-teal-50 font-bold text-teal-900">
                                <td class="py-3 px-4">NET CASHFLOW FROM INVESTING ACTIVITIES</td>
                                <td class="py-3 px-4 text-right font-mono">₦<?php echo number_format($data['investing']['net_cashflow_investing'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- FINANCING ACTIVITIES -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-teal-200">NET CASHFLOW FROM FINANCING ACTIVITIES</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Members Fund</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['financing']['members_fund_change'], 2); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Borrowed Loans</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['financing']['borrowed_loans_change'], 2); ?></td>
                            </tr>
                            <tr class="bg-teal-50 font-bold text-teal-900">
                                <td class="py-3 px-4">NET CASHFLOW FROM FINANCING ACTIVITIES</td>
                                <td class="py-3 px-4 text-right font-mono">₦<?php echo number_format($data['financing']['net_cashflow_financing'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- SUMMARY CASHFLOW -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3 pb-2 border-b-2 border-teal-200">SUMMARY CASHFLOW</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Net Cashflow for the Year</td>
                                <td class="py-2 px-4 text-right font-mono font-bold">₦<?php echo number_format($data['net_cashflow'], 2); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">Cash Balance B/D (Beginning)</td>
                                <td class="py-2 px-4 text-right font-mono">₦<?php echo number_format($data['cash_beginning'], 2); ?></td>
                            </tr>
                            <tr class="bg-gradient-to-r from-teal-100 to-teal-50 font-bold text-teal-900 border-2 border-teal-500">
                                <td class="py-3 px-4 text-lg">Cash Balance C/F (Ending)</td>
                                <td class="py-3 px-4 text-right font-mono text-lg">₦<?php echo number_format($data['cash_ending'], 2); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- VERIFICATION -->
                    <?php if ($data['verification']): ?>
                        <div class="p-4 bg-green-100 border-2 border-green-500 rounded-lg">
                            <p class="text-green-800 font-bold">✓ CASHFLOW VERIFIED</p>
                            <p class="text-sm text-green-700">Beginning + Net Cashflow = Ending Balance</p>
                        </div>
                    <?php else: ?>
                        <div class="p-4 bg-red-100 border-2 border-red-500 rounded-lg">
                            <p class="text-red-800 font-bold">⚠️ CASHFLOW MISMATCH</p>
                            <p class="text-sm text-red-700">Reconciliation needed</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
            <div class="flex items-center">
                <div class="text-yellow-400 text-3xl mr-4">⚠️</div>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800">No Period Selected</h3>
                    <p class="text-yellow-700 mt-1">Please select a period to view financial statements.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function exportToExcel() {
    const periodid = <?php echo $selectedPeriod; ?>;
    const statement = '<?php echo $statementType; ?>';
    
    if (!periodid) {
        alert('Please select a period first');
        return;
    }
    
    let type = statement;
    if (statement == 'all') {
        // Ask which one to export
        type = prompt('Export which statement?\n\nincome = Income & Expenditure\nbalance = Balance Sheet\ncashflow = Cashflow Statement', 'income');
    }
    
    if (type) {
        window.open('api/export_financial_statements.php?periodid=' + periodid + '&type=' + type, '_blank');
    }
}

// Print styles
const style = document.createElement('style');
style.textContent = `
    @media print {
        .no-print { display: none !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .container { max-width: 100%; }
    }
`;
document.head.appendChild(style);
</script>

<?php require_once('includes/footer.php'); ?>

