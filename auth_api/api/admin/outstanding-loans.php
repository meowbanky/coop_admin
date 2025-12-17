<?php
// api/admin/outstanding-loans.php
// Admin API for managing outstanding loans

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../../config/Database.php';
    
    // Start session for admin authentication
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is admin
    if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] !== 'Admin') {
        throw new Exception('Admin access required', 403);
    }

    $database = new Database();
    $db = $database->getConnection();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getOutstandingLoans($db);
            break;
        case 'POST':
            importOutstandingLoan($db);
            break;
        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Outstanding loans API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $status_code = $e->getCode();
    if (!is_int($status_code) || $status_code < 100 || $status_code > 599) {
        $status_code = 400;
    }

    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getOutstandingLoans($db) {
    // Get all loans with outstanding amounts (approved but not fully disbursed)
    $query = "SELECT 
        lr.id,
        lr.requester_coop_id,
        lr.requested_amount,
        lr.approved_amount,
        lr.outstanding_amount,
        lr.period_id as original_period_id,
        lr.approved_at,
        lr.approved_by,
        CONCAT(e.FirstName, ' ', e.LastName) as requester_name,
        e.EmailAddress,
        e.MobileNumber,
        pp.PayrollPeriod as original_period_name
    FROM loan_requests lr
    LEFT JOIN tblemployees e ON e.CoopID = lr.requester_coop_id
    LEFT JOIN tbpayrollperiods pp ON pp.id = lr.period_id
    WHERE lr.status = 'approved'
    AND lr.outstanding_amount > 0
    ORDER BY lr.approved_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    // Fetch all periods from salary API to create a lookup map
    $periodMap = [];
    try {
        $apiClientPath = __DIR__ . '/../../../classes/OOUTHSalaryAPIClient.php';
        if (!file_exists($apiClientPath)) {
            $apiClientPath = __DIR__ . '/../../../../classes/OOUTHSalaryAPIClient.php';
        }
        
        if (file_exists($apiClientPath)) {
            require_once $apiClientPath;
            $apiClient = new OOUTHSalaryAPIClient();
            $periodsResult = $apiClient->getPeriods(1, 1000);
            
            if ($periodsResult && isset($periodsResult['success']) && $periodsResult['success'] && isset($periodsResult['data'])) {
                foreach ($periodsResult['data'] as $period) {
                    $periodId = $period['period_id'] ?? $period['id'] ?? null;
                    if ($periodId) {
                        $description = $period['description'] ?? $period['name'] ?? '';
                        $year = $period['year'] ?? '';
                        $displayName = trim($description . ' ' . $year);
                        if (!empty($displayName)) {
                            $periodMap[intval($periodId)] = $displayName;
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching periods from salary API: " . $e->getMessage());
    }

    $outstandingLoans = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $periodId = $row['original_period_id'] ? intval($row['original_period_id']) : null;
        
        // Try to get period name from salary API first, then database, then fallback
        $periodName = null;
        if ($periodId && isset($periodMap[$periodId])) {
            $periodName = $periodMap[$periodId];
        } elseif (!empty($row['original_period_name'])) {
            $periodName = $row['original_period_name'];
        } elseif ($periodId) {
            // Last resort: try to fetch from API directly
            try {
                if (isset($apiClient)) {
                    $periodResult = $apiClient->getPeriod($periodId);
                    if ($periodResult && isset($periodResult['success']) && $periodResult['success'] && isset($periodResult['data'])) {
                        $periodData = $periodResult['data'];
                        $description = $periodData['description'] ?? $periodData['name'] ?? '';
                        $year = $periodData['year'] ?? '';
                        $periodName = trim($description . ' ' . $year);
                        if (!empty($periodName)) {
                            $periodMap[$periodId] = $periodName; // Cache it
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching period {$periodId} from salary API: " . $e->getMessage());
            }
        }
        
        // Final fallback
        if (empty($periodName) && $periodId) {
            $periodName = 'Period ' . $periodId;
        }
        
        $outstandingLoans[] = [
            'id' => intval($row['id']),
            'requester_coop_id' => $row['requester_coop_id'],
            'requester_name' => $row['requester_name'] ?? 'Unknown',
            'requested_amount' => floatval($row['requested_amount']),
            'approved_amount' => floatval($row['approved_amount']),
            'outstanding_amount' => floatval($row['outstanding_amount']),
            'original_period_id' => $periodId,
            'original_period_name' => $periodName,
            'approved_at' => $row['approved_at'],
            'approved_by' => $row['approved_by'],
            'email' => $row['EmailAddress'],
            'mobile' => $row['MobileNumber']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $outstandingLoans
    ]);
}

function importOutstandingLoan($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    $outstandingLoanId = isset($input['outstanding_loan_id']) ? intval($input['outstanding_loan_id']) : null;
    $newPeriodId = isset($input['new_period_id']) ? intval($input['new_period_id']) : null;

    if (!$outstandingLoanId || !$newPeriodId) {
        throw new Exception('outstanding_loan_id and new_period_id are required', 400);
    }

    // Get outstanding loan details
    $outstandingQuery = "SELECT 
        id,
        requester_coop_id,
        outstanding_amount,
        period_id as original_period_id,
        staff_id,
        deduction_id
    FROM loan_requests
    WHERE id = :id
    AND status = 'approved'
    AND outstanding_amount > 0
    LIMIT 1";

    $outstandingStmt = $db->prepare($outstandingQuery);
    $outstandingStmt->execute([':id' => $outstandingLoanId]);
    $outstandingLoan = $outstandingStmt->fetch(PDO::FETCH_ASSOC);

    if (!$outstandingLoan) {
        throw new Exception('Outstanding loan not found or already fully disbursed', 404);
    }

    // Check if user already has a pending request for the new period
    $existingQuery = "SELECT id FROM loan_requests 
        WHERE requester_coop_id = :coop_id 
        AND period_id = :period_id 
        AND status IN ('draft', 'pending_guarantors', 'partially_guaranteed', 'submitted')
        LIMIT 1";
    
    $existingStmt = $db->prepare($existingQuery);
    $existingStmt->execute([
        ':coop_id' => $outstandingLoan['requester_coop_id'],
        ':period_id' => $newPeriodId
    ]);
    
    if ($existingStmt->fetch()) {
        throw new Exception('User already has a pending loan request for this period', 400);
    }

    $outstandingAmount = floatval($outstandingLoan['outstanding_amount']);
    $monthlyRepayment = $outstandingAmount * 0.10; // 10% of outstanding amount

    // Start transaction
    $db->beginTransaction();

    try {
        // Get admin username
        $adminUsername = $_SESSION['complete_name'] ?? 'Admin';
        
        // Create new loan request for the new period with outstanding amount
        // Auto-approve since it was already approved in the original period
        $insertQuery = "INSERT INTO loan_requests 
            (requester_coop_id, requested_amount, approved_amount, outstanding_amount, monthly_repayment, 
             staff_id, deduction_id, period_id, status, approved_by, approved_at, payslip_file_path)
            VALUES (:requester_coop_id, :requested_amount, :approved_amount, 0, :monthly_repayment,
                    :staff_id, :deduction_id, :period_id, 'approved', :approved_by, CURRENT_TIMESTAMP, NULL)";
        
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            ':requester_coop_id' => $outstandingLoan['requester_coop_id'],
            ':requested_amount' => $outstandingAmount,
            ':approved_amount' => $outstandingAmount, // Auto-approve the imported amount
            ':monthly_repayment' => $monthlyRepayment,
            ':staff_id' => $outstandingLoan['staff_id'],
            ':deduction_id' => $outstandingLoan['deduction_id'],
            ':period_id' => $newPeriodId,
            ':approved_by' => $adminUsername
        ]);

        $newLoanRequestId = $db->lastInsertId();

        // Update original loan to clear outstanding amount (mark as fully disbursed)
        $updateQuery = "UPDATE loan_requests 
            SET outstanding_amount = 0,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([':id' => $outstandingLoanId]);

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Outstanding loan imported successfully to new period',
            'data' => [
                'original_loan_id' => $outstandingLoanId,
                'new_loan_request_id' => $newLoanRequestId,
                'imported_amount' => $outstandingAmount,
                'new_period_id' => $newPeriodId
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}