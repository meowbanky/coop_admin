<?php
ini_set('max_execution_time', '300');
require_once('../Connections/coop.php');
include_once('../classes/model.php');

// Set content type to JSON
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

// Start session
session_start();

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete_records':
            deleteRecords();
            break;
        case 'export_data':
            exportData();
            break;
        case 'get_summary':
            getSummary();
            break;
        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function deleteRecords() {
    global $conn;
    
    if (!isset($_POST['records']) || !is_array($_POST['records'])) {
        throw new Exception('No records specified for deletion');
    }

    $records = $_POST['records'];
    $deletedCount = 0;
    $errors = [];

    foreach ($records as $record) {
        $parts = explode(',', $record);
        if (count($parts) !== 2) {
            $errors[] = "Invalid record format: $record";
            continue;
        }

        $coopId = trim($parts[0]);
        $period = trim($parts[1]);

        if (empty($coopId) || empty($period)) {
            $errors[] = "Invalid record data: $record";
            continue;
        }

        try {
            // Start transaction
            $conn->beginTransaction();

            // Delete from all related tables
            $tables = [
                'tbl_shares' => ['coopid', 'SharesPeriod'],
                'tbl_savings' => ['coopid', 'deductionperiod'],
                'tbl_commodityrepayment' => ['coopid', 'paymentperiod'],
                'tbl_loans' => ['coopid', 'loanperiod'],
                'tbl_loanrepayment' => ['coopid', 'loanrepaymentPeriod'],
                'tbl_entryfee' => ['coopid', 'deductionperiod'],
                'tbl_stationery' => ['coopid', 'stationeryperiod'],
                'tbl_mastertransact' => ['coopid', 'TransactionPeriod']
            ];

            foreach ($tables as $table => $columns) {
                $sql = "DELETE FROM $table WHERE {$columns[0]} = ? AND {$columns[1]} = ?";
                $query = $conn->prepare($sql);
                $query->execute([$coopId, $period]);
            }

            // Commit transaction
            $conn->commit();
            $deletedCount++;

        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $errors[] = "Failed to delete record $coopId, $period: " . $e->getMessage();
        }
    }

    if ($deletedCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Successfully deleted $deletedCount records",
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ]);
    } else {
        throw new Exception('No records were deleted. Errors: ' . implode(', ', $errors));
    }
}

function exportData() {
    global $conn;
    
    $periodFrom = $_POST['period_from'] ?? '';
    $periodTo = $_POST['period_to'] ?? '';
    $format = $_POST['format'] ?? 'excel';
    $staffId = $_POST['staff_id'] ?? '';

    if (empty($periodFrom) || empty($periodTo)) {
        throw new Exception('Period range is required for export');
    }

    // Build query based on parameters
    if (!empty($staffId)) {
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
                ORDER BY tbl_mastertransact.TransactionPeriod ASC';

        $query = $conn->prepare($sql);
        $query->execute([$staffId, $periodFrom, $periodTo]);
    } else {
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
                ORDER BY tbl_mastertransact.COOPID, tbl_mastertransact.TransactionPeriod ASC';

        $query = $conn->prepare($sql);
        $query->execute([$periodFrom, $periodTo]);
    }

    $results = $query->fetchAll(PDO::FETCH_ASSOC);

    // Process data for export
    $exportData = [];
    foreach ($results as $row) {
        $exportData[] = [
            'Coop ID' => $row['COOPID'],
            'Period' => substr($row['PayrollPeriod'], 0, 3) . ' - ' . $row['PhysicalYear'],
            'Name' => $row['namee'],
            'Shares Amount' => number_format($row['sharesAmount'], 2),
            'Savings Amount' => number_format($row['savingsAmount'], 2),
            'Interest Paid' => number_format($row['InterestPaid'], 2),
            'Dev Levy' => number_format($row['DevLevy'], 2),
            'Stationery' => number_format($row['Stationery'], 2),
            'Entry Fee' => number_format($row['EntryFee'], 2),
            'Loan' => number_format($row['loan'], 2),
            'Loan Repayment' => number_format($row['loanRepayment'], 2),
            'Commodity' => number_format($row['Commodity'], 2),
            'Commodity Repayment' => number_format($row['CommodityRepayment'], 2)
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Export data prepared successfully',
        'data' => $exportData,
        'format' => $format,
        'total_records' => count($exportData)
    ]);
}

