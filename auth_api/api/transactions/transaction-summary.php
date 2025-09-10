<?php
if (ob_get_level()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set all required CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 1728000');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../utils/JWTHandler.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'));

    if (!isset($data->fromPeriod) || !isset($data->toPeriod)) {
        throw new Exception('From and To periods are required');
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT 
    tbpayrollperiods.PayrollPeriod,
    SUM(tbl_mastertransact.DevLevy) as devLevy,
    SUM(tbl_mastertransact.savingsAmount) as savingsAmount,
    SUM(tbl_mastertransact.sharesAmount) as sharesAmount,
    SUM(tbl_mastertransact.InterestPaid) as InterestPaid,
    SUM(tbl_mastertransact.Commodity) as Commodity,
    SUM(tbl_mastertransact.CommodityRepayment) as CommodityRepayment,
    SUM(tbl_mastertransact.loan) as loan,
    SUM(tbl_mastertransact.loanRepayment) as loanRepayment,
    SUM(tbl_mastertransact.Stationery) as Stationery,
    (
        SELECT 
            SUM(m2.loan) - SUM(m2.loanRepayment)
        FROM tbl_mastertransact m2
        WHERE m2.COOPID = tbl_mastertransact.COOPID
        AND m2.TransactionPeriod <= tbl_mastertransact.TransactionPeriod
    ) as loanBalance,
		(
        SELECT 
            SUM(m2.savingsAmount)
        FROM tbl_mastertransact m2
        WHERE m2.COOPID = tbl_mastertransact.COOPID
        AND m2.TransactionPeriod <= tbl_mastertransact.TransactionPeriod
    ) as savingsBalance,
		(
        SELECT 
            SUM(m2.sharesAmount)
        FROM tbl_mastertransact m2
        WHERE m2.COOPID = tbl_mastertransact.COOPID
        AND m2.TransactionPeriod <= tbl_mastertransact.TransactionPeriod
    ) as sharesBalance,
		(
        SELECT 
            SUM(m2.Commodity) - SUM(m2.CommodityRepayment)
        FROM tbl_mastertransact m2
        WHERE m2.COOPID = tbl_mastertransact.COOPID
        AND m2.TransactionPeriod <= tbl_mastertransact.TransactionPeriod
    ) as CommodityBalance,
    SUM(tbl_mastertransact.DevLevy + 
        tbl_mastertransact.savingsAmount + 
        tbl_mastertransact.sharesAmount + 
        tbl_mastertransact.InterestPaid + 
        tbl_mastertransact.CommodityRepayment + 
        tbl_mastertransact.loanRepayment + 
        tbl_mastertransact.Stationery) as total
FROM tbl_mastertransact 
LEFT JOIN tbpayrollperiods ON tbl_mastertransact.TransactionPeriod = tbpayrollperiods.id 
WHERE tbl_mastertransact.COOPID = :coopId 
AND TransactionPeriod BETWEEN :fromPeriod AND :toPeriod 
GROUP BY tbpayrollperiods.id, tbpayrollperiods.PayrollPeriod
ORDER BY tbpayrollperiods.id DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':coopId', $data->coopId);
    $stmt->bindParam(':fromPeriod', $data->fromPeriod);
    $stmt->bindParam(':toPeriod', $data->toPeriod);
    $stmt->execute();

    $summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $summaries
    ]);
    error_log(print_r($summaries, true));
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}