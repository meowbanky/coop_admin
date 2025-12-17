<?php

require_once('Connections/coop.php');
require_once('includes/header.php');

// Get filter parameters
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$filterCategory = isset($_GET['category']) ? $_GET['category'] : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$showInactive = isset($_GET['show_inactive']) ? true : false;

// Build WHERE clause
$where = [];
$params = [];
$types = '';

if ($filterType) {
    $where[] = "a.account_type = ?";
    $params[] = $filterType;
    $types .= 's';
}

if ($filterCategory) {
    $where[] = "a.account_category = ?";
    $params[] = $filterCategory;
    $types .= 's';
}

if ($searchQuery) {
    $where[] = "(a.account_code LIKE ? OR a.account_name LIKE ? OR a.description LIKE ?)";
    $searchParam = "%{$searchQuery}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if (!$showInactive) {
    $where[] = "a.is_active = TRUE";
}

$whereClause = count($where) > 0 ? " WHERE " . implode(' AND ', $where) : "";

// Get accounts
$sql = "SELECT 
            a.*,
            parent.account_name as parent_name,
            (SELECT COUNT(*) FROM coop_accounts c WHERE c.parent_id = a.id) as child_count
        FROM coop_accounts a
        LEFT JOIN coop_accounts parent ON a.parent_id = parent.id
        {$whereClause}
        ORDER BY a.account_code";

$stmt = mysqli_prepare($coop, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$accounts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $accounts[] = $row;
}
mysqli_stmt_close($stmt);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN account_type = 'asset' THEN 1 ELSE 0 END) as assets,
    SUM(CASE WHEN account_type = 'liability' THEN 1 ELSE 0 END) as liabilities,
    SUM(CASE WHEN account_type = 'equity' THEN 1 ELSE 0 END) as equity,
    SUM(CASE WHEN account_type = 'revenue' THEN 1 ELSE 0 END) as revenue,
    SUM(CASE WHEN account_type = 'expense' THEN 1 ELSE 0 END) as expense,
    SUM(CASE WHEN is_control_account = TRUE THEN 1 ELSE 0 END) as control,
    SUM(CASE WHEN is_system_account = TRUE THEN 1 ELSE 0 END) as system_accounts
