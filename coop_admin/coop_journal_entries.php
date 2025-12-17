<?php

require_once('Connections/coop.php');
require_once('libs/services/AccountingEngine.php');
require_once('includes/header.php');

// Initialize engine
$accountingEngine = new AccountingEngine($coop, $database);

// Get periods for dropdown
$periods = [];
$periodQuery = "SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC";
$periodResult = mysqli_query($coop, $periodQuery);
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

// Filters
$selectedPeriod = isset($_GET['periodid']) ? intval($_GET['periodid']) : 0;
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';
$selectedType = isset($_GET['type']) ? $_GET['type'] : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where = ['1=1'];
$params = [];
$types = '';

if ($selectedPeriod > 0) {
    $where[] = "je.periodid = ?";
    $params[] = $selectedPeriod;
    $types .= 'i';
}

if ($selectedStatus) {
    $where[] = "je.status = ?";
    $params[] = $selectedStatus;
    $types .= 's';
}

if ($selectedType) {
    $where[] = "je.entry_type = ?";
    $params[] = $selectedType;
    $types .= 's';
}

if ($searchQuery) {
    $where[] = "(je.entry_number LIKE ? OR je.description LIKE ?)";
    $searchParam = "%{$searchQuery}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

// Get journal entries
$sql = "SELECT 
            je.*,
            pp.PayrollPeriod
        FROM coop_journal_entries je
        LEFT JOIN tbpayrollperiods pp ON je.periodid = pp.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY je.entry_date DESC, je.id DESC
        LIMIT 100";

$stmt = mysqli_prepare($coop, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$entries = [];
while ($row = mysqli_fetch_assoc($result)) {
    $entries[] = $row;
}
mysqli_stmt_close($stmt);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
    SUM(total_amount) as total_amount
FROM coop_journal_entries" . ($selectedPeriod > 0 ? " WHERE periodid = {$selectedPeriod}" : "");
$statsResult = mysqli_query($coop, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-blue-900">📒 Journal Entries</h1>
                <p class="text-gray-600 mt-1">View and manage accounting transactions</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Total Entries</p>
                <p class="text-3xl font-bold text-blue-900"><?php echo number_format($stats['total']); ?></p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="text-sm text-gray-600">Total Entries</div>
            <div class="text-2xl font-bold text-blue-900"><?php echo number_format($stats['total']); ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="text-sm text-gray-600">Posted</div>
            <div class="text-2xl font-bold text-green-900"><?php echo number_format($stats['posted'] ?? 0); ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <div class="text-sm text-gray-600">Draft</div>
            <div class="text-2xl font-bold text-yellow-900"><?php echo number_format($stats['draft'] ?? 0); ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="text-sm text-gray-600">Total Amount</div>
            <div class="text-2xl font-bold text-purple-900">₦<?php echo number_format($stats['total_amount'] ?? 0, 2); ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Period</label>
                <select name="periodid"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Periods</option>
                    <?php foreach ($periods as $period): ?>
                    <option value="<?php echo $period['id']; ?>"
                        <?php echo ($period['id'] == $selectedPeriod) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($period['PayrollPeriod']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="draft" <?php echo ($selectedStatus == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="posted" <?php echo ($selectedStatus == 'posted') ? 'selected' : ''; ?>>Posted
                    </option>
                    <option value="approved" <?php echo ($selectedStatus == 'approved') ? 'selected' : ''; ?>>Approved
                    </option>
                    <option value="voided" <?php echo ($selectedStatus == 'voided') ? 'selected' : ''; ?>>Voided
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                <select name="type"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="member_transaction"
                        <?php echo ($selectedType == 'member_transaction') ? 'selected' : ''; ?>>Member Transaction
                    </option>
                    <option value="manual" <?php echo ($selectedType == 'manual') ? 'selected' : ''; ?>>Manual</option>
                    <option value="system" <?php echo ($selectedType == 'system') ? 'selected' : ''; ?>>System</option>
                    <option value="closing" <?php echo ($selectedType == 'closing') ? 'selected' : ''; ?>>Closing
                    </option>
                    <option value="adjustment" <?php echo ($selectedType == 'adjustment') ? 'selected' : ''; ?>>
                        Adjustment</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>"
                        placeholder="Entry # or description"
                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        <?php if ($selectedPeriod || $selectedStatus || $selectedType || $searchQuery): ?>
        <div class="mt-3">
            <a href="coop_journal_entries.php" class="text-sm text-blue-600 hover:text-blue-800">
                <i class="fa fa-times-circle"></i> Clear all filters
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Journal Entries List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
            <h2 class="text-xl font-bold">Journal Entries</h2>
            <p class="text-sm text-blue-100">Click on an entry to view details</p>
        </div>

        <?php if (count($entries) > 0): ?>
        <div class="divide-y divide-gray-200">
            <?php foreach ($entries as $entry): ?>
            <div class="p-6 hover:bg-gray-50 cursor-pointer transition duration-150"
                onclick="toggleDetails(<?php echo $entry['id']; ?>)">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span
                                class="text-lg font-bold text-blue-900"><?php echo htmlspecialchars($entry['entry_number']); ?></span>

                            <!-- Status Badge -->
                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        <?php 
                                        switch($entry['status']) {
                                            case 'posted': echo 'bg-green-100 text-green-800'; break;
                                            case 'draft': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'approved': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'voided': echo 'bg-red-100 text-red-800'; break;
                                        }
                                        ?>">
                                <?php echo ucfirst($entry['status']); ?>
                            </span>

                            <!-- Type Badge -->
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                <?php echo str_replace('_', ' ', ucwords($entry['entry_type'], '_')); ?>
                            </span>

                            <?php if ($entry['is_reversed']): ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                Reversed
                            </span>
                            <?php endif; ?>
                        </div>

                        <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($entry['description']); ?></p>

                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span><i
                                    class="fa fa-calendar mr-1"></i><?php echo date('d M Y', strtotime($entry['entry_date'])); ?></span>
                            <span><i
                                    class="fa fa-clock mr-1"></i><?php echo htmlspecialchars($entry['PayrollPeriod']); ?></span>
                            <span><i
                                    class="fa fa-user mr-1"></i>User ID: <?php echo $entry['created_by']; ?></span>
                            <?php if ($entry['source_document']): ?>
                            <span><i
                                    class="fa fa-file mr-1"></i><?php echo htmlspecialchars($entry['source_document']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-900">
                            ₦<?php echo number_format($entry['total_amount'], 2); ?></div>
                        <button class="text-blue-600 hover:text-blue-800 text-sm mt-2">
                            <i class="fa fa-chevron-down mr-1"></i>View Details
                        </button>
                    </div>
                </div>

                <!-- Details Section (Hidden by default) -->
                <div id="details-<?php echo $entry['id']; ?>" class="hidden mt-6 border-t pt-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Journal Entry Lines</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left">Account Code</th>
                                    <th class="px-4 py-2 text-left">Account Name</th>
                                    <th class="px-4 py-2 text-left">Description</th>
                                    <th class="px-4 py-2 text-right">Debit (₦)</th>
                                    <th class="px-4 py-2 text-right">Credit (₦)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="lines-<?php echo $entry['id']; ?>">
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-center text-gray-500">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="p-12 text-center">
            <div class="text-6xl mb-4">📭</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Journal Entries Found</h3>
            <p class="text-gray-600">
                <?php if ($selectedPeriod || $selectedStatus || $selectedType || $searchQuery): ?>
                Try adjusting your filters or <a href="coop_journal_entries.php"
                    class="text-blue-600 hover:underline">clear all filters</a>
                <?php else: ?>
                Journal entries will appear here after you process member contributions.
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="mt-6 text-center text-sm text-gray-600">
        <p>Showing <?php echo count($entries); ?> entries • Generated on <?php echo date('d M Y, h:i A'); ?></p>
    </div>
</div>

<script>
// Toggle entry details
function toggleDetails(entryId) {
    const detailsDiv = document.getElementById('details-' + entryId);
    const linesDiv = document.getElementById('lines-' + entryId);

    if (detailsDiv.classList.contains('hidden')) {
        detailsDiv.classList.remove('hidden');

        // Load entry lines if not already loaded
        if (linesDiv.innerHTML.includes('Loading')) {
            loadEntryLines(entryId);
        }
    } else {
        detailsDiv.classList.add('hidden');
    }
}

// Load journal entry lines via AJAX
function loadEntryLines(entryId) {
    fetch('api/get_journal_entry_lines.php?entry_id=' + entryId)
        .then(response => response.json())
        .then(data => {
            const linesDiv = document.getElementById('lines-' + entryId);

            if (data.success && data.lines.length > 0) {
                let html = '';
                let totalDebit = 0;
                let totalCredit = 0;

                data.lines.forEach(line => {
                    totalDebit += parseFloat(line.debit_amount);
                    totalCredit += parseFloat(line.credit_amount);

                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono">${line.account_code}</td>
                            <td class="px-4 py-3">${line.account_name}</td>
                            <td class="px-4 py-3 text-gray-600">${line.description || '-'}</td>
                            <td class="px-4 py-3 text-right font-mono">
                                ${line.debit_amount > 0 ? parseFloat(line.debit_amount).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                ${line.credit_amount > 0 ? parseFloat(line.credit_amount).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}
                            </td>
                        </tr>
                    `;
                });

                // Add totals row
                html += `
                    <tr class="bg-blue-50 font-bold">
                        <td colspan="3" class="px-4 py-3 text-right">TOTAL:</td>
                        <td class="px-4 py-3 text-right font-mono">${totalDebit.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td class="px-4 py-3 text-right font-mono">${totalCredit.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>
                `;

                linesDiv.innerHTML = html;
            } else {
                linesDiv.innerHTML =
                    '<tr><td colspan="5" class="px-4 py-3 text-center text-red-500">Error loading entry lines</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const linesDiv = document.getElementById('lines-' + entryId);
            linesDiv.innerHTML =
                '<tr><td colspan="5" class="px-4 py-3 text-center text-red-500">Error loading entry lines</td></tr>';
        });
}
</script>

<?php require_once('includes/footer.php'); ?>