function getSummary() {
    global $conn;
    
    $periodFrom = $_POST['period_from'] ?? '';
    $periodTo = $_POST['period_to'] ?? '';
    $staffId = $_POST['staff_id'] ?? '';

    if (empty($periodFrom) || empty($periodTo)) {
        throw new Exception('Period range is required for summary');
    }

    // Build summary query
    if (!empty($staffId)) {
        $sql = 'SELECT
                    COUNT(DISTINCT tbl_mastertransact.TransactionPeriod) as total_periods,
                    SUM(tbl_mastertransact.savingsAmount) as total_savings,
                    SUM(tbl_mastertransact.sharesAmount) as total_shares,
                    SUM(tbl_mastertransact.InterestPaid) as total_interest,
                    SUM(tbl_mastertransact.DevLevy) as total_dev_levy,
                    SUM(tbl_mastertransact.Stationery) as total_stationery,
                    SUM(tbl_mastertransact.EntryFee) as total_entry_fee,
                    SUM(tbl_mastertransact.loan) as total_loans,
                    SUM(tbl_mastertransact.loanRepayment) as total_loan_repayment,
                    SUM(tbl_mastertransact.Commodity) as total_commodity,
                    SUM(tbl_mastertransact.CommodityRepayment) as total_commodity_repayment
                FROM tbl_mastertransact
                WHERE tbl_mastertransact.COOPID = ? 
                AND tbl_mastertransact.TransactionPeriod BETWEEN ? AND ?';

        $query = $conn->prepare($sql);
        $query->execute([$staffId, $periodFrom, $periodTo]);
    } else {
        $sql = 'SELECT
                    COUNT(DISTINCT CONCAT(tbl_mastertransact.COOPID, "-", tbl_mastertransact.TransactionPeriod)) as total_records,
                    COUNT(DISTINCT tbl_mastertransact.COOPID) as total_members,
                    SUM(tbl_mastertransact.savingsAmount) as total_savings,
                    SUM(tbl_mastertransact.sharesAmount) as total_shares,
                    SUM(tbl_mastertransact.InterestPaid) as total_interest,
                    SUM(tbl_mastertransact.DevLevy) as total_dev_levy,
                    SUM(tbl_mastertransact.Stationery) as total_stationery,
                    SUM(tbl_mastertransact.EntryFee) as total_entry_fee,
                    SUM(tbl_mastertransact.loan) as total_loans,
                    SUM(tbl_mastertransact.loanRepayment) as total_loan_repayment,
                    SUM(tbl_mastertransact.Commodity) as total_commodity,
                    SUM(tbl_mastertransact.CommodityRepayment) as total_commodity_repayment
                FROM tbl_mastertransact
                WHERE tbl_mastertransact.TransactionPeriod BETWEEN ? AND ?';

        $query = $conn->prepare($sql);
        $query->execute([$periodFrom, $periodTo]);
    }

    $result = $query->fetch(PDO::FETCH_ASSOC);

    // Calculate grand total
    $grandTotal = $result['total_savings'] + $result['total_shares'] + $result['total_interest'] + 
                 $result['total_dev_levy'] + $result['total_stationery'] + $result['total_entry_fee'] + 
                 $result['total_loan_repayment'] + $result['total_commodity_repayment'];

    $summary = [
        'total_records' => $result['total_records'] ?? 0,
        'total_members' => $result['total_members'] ?? 0,
        'total_periods' => $result['total_periods'] ?? 0,
        'totals' => [
            'savings' => (float)$result['total_savings'],
            'shares' => (float)$result['total_shares'],
            'interest' => (float)$result['total_interest'],
            'dev_levy' => (float)$result['total_dev_levy'],
            'stationery' => (float)$result['total_stationery'],
            'entry_fee' => (float)$result['total_entry_fee'],
            'loans' => (float)$result['total_loans'],
            'loan_repayment' => (float)$result['total_loan_repayment'],
            'commodity' => (float)$result['total_commodity'],
            'commodity_repayment' => (float)$result['total_commodity_repayment'],
            'grand_total' => (float)$grandTotal
        ]
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Summary generated successfully',
        'summary' => $summary
    ]);
}
?>
