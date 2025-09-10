<?php
// api/loans/tracking.php
if (ob_get_level()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

try {
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../utils/JWTHandler.php';

    $database = new Database();
    $db = $database->getConnection();

    $coopId = $_GET['coopId'] ?? null;
    if (!$coopId) {
        throw new Exception('CoopID is required');
    }

    // Get loan details with updated month calculation
    $query = "WITH LoanSummary AS (
        SELECT 
            SUM(mt.loan) as principalAmount,
            SUM(mt.loanRepayment) as totalRepaid,
            SUM(mt.InterestPaid) as interestPaid,
            MIN(CASE WHEN mt.loan > 0 THEN mt.DateOfPayment END) as startDate,
            MAX(CASE WHEN mt.loanRepayment > 0 THEN mt.DateOfPayment END) as lastPaymentDate,
            (SELECT SUM(loan) - SUM(loanRepayment) 
             FROM tbl_mastertransact 
             WHERE COOPID = :coopId) as remainingBalance
        FROM tbl_mastertransact mt
        WHERE mt.COOPID = :coopId
    )
    SELECT 
        *,
        CASE 
            WHEN remainingBalance <= 0 THEN 0
            ELSE 
                CASE 
                    WHEN principalAmount > 0 THEN
                        CEIL(
                            (remainingBalance / NULLIF(principalAmount, 0)) * 
                            TIMESTAMPDIFF(MONTH, startDate, 
                                CASE 
                                    WHEN lastPaymentDate > startDate 
                                    THEN lastPaymentDate 
                                    ELSE CURRENT_DATE 
                                END
                            )
                        )
                    ELSE 0
                END
        END as monthsRemaining
    FROM LoanSummary";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':coopId', $coopId);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['principalAmount'] > 0) {
        // Calculate additional metrics
        $principalAmount = floatval($result['principalAmount']);
        $totalRepaid = floatval($result['totalRepaid']);
        $remainingBalance = floatval($result['remainingBalance']);
        $monthsRemaining = intval($result['monthsRemaining']);

        // Calculate elapsed months since loan start
        $startDate = new DateTime($result['startDate']);
        $lastPaymentDate = new DateTime($result['lastPaymentDate']);
        $elapsedMonths = $startDate->diff($lastPaymentDate)->m + ($startDate->diff($lastPaymentDate)->y * 12);

        // Calculate total months based on elapsed months plus remaining months
        $totalMonths = $elapsedMonths + $monthsRemaining;

        // Calculate monthly payment based on actual repayment history
        $monthlyPayment = $totalRepaid / max(1, $elapsedMonths);

        // Calculate payment progress
        $paymentProgress = min(100, ($totalRepaid / $principalAmount) * 100);

        $response = [
            'success' => true,
            'message' => 'Loan tracking data retrieved successfully',
            'data' => [
                'loanId' => $coopId,
                'principalAmount' => $principalAmount,
                'totalRepaid' => $totalRepaid,
                'remainingBalance' => $remainingBalance,
                'interestPaid' => floatval($result['interestPaid']),
                'startDate' => $result['startDate'],
                'lastPaymentDate' => $result['lastPaymentDate'],
                'monthlyPayment' => $monthlyPayment,
                'totalMonths' => $totalMonths,
                'monthsRemaining' => $monthsRemaining,
                'paymentProgress' => $paymentProgress
            ]
        ];
    } else {
        $response = [
            'success' => true,
            'message' => 'No active loans found',
            'data' => null
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Loan tracking error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}