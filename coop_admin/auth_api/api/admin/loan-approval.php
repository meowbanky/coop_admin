<?php
// api/admin/loan-approval.php
// Admin API for approving loan requests

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
    
    // Try multiple paths for send_notification.php
    $possiblePaths = [
        __DIR__ . '/../../onesignal/send_notification.php',
        __DIR__ . '/../../../onesignal/send_notification.php',
        dirname(__DIR__, 2) . '/onesignal/send_notification.php',
    ];
    
    $notificationPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $notificationPath = $path;
            break;
        }
    }
    
    if ($notificationPath) {
        require_once $notificationPath;
    }

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
            getPendingLoanRequests($db);
            break;
        case 'POST':
            approveLoanRequest($db);
            break;
        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Loan approval API error: " . $e->getMessage());
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

function getPendingLoanRequests($db) {
    $status = isset($_GET['status']) ? $_GET['status'] : 'submitted';
    
    // Get pending loan requests (submitted or fully_guaranteed)
    $query = "SELECT 
        lr.id,
        lr.requester_coop_id,
        lr.requested_amount,
        lr.approved_amount,
        lr.outstanding_amount,
        lr.monthly_repayment,
        lr.payslip_file_path,
        lr.status,
        lr.period_id,
        lr.created_at,
        lr.submitted_at,
        CONCAT(e.FirstName, ' ', e.LastName) as requester_name,
        e.EmailAddress,
        e.MobileNumber,
        pp.PayrollPeriod as period_name,
        (SELECT COUNT(*) FROM guarantor_requests gr WHERE gr.loan_request_id = lr.id AND gr.status = 'approved') as approved_guarantors,
        (SELECT COUNT(*) FROM guarantor_requests gr WHERE gr.loan_request_id = lr.id AND gr.status = 'rejected') as rejected_guarantors,
        (SELECT COUNT(*) FROM guarantor_requests gr WHERE gr.loan_request_id = lr.id) as total_guarantors
    FROM loan_requests lr
    LEFT JOIN tblemployees e ON e.CoopID = lr.requester_coop_id
    LEFT JOIN tbpayrollperiods pp ON pp.id = lr.period_id
    WHERE lr.status IN ('submitted', 'fully_guaranteed')
    ORDER BY lr.submitted_at DESC, lr.created_at DESC";

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

    $loanRequests = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $periodId = $row['period_id'] ? intval($row['period_id']) : null;
        
        // Try to get period name from salary API first, then database, then fallback
        $periodName = null;
        if ($periodId && isset($periodMap[$periodId])) {
            $periodName = $periodMap[$periodId];
        } elseif (!empty($row['period_name'])) {
            $periodName = $row['period_name'];
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
        
        // Get guarantor details for this loan request
        $guarantorQuery = "SELECT 
            gr.guarantor_coop_id,
            gr.status as guarantor_status,
            CONCAT(g.FirstName, ' ', g.LastName) as guarantor_name
        FROM guarantor_requests gr
        LEFT JOIN tblemployees g ON g.CoopID = gr.guarantor_coop_id
        WHERE gr.loan_request_id = :loan_request_id
        ORDER BY gr.id";
        
        $guarantorStmt = $db->prepare($guarantorQuery);
        $guarantorStmt->execute([':loan_request_id' => $row['id']]);
        $guarantors = [];
        while ($guarantorRow = $guarantorStmt->fetch(PDO::FETCH_ASSOC)) {
            $guarantors[] = [
                'coop_id' => $guarantorRow['guarantor_coop_id'],
                'name' => $guarantorRow['guarantor_name'] ?? 'Unknown',
                'status' => $guarantorRow['guarantor_status']
            ];
        }
        
        $loanRequests[] = [
            'id' => intval($row['id']),
            'requester_coop_id' => $row['requester_coop_id'],
            'requester_name' => $row['requester_name'] ?? 'Unknown',
            'requested_amount' => floatval($row['requested_amount']),
            'approved_amount' => isset($row['approved_amount']) ? floatval($row['approved_amount']) : null,
            'outstanding_amount' => isset($row['outstanding_amount']) ? floatval($row['outstanding_amount']) : null,
            'monthly_repayment' => floatval($row['monthly_repayment']),
            'payslip_file_path' => $row['payslip_file_path'],
            'status' => $row['status'],
            'period_id' => $periodId,
            'period_name' => $periodName,
            'created_at' => $row['created_at'],
            'submitted_at' => $row['submitted_at'],
            'email' => $row['EmailAddress'],
            'mobile' => $row['MobileNumber'],
            'approved_guarantors' => intval($row['approved_guarantors']),
            'rejected_guarantors' => intval($row['rejected_guarantors']),
            'total_guarantors' => intval($row['total_guarantors']),
            'guarantors' => $guarantors
        ];
    }

    // Get summary statistics
    // Total submitted loans
    $submittedQuery = "SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(requested_amount), 0) as total_amount,
        period_id
    FROM loan_requests 
    WHERE status = 'submitted'
    GROUP BY period_id";
    
    $submittedStmt = $db->prepare($submittedQuery);
    $submittedStmt->execute();
    
    $submittedStats = [];
    while ($row = $submittedStmt->fetch(PDO::FETCH_ASSOC)) {
        $submittedStats[$row['period_id']] = [
            'count' => intval($row['total_count']),
            'total_amount' => floatval($row['total_amount'])
        ];
    }

    // Total pending guarantor approvals
    $pendingGuarantorQuery = "SELECT 
        COUNT(DISTINCT lr.id) as total_count,
        COALESCE(SUM(lr.requested_amount), 0) as total_amount,
        lr.period_id
    FROM loan_requests lr
    WHERE lr.status IN ('pending_guarantors', 'partially_guaranteed')
    GROUP BY lr.period_id";
    
    $pendingGuarantorStmt = $db->prepare($pendingGuarantorQuery);
    $pendingGuarantorStmt->execute();
    
    $pendingGuarantorStats = [];
    while ($row = $pendingGuarantorStmt->fetch(PDO::FETCH_ASSOC)) {
        $pendingGuarantorStats[$row['period_id']] = [
            'count' => intval($row['total_count']),
            'total_amount' => floatval($row['total_amount'])
        ];
    }

    // Get limits for all periods
    $limitsQuery = "SELECT period_id, limit_amount FROM loan_period_limits";
    $limitsStmt = $db->prepare($limitsQuery);
    $limitsStmt->execute();
    
    $limits = [];
    while ($row = $limitsStmt->fetch(PDO::FETCH_ASSOC)) {
        $limits[$row['period_id']] = floatval($row['limit_amount']);
    }

    // Get approved loans for each period
    $approvedQuery = "SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(requested_amount), 0) as total_amount,
        period_id
    FROM loan_requests 
    WHERE status = 'approved'
    GROUP BY period_id";
    
    $approvedStmt = $db->prepare($approvedQuery);
    $approvedStmt->execute();
    
    $approvedStats = [];
    while ($row = $approvedStmt->fetch(PDO::FETCH_ASSOC)) {
        $approvedStats[$row['period_id']] = [
            'count' => intval($row['total_count']),
            'total_amount' => floatval($row['total_amount'])
        ];
    }

    // Build period summaries
    $periodSummaries = [];
    $allPeriodIds = array_unique(array_merge(
        array_keys($submittedStats),
        array_keys($pendingGuarantorStats),
        array_keys($limits),
        array_keys($approvedStats)
    ));

    foreach ($allPeriodIds as $periodId) {
        // Try to get period name from salary API first (using the periodMap we already created)
        $periodName = null;
        if (isset($periodMap[$periodId])) {
            $periodName = $periodMap[$periodId];
        } else {
            // Fallback to database
            $periodQuery = "SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = :period_id LIMIT 1";
            $periodStmt = $db->prepare($periodQuery);
            $periodStmt->execute([':period_id' => $periodId]);
            $periodData = $periodStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!empty($periodData['PayrollPeriod'])) {
                $periodName = $periodData['PayrollPeriod'];
            } else {
                // Last resort: try API directly
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
        }
        
        // Final fallback
        if (empty($periodName)) {
            $periodName = 'Period ' . $periodId;
        }
        
        $submitted = $submittedStats[$periodId] ?? ['count' => 0, 'total_amount' => 0];
        $pendingGuarantor = $pendingGuarantorStats[$periodId] ?? ['count' => 0, 'total_amount' => 0];
        $approved = $approvedStats[$periodId] ?? ['count' => 0, 'total_amount' => 0];
        $limit = $limits[$periodId] ?? null;
        
        $totalSubmittedAndApproved = $submitted['total_amount'] + $approved['total_amount'];
        
        $periodSummaries[] = [
            'period_id' => $periodId,
            'period_name' => $periodName,
            'limit_amount' => $limit,
            'submitted_count' => $submitted['count'],
            'submitted_amount' => $submitted['total_amount'],
            'pending_guarantor_count' => $pendingGuarantor['count'],
            'pending_guarantor_amount' => $pendingGuarantor['total_amount'],
            'approved_count' => $approved['count'],
            'approved_amount' => $approved['total_amount'],
            'total_submitted_and_approved' => $totalSubmittedAndApproved,
            'remaining_limit' => $limit ? ($limit - $totalSubmittedAndApproved) : null,
            'usage_percentage' => $limit ? (($totalSubmittedAndApproved / $limit) * 100) : null
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $loanRequests,
        'summary' => [
            'periods' => $periodSummaries,
            'total_submitted' => array_sum(array_column($submittedStats, 'count')),
            'total_pending_guarantor' => array_sum(array_column($pendingGuarantorStats, 'count'))
        ]
    ]);
}