FROM coop_accounts WHERE is_active = TRUE";
$statsResult = mysqli_query($coop, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-blue-900">📋 Chart of Accounts</h1>
                <p class="text-gray-600 mt-1">Master list of all accounting accounts</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Total Accounts</p>
                <p class="text-3xl font-bold text-blue-900"><?php echo number_format($stats['total']); ?></p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="text-xs text-gray-600 mb-1">Assets</div>
            <div class="text-xl font-bold text-blue-900"><?php echo $stats['assets']; ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <div class="text-xs text-gray-600 mb-1">Liabilities</div>
            <div class="text-xl font-bold text-red-900"><?php echo $stats['liabilities']; ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="text-xs text-gray-600 mb-1">Equity</div>
            <div class="text-xl font-bold text-green-900"><?php echo $stats['equity']; ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="text-xs text-gray-600 mb-1">Revenue</div>
            <div class="text-xl font-bold text-purple-900"><?php echo $stats['revenue']; ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
            <div class="text-xs text-gray-600 mb-1">Expenses</div>
            <div class="text-xl font-bold text-orange-900"><?php echo $stats['expense']; ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Account Type</label>
                <select name="type"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="asset" <?php echo ($filterType == 'asset') ? 'selected' : ''; ?>>Asset</option>
                    <option value="liability" <?php echo ($filterType == 'liability') ? 'selected' : ''; ?>>Liability
                    </option>
                    <option value="equity" <?php echo ($filterType == 'equity') ? 'selected' : ''; ?>>Equity</option>
                    <option value="revenue" <?php echo ($filterType == 'revenue') ? 'selected' : ''; ?>>Revenue</option>
                    <option value="expense" <?php echo ($filterType == 'expense') ? 'selected' : ''; ?>>Expense</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                <select name="category"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <option value="current_asset" <?php echo ($filterCategory == 'current_asset') ? 'selected' : ''; ?>>
                        Current Asset</option>
                    <option value="non_current_asset"
                        <?php echo ($filterCategory == 'non_current_asset') ? 'selected' : ''; ?>>Non-Current Asset
                    </option>
                    <option value="member_equity" <?php echo ($filterCategory == 'member_equity') ? 'selected' : ''; ?>>
                        Member Equity</option>
                    <option value="reserves" <?php echo ($filterCategory == 'reserves') ? 'selected' : ''; ?>>Reserves
                    </option>
                    <option value="operating_revenue"
                        <?php echo ($filterCategory == 'operating_revenue') ? 'selected' : ''; ?>>Operating Revenue
                    </option>
                    <option value="overhead" <?php echo ($filterCategory == 'overhead') ? 'selected' : ''; ?>>Overhead
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>"
                    placeholder="Code, name, or description"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fa fa-search mr-1"></i> Search
                </button>
                <label
                    class="flex items-center cursor-pointer px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="show_inactive" value="1" <?php echo $showInactive ? 'checked' : ''; ?>
                        class="mr-2" onchange="this.form.submit()">
                    <span class="text-sm">Inactive</span>
                </label>
            </div>
        </form>
        <?php if ($filterType || $filterCategory || $searchQuery): ?>
        <div class="mt-3">
            <a href="coop_chart_of_accounts.php" class="text-sm text-blue-600 hover:text-blue-800">
                <i class="fa fa-times-circle"></i> Clear all filters
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Accounts Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
            <h2 class="text-xl font-bold">Account List</h2>
            <p class="text-sm text-blue-100">Showing <?php echo count($accounts); ?> accounts</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Account Name</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Type</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Category</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Balance</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Flags</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($accounts) > 0): ?>
                    <?php 
                        $current_type = '';
                        foreach ($accounts as $account): 
                            // Add section header when type changes
                            if ($current_type != $account['account_type']) {
                                $current_type = $account['account_type'];
                                ?>
                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <td colspan="7" class="px-4 py-2 text-sm font-bold text-gray-700 uppercase">
                            <?php echo $current_type; ?>
                        </td>
                    </tr>
                    <?php } ?>

                    <tr
                        class="hover:bg-blue-50 transition duration-150 <?php echo !$account['is_active'] ? 'opacity-50' : ''; ?>">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span
                                class="font-mono font-bold text-blue-900"><?php echo htmlspecialchars($account['account_code']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">
                                <?php 
                                        // Indent based on hierarchy level
                                        $indent_level = substr_count($account['account_code'], '-');
                                        if ($account['parent_id']) {
                                            echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $indent_level + 1);
                                            echo '└─ ';
                                        }
                                        echo htmlspecialchars($account['account_name']); 
                                        ?>
                            </div>
                            <?php if ($account['description']): ?>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php echo htmlspecialchars($account['description']); ?></div>
                            <?php endif; ?>
                            <?php if ($account['parent_name']): ?>
                            <div class="text-xs text-blue-600 mt-1">
                                <i class="fa fa-level-up-alt"></i>
                                <?php echo htmlspecialchars($account['parent_name']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($account['child_count'] > 0): ?>
                            <div class="text-xs text-green-600 mt-1">
                                <i class="fa fa-sitemap"></i> <?php echo $account['child_count']; ?> sub-accounts
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
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
                        <td class="px-4 py-3 text-center">
                            <?php if ($account['account_category']): ?>
                            <span class="text-xs text-gray-600">
                                <?php echo str_replace('_', ' ', ucwords($account['account_category'], '_')); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                class="px-2 py-1 text-xs font-semibold rounded 
                                        <?php echo ($account['normal_balance'] == 'debit') ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'; ?>">
                                <?php echo strtoupper($account['normal_balance']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                <?php if ($account['is_control_account']): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-indigo-100 text-indigo-800"
                                    title="Control Account">
                                    <i class="fa fa-crown"></i>
                                </span>
                                <?php endif; ?>
                                <?php if ($account['is_system_account']): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800"
                                    title="System Account">
                                    <i class="fa fa-lock"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($account['is_active']): ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Inactive
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="text-6xl mb-4">📭</div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Accounts Found</h3>
                            <p class="text-gray-600">
                                <?php if ($filterType || $filterCategory || $searchQuery): ?>
                                Try adjusting your filters or <a href="coop_chart_of_accounts.php"
                                    class="text-blue-600 hover:underline">clear all filters</a>
                                <?php else: ?>
                                No accounts available in the system.
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Legend -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-sm font-bold text-gray-900 mb-3">Legend</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">
                    <i class="fa fa-crown"></i>
                </span>
                <span class="text-gray-700"><strong>Control Account:</strong> Summarizes sub-accounts</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">
                    <i class="fa fa-lock"></i>
                </span>
                <span class="text-gray-700"><strong>System Account:</strong> Protected, cannot be deleted</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-50 text-blue-700">DR</span>
                <span class="text-gray-700"><strong>Debit Balance:</strong> Assets, Expenses</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded bg-purple-50 text-purple-700">CR</span>
                <span class="text-gray-700"><strong>Credit Balance:</strong> Liabilities, Equity, Revenue</span>
            </div>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="mt-6 text-center text-sm text-gray-600">
        <p>Total Accounts: <?php echo count($accounts); ?> •
            Control Accounts: <?php echo $stats['control']; ?> •
            System Accounts: <?php echo $stats['system_accounts']; ?></p>
    </div>
</div>
<?php require_once('includes/footer.php'); ?>