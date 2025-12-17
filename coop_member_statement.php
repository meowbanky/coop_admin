<?php

require_once('Connections/coop.php');
require_once('libs/services/MemberAccountManager.php');
require_once('includes/header.php');

// Initialize manager
$memberAccountManager = new MemberAccountManager($coop, $database);

// Get periods for dropdown
$periods = [];
$periodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC";
$periodResult = mysqli_query($coop, $periodQuery);
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

// Get members for search
$membersQuery = "SELECT CoopID as memberid, CONCAT(LastName, ', ', FirstName, ' ', IFNULL(MiddleName, '')) as full_name 
                 FROM tblemployees 
                 WHERE Status = 'Active'
                 ORDER BY CoopID";
$membersResult = mysqli_query($coop, $membersQuery);
$members = [];
if ($membersResult) {
    while ($row = mysqli_fetch_assoc($membersResult)) {
        $members[] = $row;
    }
}

// Get parameters
$selectedMember = isset($_GET['memberid']) ? intval($_GET['memberid']) : 0;
$fromPeriod = isset($_GET['from_period']) ? intval($_GET['from_period']) : 0;
$toPeriod = isset($_GET['to_period']) ? intval($_GET['to_period']) : 0;

// Set default periods if not selected
if ($fromPeriod == 0 && count($periods) > 0) {
    $fromPeriod = $periods[min(5, count($periods) - 1)]['id']; // 6 periods back or earliest
}
if ($toPeriod == 0 && count($periods) > 0) {
    $toPeriod = $periods[0]['id']; // Latest period
}

// Generate statement if member selected
$statement = null;
$memberInfo = null;
$summary = null;

if ($selectedMember > 0 && $fromPeriod > 0 && $toPeriod > 0) {
    $statement = $memberAccountManager->generateMemberStatement($selectedMember, $fromPeriod, $toPeriod);
    if ($statement['success']) {
        $memberInfo = $statement['member'];
        $summary = $memberAccountManager->getMemberAccountSummary($selectedMember, $toPeriod);
    }
}
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-blue-900">👤 Member Account Statement</h1>
                <p class="text-gray-600 mt-1">View individual member account history</p>
            </div>
            <?php if ($memberInfo): ?>
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                <i class="fa fa-print mr-1"></i> Print
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Selection Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Select Member <span class="text-red-500">*</span>
                </label>
                <select name="memberid"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                    required>
                    <option value="">-- Select Member --</option>
                    <?php foreach ($members as $member): ?>
                    <option value="<?php echo $member['memberid']; ?>"
                        <?php echo ($member['memberid'] == $selectedMember) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars('#' . $member['memberid'] . ' - ' . $member['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">From Period</label>
                <select name="from_period"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach (array_reverse($periods) as $period): ?>
                    <option value="<?php echo $period['id']; ?>"
                        <?php echo ($period['id'] == $fromPeriod) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">To Period</label>
                <select name="to_period"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($periods as $period): ?>
                    <option value="<?php echo $period['id']; ?>"
                        <?php echo ($period['id'] == $toPeriod) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-3">
                <button type="submit"
                    class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded-lg">
                    <i class="fa fa-search mr-1"></i> Generate Statement
                </button>
            </div>
        </form>
    </div>

    <?php if ($statement && $statement['success'] && $memberInfo): ?>

    <!-- Member Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4">Member Information</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Member ID:</span>
                        <span class="font-semibold">#<?php echo htmlspecialchars($memberInfo['memberid']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($memberInfo['full_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($memberInfo['EmailAddress']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($memberInfo['Phone']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Account Summary -->
            <?php if ($summary): ?>
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4">Current Balance Summary</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between p-2 bg-green-50 rounded">
                        <span class="text-gray-700">Shares:</span>
                        <span
                            class="font-bold text-green-900">₦<?php echo number_format($summary['shares'], 2); ?></span>
                    </div>
                    <div class="flex justify-between p-2 bg-blue-50 rounded">
                        <span class="text-gray-700">Savings:</span>
                        <span
                            class="font-bold text-blue-900">₦<?php echo number_format($summary['savings'], 2); ?></span>
                    </div>
                    <div class="flex justify-between p-2 bg-purple-50 rounded">
                        <span class="text-gray-700">Special Savings:</span>
                        <span
                            class="font-bold text-purple-900">₦<?php echo number_format($summary['special_savings'], 2); ?></span>
                    </div>
                    <div class="flex justify-between p-2 bg-red-50 rounded">
                        <span class="text-gray-700">Loan Balance:</span>
                        <span class="font-bold text-red-900">₦<?php echo number_format($summary['loan'], 2); ?></span>
                    </div>
                    <div
                        class="flex justify-between p-3 bg-gradient-to-r from-blue-100 to-green-100 rounded border-2 border-blue-500">
                        <span class="font-bold text-gray-900">Net Position:</span>
                        <span
                            class="font-bold text-blue-900 text-lg">₦<?php echo number_format($summary['net_position'], 2); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Account Statements by Type -->
    <?php foreach ($statement['statement'] as $account_type => $transactions): ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gradient-to-r 
                    <?php 
                    switch($account_type) {
                        case 'shares': echo 'from-green-600 to-green-700'; break;
                        case 'savings': echo 'from-blue-600 to-blue-700'; break;
                        case 'special_savings': echo 'from-purple-600 to-purple-700'; break;
                        case 'loan': echo 'from-red-600 to-red-700'; break;
                        default: echo 'from-gray-600 to-gray-700';
                    }
                    ?> text-white">
            <h2 class="text-xl font-bold"><?php echo strtoupper(str_replace('_', ' ', $account_type)); ?> ACCOUNT</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Period</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Opening Balance
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Credit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Closing Balance
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($transactions as $tx): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($tx['PayrollPeriod']); ?></td>
                        <td class="px-4 py-3 text-sm text-right font-mono">
                            ₦<?php echo number_format($tx['opening_balance'], 2); ?></td>
                        <td
                            class="px-4 py-3 text-sm text-right font-mono <?php echo $tx['debit_amount'] > 0 ? 'text-red-600 font-semibold' : 'text-gray-400'; ?>">
                            <?php echo $tx['debit_amount'] > 0 ? '₦' . number_format($tx['debit_amount'], 2) : '-'; ?>
                        </td>
                        <td
                            class="px-4 py-3 text-sm text-right font-mono <?php echo $tx['credit_amount'] > 0 ? 'text-green-600 font-semibold' : 'text-gray-400'; ?>">
                            <?php echo $tx['credit_amount'] > 0 ? '₦' . number_format($tx['credit_amount'], 2) : '-'; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-mono font-bold">
                            ₦<?php echo number_format($tx['closing_balance'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-100">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-bold text-gray-900">Final Balance:</td>
                        <td class="px-4 py-3 text-right font-bold text-lg font-mono text-blue-900">
                            ₦<?php echo number_format(end($transactions)['closing_balance'], 2); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php elseif ($selectedMember > 0): ?>
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
        <div class="flex items-center">
            <div class="text-blue-400 text-3xl mr-4">ℹ️</div>
            <div>
                <h3 class="text-lg font-semibold text-blue-800">No Account Activity</h3>
                <p class="text-blue-700 mt-1">This member has no account activity in the selected period range.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
        <div class="flex items-center">
            <div class="text-yellow-400 text-3xl mr-4">⚠️</div>
            <div>
                <h3 class="text-lg font-semibold text-yellow-800">No Member Selected</h3>
                <p class="text-yellow-700 mt-1">Please select a member and period range to view their account statement.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
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