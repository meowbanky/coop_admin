<?php
ini_set('max_execution_time', '3000');
require_once('Connections/coop.php');
include_once('classes/model.php');

// Set content type to JSON (will be overridden for Excel export)
header('Content-Type: application/json');

// Handle CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Check if this is an export request (both POST and GET)
    $isExport = (isset($_POST['export']) && $_POST['export'] === true) || 
                (isset($_GET['export']) && $_GET['export'] === 'true');
    
    // Get parameters from both POST and GET
    $periodFrom = isset($_POST['period_from']) ? (int)$_POST['period_from'] : 
                  (isset($_GET['period_from']) ? (int)$_GET['period_from'] : -1);
    $periodTo = isset($_POST['period_to']) ? (int)$_POST['period_to'] : 
                (isset($_GET['period_to']) ? (int)$_GET['period_to'] : -1);
    $staffId = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : 
               (isset($_GET['staff_id']) ? trim($_GET['staff_id']) : '');
    $recordsPerPage = isset($_POST['records_per_page']) ? (int)$_POST['records_per_page'] : 
                      (isset($_GET['records_per_page']) ? (int)$_GET['records_per_page'] : 100);
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 
            (isset($_GET['page']) ? (int)$_GET['page'] : 1);

    // Validate parameters
    if ($periodFrom === -1 || $periodTo === -1) {
        throw new Exception('Please select both start and end periods');
    }

    if ($periodFrom > $periodTo) {
        throw new Exception('Start period cannot be greater than end period');
    }

    // Calculate offset (skip pagination for export)
    $offset = $isExport ? 0 : ($page - 1) * $recordsPerPage;
    
    // Ensure values are positive integers
    $recordsPerPage = $isExport ? 999999 : max(1, (int)$recordsPerPage); // Large number for export
    $offset = max(0, (int)$offset);
    $page = max(1, (int)$page);
    
    // Debug logging
    error_log("Master Report Debug - Period From: $periodFrom, Period To: $periodTo, Staff ID: $staffId, Records Per Page: $recordsPerPage, Page: $page, Offset: $offset");
    
    // Test database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Test if tables exist
    $testQuery = $conn->prepare("SHOW TABLES LIKE 'tbl_mastertransact'");
    $testQuery->execute();
    if ($testQuery->rowCount() == 0) {
        throw new Exception('Table tbl_mastertransact does not exist');
    }

    // Build the query based on whether staff ID is provided
    if (!empty($staffId)) {
        // Query for specific staff member
        $countSql = 'SELECT COUNT(DISTINCT tbl_mastertransact.TransactionPeriod) as Total
                     FROM tbl_mastertransact
                     INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID
                     INNER JOIN tbpayrollperiods ON tbl_mastertransact.TransactionPeriod = tbpayrollperiods.id
                     WHERE tbl_mastertransact.COOPID = ? 
                     AND tbl_mastertransact.TransactionPeriod BETWEEN ? AND ?';

        $countQuery = $conn->prepare($countSql);
        $countQuery->execute([$staffId, $periodFrom, $periodTo]);
        $totalRecords = $countQuery->fetch(PDO::FETCH_ASSOC)['Total'];

        $sql = 'SELECT
                    tbl_mastertransact.COOPID,
                    tbl_mastertransact.TransactionPeriod,
                    CONCAT(tblemployees.LastName, ", ", IFNULL(LEFT(tblemployees.MiddleName, 1), ""), " ", LEFT(tblemployees.FirstName, 1)) AS namee,
                    tbpayrollperiods.PayrollPeriod,
                    tbpayrollperiods.PhysicalYear,
                    SUM(tbl_mastertransact.savingsAmount) as savingsAmount,
                    SUM(tbl_mastertransact.sharesAmount) as sharesAmount,
                    SUM(tbl_mastertransact.InterestPaid) as InterestPaid,
                    SUM(tbl_mastertransact.DevLevy) as DevLevy,
                    SUM(tbl_mastertransact.Stationery) as Stationery,
                    SUM(tbl_mastertransact.EntryFee) as EntryFee,
                    SUM(tbl_mastertransact.Commodity) as Commodity,
                    SUM(tbl_mastertransact.CommodityRepayment) as CommodityRepayment,
                    SUM(tbl_mastertransact.loan) as loan,
                    SUM(tbl_mastertransact.loanRepayment) as loanRepayment
                FROM tbl_mastertransact
                INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID
                INNER JOIN tbpayrollperiods ON tbl_mastertransact.TransactionPeriod = tbpayrollperiods.id
                WHERE tbl_mastertransact.COOPID = ? 
                AND tbl_mastertransact.TransactionPeriod BETWEEN ? AND ?
                GROUP BY tbl_mastertransact.TransactionPeriod 
                ORDER BY tbl_mastertransact.TransactionPeriod ASC
                LIMIT ' . (int)$recordsPerPage . ' OFFSET ' . (int)$offset;

        $query = $conn->prepare($sql);
        $params = [$staffId, $periodFrom, $periodTo];
        error_log("SQL Query: " . $sql);
        error_log("SQL Params: " . json_encode($params));
        $query->execute($params);
    } else {
        // Query for all staff members
        $countSql = 'SELECT COUNT(DISTINCT CONCAT(tbl_mastertransact.COOPID, "-", tbl_mastertransact.TransactionPeriod)) as Total
                     FROM tbl_mastertransact
                     INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID
                     INNER JOIN tbpayrollperiods ON tbl_mastertransact.TransactionPeriod = tbpayrollperiods.id
                     WHERE tbl_mastertransact.TransactionPeriod BETWEEN ? AND ?';

        $countQuery = $conn->prepare($countSql);
        $countQuery->execute([$periodFrom, $periodTo]);
        $totalRecords = $countQuery->fetch(PDO::FETCH_ASSOC)['Total'];

        $sql = 'SELECT
                    tbl_mastertransact.COOPID,
                    tbl_mastertransact.TransactionPeriod,
                    CONCAT(tblemployees.LastName, ", ", IFNULL(LEFT(tblemployees.MiddleName, 1), ""), " ", LEFT(tblemployees.FirstName, 1)) AS namee,
                    tbpayrollperiods.PayrollPeriod,
                    tbpayrollperiods.PhysicalYear,
                    SUM(tbl_mastertransact.savingsAmount) as savingsAmount,
                    SUM(tbl_mastertransact.sharesAmount) as sharesAmount,
                    SUM(tbl_mastertransact.InterestPaid) as InterestPaid,
                    SUM(tbl_mastertransact.DevLevy) as DevLevy,
                    SUM(tbl_mastertransact.Stationery) as Stationery,
                    SUM(tbl_mastertransact.EntryFee) as EntryFee,
                    SUM(tbl_mastertransact.Commodity) as Commodity,
                    SUM(tbl_mastertransact.CommodityRepayment) as CommodityRepayment,
                    SUM(tbl_mastertransact.loan) as loan,
                    SUM(tbl_mastertransact.loanRepayment) as loanRepayment
                FROM tbl_mastertransact
                INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID
                INNER JOIN tbpayrollperiods ON tbl_mastertransact.TransactionPeriod = tbpayrollperiods.id
                WHERE tbl_mastertransact.TransactionPeriod BETWEEN ? AND ?
                GROUP BY tbl_mastertransact.COOPID, tbl_mastertransact.TransactionPeriod 
                ORDER BY tbl_mastertransact.COOPID, tbl_mastertransact.TransactionPeriod ASC
                LIMIT ' . (int)$recordsPerPage . ' OFFSET ' . (int)$offset;

        $query = $conn->prepare($sql);
        $params = [$periodFrom, $periodTo];
        error_log("SQL Query: " . $sql);
        error_log("SQL Params: " . json_encode($params));
        $query->execute($params);
    }

    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for SQL errors
    if ($query->errorCode() !== '00000') {
        $errorInfo = $query->errorInfo();
        throw new Exception('SQL Error: ' . $errorInfo[2]);
    }

    // Process results and calculate additional fields
    $processedData = [];
    $totals = [
        'shares_amount' => 0,
        'savings_amount' => 0,
        'interest_paid' => 0,
        'dev_levy' => 0,
        'stationery' => 0,
        'entry_fee' => 0,
        'loan' => 0,
        'loan_repayment' => 0,
        'commodity' => 0,
        'commodity_repayment' => 0,
        'total' => 0
    ];

    foreach ($results as $row) {
        // Calculate balances and additional fields
        $sharesBalance = getSharesBalance($row['COOPID'], $row['TransactionPeriod']);
        $savingsBalance = getSavingsBalance($row['COOPID'], $row['TransactionPeriod']);
        $loanBalance = getLoanBalance($row['COOPID'], $row['TransactionPeriod']);
        $commodityBalance = getCommodityBalance($row['COOPID'], $row['TransactionPeriod']);
        
        // Calculate total for this row
        $rowTotal = $row['sharesAmount'] + $row['savingsAmount'] + $row['InterestPaid'] + 
                   $row['DevLevy'] + $row['Stationery'] + $row['EntryFee'] + 
                   $row['loanRepayment'] + $row['CommodityRepayment'];

        $processedRow = [
            'coopid' => $row['COOPID'],
            'period' => $row['TransactionPeriod'],
            'period_display' => substr($row['PayrollPeriod'], 0, 3) . ' - ' . $row['PhysicalYear'],
            'name' => $row['namee'],
            'shares_amount' => (float)$row['sharesAmount'],
            'shares_balance' => (float)$sharesBalance,
            'savings_amount' => (float)$row['savingsAmount'],
            'savings_balance' => (float)$savingsBalance,
            'interest_paid' => (float)$row['InterestPaid'],
            'dev_levy' => (float)$row['DevLevy'],
            'stationery' => (float)$row['Stationery'],
            'entry_fee' => (float)$row['EntryFee'],
            'loan' => (float)$row['loan'],
            'loan_repayment' => (float)$row['loanRepayment'],
            'loan_balance' => (float)$loanBalance,
            'commodity' => (float)$row['Commodity'],
            'commodity_repayment' => (float)$row['CommodityRepayment'],
            'commodity_balance' => (float)$commodityBalance,
            'total' => (float)$rowTotal
        ];

        $processedData[] = $processedRow;

        // Add to totals
        $totals['shares_amount'] += $processedRow['shares_amount'];
        $totals['savings_amount'] += $processedRow['savings_amount'];
        $totals['interest_paid'] += $processedRow['interest_paid'];
        $totals['dev_levy'] += $processedRow['dev_levy'];
        $totals['stationery'] += $processedRow['stationery'];
        $totals['entry_fee'] += $processedRow['entry_fee'];
        $totals['loan'] += $processedRow['loan'];
        $totals['loan_repayment'] += $processedRow['loan_repayment'];
        $totals['commodity'] += $processedRow['commodity'];
        $totals['commodity_repayment'] += $processedRow['commodity_repayment'];
        $totals['total'] += $processedRow['total'];
    }

    // Calculate pagination
    $totalPages = ceil($totalRecords / $recordsPerPage);

    $pagination = [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords,
        'records_per_page' => $recordsPerPage,
        'has_previous' => $page > 1,
        'has_next' => $page < $totalPages
    ];

    // Calculate grand totals for ALL records (not just current page)
    $grandTotals = calculateGrandTotals($periodFrom, $periodTo, $staffId);

    // Handle export request
    if ($isExport) {
        exportToExcel($processedData, $totals, $grandTotals, $periodFrom, $periodTo, $staffId);
        exit;
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Report generated successfully',
        'data' => $processedData,
        'totals' => $totals,
        'grand_totals' => $grandTotals,
        'pagination' => $pagination
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper functions to calculate balances
function getSharesBalance($coopId, $period) {
    global $conn;
    try {
        $query = $conn->prepare("
            SELECT COALESCE(SUM(sharesAmount), 0) as balance 
            FROM tbl_shares 
            WHERE CoopID = ? AND SharesPeriod < ?
        ");
        $query->execute([$coopId, $period]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['balance'];
    } catch (Exception $e) {
        return 0;
    }
}

function getSavingsBalance($coopId, $period) {
    global $conn;
    try {
        $query = $conn->prepare("
            SELECT COALESCE(SUM(AmountPaid), 0) as balance 
            FROM tbl_savings 
            WHERE coopid = ? AND DeductionPeriod < ?
        ");
        $query->execute([$coopId, $period]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['balance'];
    } catch (Exception $e) {
        return 0;
    }
}

function getLoanBalance($coopId, $period) {
    global $conn;
    try {
        $loanQuery = $conn->prepare("
            SELECT COALESCE(SUM(LoanAmount), 0) as total_loans 
            FROM tbl_loans 
            WHERE coopid = ? AND LoanPeriod <= ?
        ");
        $loanQuery->execute([$coopId, $period]);
        $loanResult = $loanQuery->fetch(PDO::FETCH_ASSOC);
        
        $repaymentQuery = $conn->prepare("
            SELECT COALESCE(SUM(Repayment), 0) as total_repayments 
            FROM tbl_loanrepayment 
            WHERE coopid = ? AND LoanRepaymentPeriod <= ?
        ");
        $repaymentQuery->execute([$coopId, $period]);
        $repaymentResult = $repaymentQuery->fetch(PDO::FETCH_ASSOC);
        
        return $loanResult['total_loans'] - $repaymentResult['total_repayments'];
    } catch (Exception $e) {
        return 0;
    }
}

function getCommodityBalance($coopId, $period) {
    global $conn;
    try {
        $commodityQuery = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_commodity 
            FROM tbl_commodity 
            WHERE coopID = ? AND Period < ?
        ");
        $commodityQuery->execute([$coopId, $period]);
        $commodityResult = $commodityQuery->fetch(PDO::FETCH_ASSOC);
        
        $repaymentQuery = $conn->prepare("
            SELECT COALESCE(SUM(CommodityPayment), 0) as total_repayments 
            FROM tbl_commodityrepayment 
            WHERE coopid = ? AND PaymentPeriod < ?
        ");
        $repaymentQuery->execute([$coopId, $period]);
        $repaymentResult = $repaymentQuery->fetch(PDO::FETCH_ASSOC);
        
        return $commodityResult['total_commodity'] - $repaymentResult['total_repayments'];
    } catch (Exception $e) {
        return 0;
    }
}

// Excel export function
function exportToExcel($data, $totals, $grandTotals, $periodFrom, $periodTo, $staffId) {
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="master_report_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // Get period names for filename
    global $conn;
    $periodFromQuery = $conn->prepare('SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?');
    $periodFromQuery->execute([$periodFrom]);
    $periodFromName = $periodFromQuery->fetch(PDO::FETCH_ASSOC)['PayrollPeriod'] ?? 'Unknown';
    
    $periodToQuery = $conn->prepare('SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?');
    $periodToQuery->execute([$periodTo]);
    $periodToName = $periodToQuery->fetch(PDO::FETCH_ASSOC)['PayrollPeriod'] ?? 'Unknown';
    
    // Start output
    echo "<html><head><meta charset='utf-8'></head><body>";
    echo "<table border='1'>";
    
    // Title
    echo "<tr><td colspan='19' style='text-align:center; font-weight:bold; font-size:16px; background-color:#f0f0f0;'>";
    echo "MASTER REPORT - {$periodFromName} TO {$periodToName}";
    if ($staffId) {
        echo " (STAFF: {$staffId})";
    }
    echo "</td></tr>";
    
    // Empty row
    echo "<tr><td colspan='19'>&nbsp;</td></tr>";
    
    // Headers
    echo "<tr style='background-color:#e0e0e0; font-weight:bold;'>";
    echo "<td>S/N</td>";
    echo "<td>Period</td>";
    echo "<td>Coop No</td>";
    echo "<td>Share Amt</td>";
    echo "<td>Share Bal</td>";
    echo "<td>Sav Amt</td>";
    echo "<td>Sav Bal</td>";
    echo "<td>Interest</td>";
    echo "<td>Dev Levy</td>";
    echo "<td>Stationery</td>";
    echo "<td>Entry Fee</td>";
    echo "<td>Loan</td>";
    echo "<td>Loan Pay</td>";
    echo "<td>Loan Bal</td>";
    echo "<td>Commodity</td>";
    echo "<td>Comdty Pay</td>";
    echo "<td>Comdty Bal</td>";
    echo "<td>Total</td>";
    echo "</tr>";
    
    // Data rows
    foreach ($data as $index => $row) {
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>" . htmlspecialchars($row['period_display']) . "</td>";
        echo "<td>" . htmlspecialchars($row['coopid']) . "</td>";
        echo "<td>" . number_format($row['shares_amount'], 2) . "</td>";
        echo "<td>" . number_format($row['shares_balance'], 2) . "</td>";
        echo "<td>" . number_format($row['savings_amount'], 2) . "</td>";
        echo "<td>" . number_format($row['savings_balance'], 2) . "</td>";
        echo "<td>" . number_format($row['interest_paid'], 2) . "</td>";
        echo "<td>" . number_format($row['dev_levy'], 2) . "</td>";
        echo "<td>" . number_format($row['stationery'], 2) . "</td>";
        echo "<td>" . number_format($row['entry_fee'], 2) . "</td>";
        echo "<td>" . number_format($row['loan'], 2) . "</td>";
        echo "<td>" . number_format($row['loan_repayment'], 2) . "</td>";
        echo "<td>" . number_format($row['loan_balance'], 2) . "</td>";
        echo "<td>" . number_format($row['commodity'], 2) . "</td>";
        echo "<td>" . number_format($row['commodity_repayment'], 2) . "</td>";
        echo "<td>" . number_format($row['commodity_balance'], 2) . "</td>";
        echo "<td>" . number_format($row['total'], 2) . "</td>";
        echo "</tr>";
    }
    
    // Empty row
    echo "<tr><td colspan='19'>&nbsp;</td></tr>";
    
    // Totals row
    echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
    echo "<td colspan='3'>TOTALS (CURRENT PAGE)</td>";
    echo "<td>" . number_format($totals['shares_amount'], 2) . "</td>";
    echo "<td>-</td>";
    echo "<td>" . number_format($totals['savings_amount'], 2) . "</td>";
    echo "<td>-</td>";
    echo "<td>" . number_format($totals['interest_paid'], 2) . "</td>";
    echo "<td>" . number_format($totals['dev_levy'], 2) . "</td>";
    echo "<td>" . number_format($totals['stationery'], 2) . "</td>";
    echo "<td>" . number_format($totals['entry_fee'], 2) . "</td>";
    echo "<td>" . number_format($totals['loan'], 2) . "</td>";
    echo "<td>" . number_format($totals['loan_repayment'], 2) . "</td>";
    echo "<td>-</td>";
    echo "<td>" . number_format($totals['commodity'], 2) . "</td>";
    echo "<td>" . number_format($totals['commodity_repayment'], 2) . "</td>";
    echo "<td>-</td>";
    echo "<td>" . number_format($totals['total'], 2) . "</td>";
    echo "</tr>";
    
    // Grand totals row
    if ($grandTotals) {
        echo "<tr style='background-color:#e6f3ff; font-weight:bold; border-top:3px solid #0066cc;'>";
        echo "<td colspan='3'>GRAND TOTALS (ALL PAGES)</td>";
        echo "<td>" . number_format($grandTotals['shares_amount'], 2) . "</td>";
        echo "<td>-</td>";
        echo "<td>" . number_format($grandTotals['savings_amount'], 2) . "</td>";
        echo "<td>-</td>";
        echo "<td>" . number_format($grandTotals['interest_paid'], 2) . "</td>";
        echo "<td>" . number_format($grandTotals['dev_levy'], 2) . "</td>";
        echo "<td>" . number_format($grandTotals['stationery'], 2) . "</td>";
        echo "<td>" . number_format($grandTotals['entry_fee'], 2) . "</td>";
        echo "<td>" . number_format($grandTotals['loan'], 2) . "</td>";
        echo "<td>" . number_format($grandTotals['loan_repayment'], 2) . "</td>";
        echo "<td>-</td>";
        echo "<td>" . number_format($grandTotals['commodity'], 2) . "</td>";
        echo "<td>" . number_format($grandTotals['commodity_repayment'], 2) . "</td>";
        echo "<td>-</td>";
        echo "<td style='color:#0066cc; font-size:14px;'>" . number_format($grandTotals['total'], 2) . "</td>";
        echo "</tr>";
    }
    
    // Footer
    echo "<tr><td colspan='19'>&nbsp;</td></tr>";
    echo "<tr><td colspan='19' style='text-align:center; font-size:12px; color:#666;'>";
    echo "Generated on: " . date('Y-m-d H:i:s') . " | Total Records: " . count($data);
    echo "</td></tr>";
    
    echo "</table></body></html>";
}

// Function to calculate grand totals for all records
function calculateGrandTotals($periodFrom, $periodTo, $staffId) {
    global $conn;
    
    try {
        if (!empty($staffId)) {
            // Query for specific staff member - all records
            $sql = 'SELECT
                        SUM(tbl_mastertransact.savingsAmount) as savingsAmount,
                        SUM(tbl_mastertransact.sharesAmount) as sharesAmount,
                        SUM(tbl_mastertransact.InterestPaid) as InterestPaid,
                        SUM(tbl_mastertransact.DevLevy) as DevLevy,
                        SUM(tbl_mastertransact.Stationery) as Stationery,
                        SUM(tbl_mastertransact.EntryFee) as EntryFee,
                        SUM(tbl_mastertransact.Commodity) as Commodity,
                        SUM(tbl_mastertransact.CommodityRepayment) as CommodityRepayment,
                        SUM(tbl_mastertransact.loan) as loan,
                        SUM(tbl_mastertransact.loanRepayment) as loanRepayment
                    FROM tbl_mastertransact
                    INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID
                    INNER JOIN tbpayrollperiods ON tbl_mastertransact.TransactionPeriod = tbpayrollperiods.id
                    WHERE tbl_mastertransact.COOPID = ? 
                    AND tbl_mastertransact.TransactionPeriod BETWEEN ? AND ?';

            $query = $conn->prepare($sql);
            $query->execute([$staffId, $periodFrom, $periodTo]);
        } else {
            // Query for all staff members - all records
            $sql = 'SELECT
                        SUM(tbl_mastertransact.savingsAmount) as savingsAmount,
                        SUM(tbl_mastertransact.sharesAmount) as sharesAmount,
                        SUM(tbl_mastertransact.InterestPaid) as InterestPaid,
                        SUM(tbl_mastertransact.DevLevy) as DevLevy,
                        SUM(tbl_mastertransact.Stationery) as Stationery,
                        SUM(tbl_mastertransact.EntryFee) as EntryFee,
                        SUM(tbl_mastertransact.Commodity) as Commodity,
                        SUM(tbl_mastertransact.CommodityRepayment) as CommodityRepayment,
                        SUM(tbl_mastertransact.loan) as loan,
                        SUM(tbl_mastertransact.loanRepayment) as loanRepayment
                    FROM tbl_mastertransact
                    INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID
                    INNER JOIN tbpayrollperiods ON tbl_mastertransact.TransactionPeriod = tbpayrollperiods.id
                    WHERE tbl_mastertransact.TransactionPeriod BETWEEN ? AND ?';

            $query = $conn->prepare($sql);
            $query->execute([$periodFrom, $periodTo]);
        }

        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        // Calculate total for grand totals
        $grandTotal = $result['sharesAmount'] + $result['savingsAmount'] + $result['InterestPaid'] + 
                     $result['DevLevy'] + $result['Stationery'] + $result['EntryFee'] + 
                     $result['loanRepayment'] + $result['CommodityRepayment'];

        return [
            'shares_amount' => (float)$result['sharesAmount'],
            'savings_amount' => (float)$result['savingsAmount'],
            'interest_paid' => (float)$result['InterestPaid'],
            'dev_levy' => (float)$result['DevLevy'],
            'stationery' => (float)$result['Stationery'],
            'entry_fee' => (float)$result['EntryFee'],
            'loan' => (float)$result['loan'],
            'loan_repayment' => (float)$result['loanRepayment'],
            'commodity' => (float)$result['Commodity'],
            'commodity_repayment' => (float)$result['CommodityRepayment'],
            'total' => (float)$grandTotal
        ];
        
    } catch (Exception $e) {
        error_log("Grand totals calculation error: " . $e->getMessage());
        return [
            'shares_amount' => 0,
            'savings_amount' => 0,
            'interest_paid' => 0,
            'dev_levy' => 0,
            'stationery' => 0,
            'entry_fee' => 0,
            'loan' => 0,
            'loan_repayment' => 0,
            'commodity' => 0,
            'commodity_repayment' => 0,
            'total' => 0
        ];
    }
}
?>