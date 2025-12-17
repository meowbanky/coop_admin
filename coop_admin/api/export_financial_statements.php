<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}
require_once('../Connections/coop.php');
require_once('../libs/reports/IncomeExpenditureStatement.php');
require_once('../libs/reports/BalanceSheet.php');
require_once('../libs/reports/CashflowStatement.php');
$periodid = intval($_GET['periodid'] ?? 0);
$type = $_GET['type'] ?? 'income';
if ($periodid <= 0) {
    die('Invalid period');
}
// Get period name
$periodQuery = "SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?";
$stmt = mysqli_prepare($coop, $periodQuery);
mysqli_stmt_bind_param($stmt, "i", $periodid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$period = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
$periodName = $period['PayrollPeriod'] ?? 'Unknown';
// Generate statement
$data = null;
$filename = '';
if ($type == 'income') {
    $generator = new IncomeExpenditureStatement($coop, $database);
    $result = $generator->generateStatement($periodid);
    $data = $result['statement'][$periodid];
    $filename = 'Income_Expenditure_' . str_replace(' ', '_', $periodName);
} elseif ($type == 'balance') {
    $generator = new BalanceSheet($coop, $database);
    $result = $generator->generateStatement($periodid);
    $data = $result['statement'][$periodid];
    $filename = 'Balance_Sheet_' . str_replace(' ', '_', $periodName);
} elseif ($type == 'cashflow') {
    $generator = new CashflowStatement($coop, $database);
    $result = $generator->generateStatement($periodid);
    $data = $result['statement'][$periodid];
    $filename = 'Cashflow_Statement_' . str_replace(' ', '_', $periodName);
}
// Output as CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
$output = fopen('php://output', 'w');
// Write header
fputcsv($output, [strtoupper(str_replace('_', ' & ', $type)) . ' STATEMENT']);
fputcsv($output, ['Period: ' . $periodName]);
fputcsv($output, ['Generated: ' . date('d M Y, h:i A')]);
fputcsv($output, []); // Empty line
if ($type == 'income' && $data) {
    fputcsv($output, ['REVENUE', '']);
    fputcsv($output, ['Entrance Fee', number_format($data['revenue']['entrance_fee'], 2)]);
    fputcsv($output, ['Interest Charges', number_format($data['revenue']['interest_charges'], 2)]);
    fputcsv($output, ['Other Income', number_format($data['revenue']['other_income'], 2)]);
    fputcsv($output, ['TOTAL REVENUE', number_format($data['revenue']['total_revenue'], 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['COST OF SALES', number_format($data['cost_of_sales'], 2)]);
    fputcsv($output, ['GROSS PROFIT', number_format($data['gross_profit'], 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['OVERHEAD EXPENSES', '']);
    foreach ($data['overhead'] as $key => $value) {
        if ($key != 'total_expenses') {
            fputcsv($output, [str_replace('_', ' ', ucwords($key, '_')), number_format($value, 2)]);
        }
    }
    fputcsv($output, ['TOTAL EXPENSES', number_format($data['overhead']['total_expenses'], 2)]);
    fputcsv($output, []);
    fputcsv($output, ['SURPLUS (DEFICIT)', number_format($data['surplus'], 2)]);
    
} elseif ($type == 'balance' && $data) {
    fputcsv($output, ['ASSETS', '']);
    fputcsv($output, ['Non-Current Assets', number_format($data['total_non_current_assets'], 2)]);
    fputcsv($output, ['Current Assets', number_format($data['total_current_assets'], 2)]);
    fputcsv($output, ['TOTAL ASSETS', number_format($data['total_current_assets'] + $data['total_non_current_assets'], 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['EQUITY', '']);
    fputcsv($output, ['Members Fund', number_format($data['total_members_fund'], 2)]);
    fputcsv($output, ['Reserves', number_format($data['total_reserves'], 2)]);
    fputcsv($output, ['Retained Earnings', number_format($data['retained_earnings'], 2)]);
    fputcsv($output, ['TOTAL EQUITY', number_format($data['total_equity'], 2)]);
    fputcsv($output, []);
    fputcsv($output, ['NET ASSETS', number_format($data['net_assets'], 2)]);
    
} elseif ($type == 'cashflow' && $data) {
    fputcsv($output, ['OPERATING ACTIVITIES', '']);
    fputcsv($output, ['Net Profit', number_format($data['operating']['net_profit'], 2)]);
    fputcsv($output, ['Add: Depreciation', number_format($data['operating']['depreciation'], 2)]);
    fputcsv($output, ['Net Operating Cashflow', number_format($data['operating']['net_cashflow_operating'], 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['INVESTING ACTIVITIES', '']);
    fputcsv($output, ['Net Investing Cashflow', number_format($data['investing']['net_cashflow_investing'], 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['FINANCING ACTIVITIES', '']);
    fputcsv($output, ['Net Financing Cashflow', number_format($data['financing']['net_cashflow_financing'], 2)]);
    fputcsv($output, []);
    
    fputcsv($output, ['NET CASHFLOW', number_format($data['net_cashflow'], 2)]);
    fputcsv($output, ['Cash Beginning', number_format($data['cash_beginning'], 2)]);
    fputcsv($output, ['Cash Ending', number_format($data['cash_ending'], 2)]);
}
fclose($output);
exit;
