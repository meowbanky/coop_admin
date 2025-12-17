<?php
require_once('Connections/coop.php');
$periodid = intval($_GET['periodid'] ?? 0);
$type = $_GET['type'] ?? 'excel';
if (!$periodid) die('No period selected.');

$sql = "SELECT t.amount, t.type, t.description, t.created_at, c.name as category
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
$net = $income_total - $expend_total;

if ($type === 'excel') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="coop_finance_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Type','Date','Category','Description','Amount']);
    foreach ($rows as $r) {
        fputcsv($out, [ucfirst($r['type']), date('Y-m-d', strtotime($r['created_at'])), $r['category'], $r['description'], $r['amount']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Income Total','','','','', $income_total]);
    fputcsv($out, ['Expenditure Total','','','','', $expend_total]);
    fputcsv($out, ['Net','','','','', $net]);
    fclose($out);
    exit;
}

// PDF or HTML for print
$html = '<h2 style="text-align:center;">Cooperative Society Income & Expenditure Report</h2>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:13px;">';
$html .= '<thead><tr><th>Type</th><th>Date</th><th>Category</th><th>Description</th><th>Amount</th></tr></thead><tbody>';
foreach ($rows as $r) {
    $html .= '<tr>';
    $html .= '<td>'.ucfirst($r['type']).'</td>';
    $html .= '<td>'.htmlspecialchars(date('Y-m-d', strtotime($r['created_at']))).'</td>';
    $html .= '<td>'.htmlspecialchars($r['category']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['description']).'</td>';
    $html .= '<td style="text-align:right;">₦'.number_format($r['amount'],2).'</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';
$html .= '<br><b>Income Total:</b> ₦'.number_format($income_total,2).'<br>';
$html .= '<b>Expenditure Total:</b> ₦'.number_format($expend_total,2).'<br>';
$html .= '<b>Net:</b> <span style="color:'.($net>=0?'green':'red').';">₦'.number_format($net,2).'</span>';

if ($type === 'pdf') {
    if (class_exists('Dompdf\Dompdf')) {
        require_once 'vendor/autoload.php';
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('coop_finance_report.pdf');
        exit;
    } else {
        // fallback: show styled HTML for browser print
        echo '<html><head><title>Coop Finance Report</title></head><body>'.$html.'<script>window.print();</script></body></html>';
        exit;
    }
}
echo $html; 