function approveLoanRequest($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    $loanRequestId = isset($input['loan_request_id']) ? intval($input['loan_request_id']) : null;
    $skipGuarantor = isset($input['skip_guarantor']) ? (bool)$input['skip_guarantor'] : false;
    $adminNotes = isset($input['admin_notes']) ? trim($input['admin_notes']) : null;
    $approvedAmount = isset($input['approved_amount']) ? floatval($input['approved_amount']) : null;

    if (!$loanRequestId) {
        throw new Exception('loan_request_id is required', 400);
    }
    
    // Get admin username
    $adminUsername = $_SESSION['complete_name'] ?? 'Admin';

    // Get loan request details
    $loanQuery = "SELECT 
        lr.id,
        lr.requester_coop_id,
        lr.requested_amount,
        lr.monthly_repayment,
        lr.status,
        lr.period_id,
        CONCAT(e.FirstName, ' ', e.LastName) as requester_name,
        e.EmailAddress,
        e.MobileNumber
    FROM loan_requests lr
    LEFT JOIN tblemployees e ON e.CoopID = lr.requester_coop_id
    WHERE lr.id = :id
    LIMIT 1";

    $loanStmt = $db->prepare($loanQuery);
    $loanStmt->execute([':id' => $loanRequestId]);
    $loanRequest = $loanStmt->fetch(PDO::FETCH_ASSOC);

    if (!$loanRequest) {
        throw new Exception('Loan request not found', 404);
    }

    if ($loanRequest['status'] === 'approved') {
        throw new Exception('Loan request is already approved', 400);
    }

    $requestedAmount = floatval($loanRequest['requested_amount']);
    
    // If approved_amount is not provided, default to requested_amount (100% approval)
    if ($approvedAmount === null || $approvedAmount <= 0) {
        $approvedAmount = $requestedAmount;
    }
    
    // Validate approved amount doesn't exceed requested amount
    if ($approvedAmount > $requestedAmount) {
        throw new Exception('Approved amount cannot exceed requested amount', 400);
    }
    
    // Calculate outstanding amount (if partial approval)
    $outstandingAmount = $requestedAmount - $approvedAmount;
    
    // Recalculate monthly repayment based on approved amount (10% of approved)
    $monthlyRepayment = $approvedAmount * 0.10;

    // Start transaction
    $db->beginTransaction();

    try {
        // Update loan request status to approved with approved amount
        $updateQuery = "UPDATE loan_requests 
            SET status = 'approved',
                approved_amount = :approved_amount,
                outstanding_amount = :outstanding_amount,
                monthly_repayment = :monthly_repayment,
                approved_by = :approved_by,
                approved_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([
            ':id' => $loanRequestId,
            ':approved_amount' => $approvedAmount,
            ':outstanding_amount' => $outstandingAmount,
            ':monthly_repayment' => $monthlyRepayment,
            ':approved_by' => $adminUsername
        ]);

        // If skipping guarantor, we don't auto-approve guarantor requests
        // They remain in their current state, but the loan is approved anyway

        // Save notification to database
        $notificationTitle = 'Loan Request Approved';
        if ($approvedAmount < $requestedAmount) {
            $notificationMessage = "Your loan request of ₦" . number_format($requestedAmount, 2) . 
                " has been partially approved. Approved amount: ₦" . number_format($approvedAmount, 2) . 
                ". Outstanding amount: ₦" . number_format($outstandingAmount, 2) . 
                " can be requested in the next period.";
        } else {
            $notificationMessage = "Your loan request of ₦" . number_format($requestedAmount, 2) . " has been fully approved.";
        }
        
        $saveNotificationQuery = "INSERT INTO notifications 
            (coop_id, title, message, status) 
            VALUES (:coop_id, :title, :message, 'unread')";
        
        $saveNotificationStmt = $db->prepare($saveNotificationQuery);
        $saveNotificationStmt->execute([
            ':coop_id' => $loanRequest['requester_coop_id'],
            ':title' => $notificationTitle,
            ':message' => $notificationMessage
        ]);

        // Get OneSignal device ID for push notification
        $deviceId = null;
        
        // Try to get from oneSignal table first
        $oneSignalQuery = "SELECT player_id FROM oneSignal WHERE coop_id = :coop_id LIMIT 1";
        $oneSignalStmt = $db->prepare($oneSignalQuery);
        $oneSignalStmt->execute([':coop_id' => $loanRequest['requester_coop_id']]);
        $oneSignal = $oneSignalStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oneSignal && !empty($oneSignal['player_id'])) {
            $deviceId = $oneSignal['player_id'];
        } else {
            // Fallback to onesignal_id from tblemployees table
            $memberQuery = "SELECT onesignal_id FROM tblemployees WHERE CoopID = :coop_id LIMIT 1";
            $memberStmt = $db->prepare($memberQuery);
            $memberStmt->execute([':coop_id' => $loanRequest['requester_coop_id']]);
            $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member && !empty($member['onesignal_id'])) {
                $deviceId = $member['onesignal_id'];
            }
        }

        // Send push notification if device ID is available and function exists
        if ($deviceId && function_exists('sendNotificationToDevice')) {
            sendNotificationToDevice($deviceId, $notificationTitle, $notificationMessage);
        }

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => $approvedAmount < $requestedAmount 
                ? 'Loan request partially approved successfully' 
                : 'Loan request fully approved successfully',
            'data' => [
                'loan_request_id' => $loanRequestId,
                'status' => 'approved',
                'requested_amount' => $requestedAmount,
                'approved_amount' => $approvedAmount,
                'outstanding_amount' => $outstandingAmount,
                'skip_guarantor' => $skipGuarantor
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}