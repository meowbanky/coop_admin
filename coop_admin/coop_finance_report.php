<?php
require_once('Connections/coop.php');
$periodid = intval($_GET['periodid'] ?? 0);
$admin = isset($_GET['admin']) && $_GET['admin'] === '1' || $_GET['admin'] === 'true';
if (!$periodid) { echo '<div class="text-gray-500 p-4">No period selected.</div>'; exit; }

$sql = "SELECT t.id, t.amount, t.type, t.description, t.created_at, c.name as category
        FROM coop_transactions t
        JOIN coop_categories c ON t.category_id = c.id
        WHERE t.periodid = ? AND t.deleted_at IS NULL
        ORDER BY t.type, t.created_at DESC";
$stmt = $coop->prepare($sql);
$stmt->bind_param('i', $periodid);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$income = array_filter($rows, fn($r) => $r['type'] === 'income');
$expend = array_filter($rows, fn($r) => $r['type'] === 'expenditure');
$income_total = array_sum(array_column($income, 'amount'));
$expend_total = array_sum(array_column($expend, 'amount'));

function renderTable($rows, $title, $total, $admin) {
    if (!count($rows)) {
        $icon = $title === 'Income' ? 'fa-arrow-up' : 'fa-arrow-down';
        $color = $title === 'Income' ? 'text-green-500' : 'text-red-500';
        return "<div class='mb-6'>
            <div class='bg-white rounded-lg shadow-lg p-6 text-center'>
                <div class='{$color} mb-4'>
                    <i class='fa {$icon} text-4xl'></i>
                </div>
                <h3 class='text-lg font-semibold text-gray-600 mb-2'>No {$title} Records</h3>
                <p class='text-gray-500'>No {$title} transactions found for this period.</p>
            </div>
        </div>";
    }
    
    $icon = $title === 'Income' ? 'fa-arrow-up' : 'fa-arrow-down';
    $iconColor = $title === 'Income' ? 'text-green-600' : 'text-red-600';
    $headerColor = $title === 'Income' ? 'from-green-600 to-green-700' : 'from-red-600 to-red-700';
    $totalColor = $title === 'Income' ? 'text-green-600' : 'text-red-600';
    $bgColor = $title === 'Income' ? 'from-green-50 to-green-100' : 'from-red-50 to-red-100';
    
    $html = "<div class='mb-6'>
        <div class='bg-white rounded-lg shadow-lg overflow-hidden'>
            <div class='bg-gradient-to-r {$headerColor} px-6 py-4'>
                <h3 class='text-white text-lg font-bold flex items-center gap-2'>
                    <i class='fa {$icon}'></i>
                    {$title}
                </h3>
            </div>
            <div class='overflow-x-auto'>
                <table class='w-full text-sm'>
                    <thead class='bg-gray-50'>
                        <tr class='border-b border-gray-200'>
                            <th class='px-4 py-3 text-left font-semibold text-gray-700'>Date</th>
                            <th class='px-4 py-3 text-left font-semibold text-gray-700'>Category</th>
                            <th class='px-4 py-3 text-left font-semibold text-gray-700'>Description</th>
                            <th class='px-4 py-3 text-right font-semibold text-gray-700'>Amount</th>";
    if ($admin) $html .= "<th class='px-4 py-3 text-center font-semibold text-gray-700'>Actions</th>";
    $html .= "</tr></thead><tbody class='divide-y divide-gray-100'>";
    
    $rowCount = 0;
    foreach ($rows as $r) {
        $rowCount++;
        $rowClass = $rowCount % 2 === 0 ? 'bg-gray-50 hover:bg-gray-100' : 'bg-white hover:bg-gray-50';
        $amountColor = $title === 'Income' ? 'text-green-600' : 'text-red-600';
        
        $html .= "<tr class='{$rowClass} transition-colors duration-150'>
            <td class='px-4 py-3 font-mono text-gray-600'>".htmlspecialchars(date('M j, Y', strtotime($r['created_at'])))."</td>
            <td class='px-4 py-3'>
                <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800'>
                    ".htmlspecialchars($r['category'])."
                </span>
            </td>
            <td class='px-4 py-3 text-gray-800'>".htmlspecialchars($r['description'])."</td>
            <td class='px-4 py-3 text-right font-semibold {$amountColor}'>₦".number_format($r['amount'],2)."</td>";
        if ($admin) {
            $html .= "<td class='px-4 py-3 text-center'>
                <div class='flex gap-2 justify-center'>
                    <button class='editTransBtn bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-lg text-xs font-semibold transition-colors duration-150 flex items-center gap-1' 
                            data-id='".$r['id']."' 
                            data-amount='".$r['amount']."' 
                            data-description='".htmlspecialchars($r['description'], ENT_QUOTES)."'>
                        <i class='fa fa-edit'></i>
                        Edit
                    </button>
                    <button class='deleteTransBtn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-xs font-semibold transition-colors duration-150 flex items-center gap-1' 
                            data-id='".$r['id']."'>
                        <i class='fa fa-trash'></i>
                        Delete
                    </button>
                </div>
            </td>";
        }
        $html .= "</tr>";
    }
    
    $html .= "<tr class='bg-gradient-to-r {$bgColor} border-t-2 border-gray-200'>
        <td colspan='".($admin?4:3)."' class='px-4 py-4 text-right font-bold text-gray-700'>
            <i class='fa fa-calculator mr-2'></i>
            Total ({$rowCount} transactions)
        </td>
        <td class='px-4 py-4 text-right font-bold {$totalColor} text-lg'>₦".number_format($total,2)."</td>";
    if ($admin) $html .= "<td class='px-4 py-4'></td>";
    $html .= "</tr></tbody></table></div></div></div>";
    
    return $html;
}

$html = renderTable($income, 'Income', $income_total, $admin);
$html .= renderTable($expend, 'Expenditure', $expend_total, $admin);

// Net Summary Card
$netAmount = $income_total - $expend_total;
$isPositive = $netAmount >= 0;
$netIcon = $isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
$netColor = $isPositive ? 'text-green-600' : 'text-red-600';
$netBgColor = $isPositive ? 'from-green-600 to-green-700' : 'from-red-600 to-red-700';
$netCardBg = $isPositive ? 'from-green-50 to-green-100 border-green-200' : 'from-red-50 to-red-100 border-red-200';

$html .= "<div class='mt-6'>
    <div class='bg-white rounded-lg shadow-lg overflow-hidden'>
        <div class='bg-gradient-to-r {$netBgColor} px-6 py-4'>
            <h3 class='text-white text-lg font-bold flex items-center gap-2'>
                <i class='fa fa-balance-scale'></i>
                Financial Summary
            </h3>
        </div>
        <div class='bg-gradient-to-r {$netCardBg} p-6'>
            <div class='grid grid-cols-1 md:grid-cols-3 gap-6 text-center'>
                <div>
                    <p class='text-sm text-gray-600 mb-1'>Total Income</p>
                    <p class='text-2xl font-bold text-green-600'>₦".number_format($income_total, 2)."</p>
                </div>
                <div>
                    <p class='text-sm text-gray-600 mb-1'>Total Expenditure</p>
                    <p class='text-2xl font-bold text-red-600'>₦".number_format($expend_total, 2)."</p>
                </div>
                <div>
                    <p class='text-sm text-gray-600 mb-1'>Net Balance</p>
                    <p class='text-3xl font-bold {$netColor} flex items-center justify-center gap-2'>
                        <i class='fa {$netIcon}'></i>
                        ₦".number_format($netAmount, 2)."
                    </p>
                    <p class='text-xs text-gray-500 mt-1'>
                        ".($isPositive ? 'Profit' : 'Loss')."
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>";

echo $html; 