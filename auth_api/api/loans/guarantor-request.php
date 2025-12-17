<?php
// api/loans/guarantor-request.php
// Send guarantor requests and manage guarantor responses

// Clean any previous output and start buffering
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Disable error display, log errors instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    ob_end_flush();
    exit();
}

try {
    // Suppress warnings from required files to prevent HTML output
    $oldErrorReporting = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
    $oldDisplayErrors = ini_get('display_errors');
    ini_set('display_errors', 0);
    
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../utils/JWTHandler.php';
    
    // Try multiple paths for send_notification.php
    $possiblePaths = [
        __DIR__ . '/../../onesignal/send_notification.php',  // Standard path: auth_api/api/loans -> auth_api/onesignal
        __DIR__ . '/../../../onesignal/send_notification.php', // Alternative: if structure is different
        dirname(__DIR__, 2) . '/onesignal/send_notification.php', // Using dirname() for better path resolution
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
    } else {
        // Log warning but don't fail - notifications are optional
        error_log("Warning: send_notification.php not found at expected paths. Tried: " . implode(', ', $possiblePaths));
    }
    
    ini_set('display_errors', $oldDisplayErrors);
    error_reporting($oldErrorReporting);

    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $decodedToken = $jwt->validateToken($token);

    if (!$decodedToken) {
        throw new Exception('Invalid token', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            sendGuarantorRequest($db);
            break;
        case 'GET':
            getGuarantorRequests($db);
            break;
        case 'PUT':
            respondToGuarantorRequest($db);
            break;
        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    // Clean output buffer before sending error
    ob_clean();
    
    error_log("Guarantor request error: " . $e->getMessage());
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
    ob_end_flush();
    exit();
} catch (Error $e) {
    // Catch PHP 7+ errors (TypeError, ParseError, etc.)
    ob_clean();
    
    error_log("Guarantor request PHP error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
    ob_end_flush();
    exit();
}

function sendGuarantorRequest($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    $loan_request_id = isset($input['loan_request_id']) ? intval($input['loan_request_id']) : 0;
    $guarantor_coop_id = isset($input['guarantor_coop_id']) ? trim($input['guarantor_coop_id']) : '';

    if (!$loan_request_id || empty($guarantor_coop_id)) {
        throw new Exception('loan_request_id and guarantor_coop_id are required', 400);
    }

    // Get loan request details
    $loanQuery = "SELECT lr.*, 
        CONCAT(e.FirstName, ' ', IFNULL(e.MiddleName, ''), ' ', e.LastName) as requester_name
    FROM loan_requests lr
    JOIN tblemployees e ON lr.requester_coop_id = e.CoopID
    WHERE lr.id = :loan_request_id";
    
    $loanStmt = $db->prepare($loanQuery);
    $loanStmt->execute([':loan_request_id' => $loan_request_id]);
    $loanRequest = $loanStmt->fetch(PDO::FETCH_ASSOC);

    if (!$loanRequest) {
        throw new Exception('Loan request not found', 404);
    }

    // Check if guarantor is different from requester
    if ($loanRequest['requester_coop_id'] === $guarantor_coop_id) {
        throw new Exception('Guarantor cannot be the same as the requester', 400);
    }

    // Check if guarantor exists and is active
    $guarantorQuery = "SELECT CoopID FROM tblemployees WHERE CoopID = :coop_id AND Status = 'Active'";
    $guarantorStmt = $db->prepare($guarantorQuery);
    $guarantorStmt->execute([':coop_id' => $guarantor_coop_id]);
    
    if ($guarantorStmt->rowCount() === 0) {
        throw new Exception('Guarantor not found or inactive', 404);
    }

    // Check loan request status - prevent sending if already submitted
    if ($loanRequest['status'] === 'submitted') {
        throw new Exception('Loan request has already been submitted and cannot be modified', 400);
    }

    // Check if guarantor request already exists with pending or approved status
    $checkQuery = "SELECT id, status FROM guarantor_requests 
        WHERE loan_request_id = :loan_request_id AND guarantor_coop_id = :guarantor_coop_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([
        ':loan_request_id' => $loan_request_id,
        ':guarantor_coop_id' => $guarantor_coop_id
    ]);
    $existingRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRequest) {
        // If already approved, cannot replace
        if ($existingRequest['status'] === 'approved') {
            throw new Exception('Cannot replace an approved guarantor', 400);
        }
        // If pending, cannot resend
        if ($existingRequest['status'] === 'pending') {
            throw new Exception('Guarantor request already pending', 400);
        }
        // If rejected, allow replacement - delete old rejected request
        if ($existingRequest['status'] === 'rejected') {
            $deleteQuery = "DELETE FROM guarantor_requests WHERE id = :id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([':id' => $existingRequest['id']]);
        }
    }

    // Edge case: Check if trying to replace an approved guarantor
    // Get all guarantor requests for this loan
    $allGuarantorsQuery = "SELECT guarantor_coop_id, status FROM guarantor_requests 
        WHERE loan_request_id = :loan_request_id AND status = 'approved'";
    $allGuarantorsStmt = $db->prepare($allGuarantorsQuery);
    $allGuarantorsStmt->execute([':loan_request_id' => $loan_request_id]);
    $approvedGuarantors = $allGuarantorsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($approvedGuarantors as $approved) {
        if ($approved['guarantor_coop_id'] === $guarantor_coop_id) {
            throw new Exception('Cannot replace an approved guarantor', 400);
        }
    }

    // Edge case: Check maximum guarantors (should be 2)
    // Count total pending + approved guarantors (excluding rejected ones that will be replaced)
    $countQuery = "SELECT COUNT(*) as total FROM guarantor_requests 
        WHERE loan_request_id = :loan_request_id 
        AND status IN ('pending', 'approved')";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute([':loan_request_id' => $loan_request_id]);
    $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
    $currentCount = intval($countData['total']);

    // If we're replacing a rejected guarantor, the count won't include it (already deleted above)
    // So we need to check if adding this new one would exceed 2
    if ($currentCount >= 2) {
        throw new Exception('Maximum of 2 guarantors allowed. Please replace an existing guarantor first.', 400);
    }

    // Insert guarantor request
    error_log("Inserting guarantor request - Loan Request ID: $loan_request_id, Guarantor CoopID: $guarantor_coop_id");
    
    $insertQuery = "INSERT INTO guarantor_requests 
        (loan_request_id, guarantor_coop_id, requester_name, requested_amount, monthly_repayment, status)
        VALUES (:loan_request_id, :guarantor_coop_id, :requester_name, :requested_amount, :monthly_repayment, 'pending')";

    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([
        ':loan_request_id' => $loan_request_id,
        ':guarantor_coop_id' => $guarantor_coop_id,  // IMPORTANT: This is the guarantor's CoopID
        ':requester_name' => $loanRequest['requester_name'],
        ':requested_amount' => $loanRequest['requested_amount'],
        ':monthly_repayment' => $loanRequest['monthly_repayment']
    ]);

    $guarantorRequestId = $db->lastInsertId();
    error_log("Guarantor request inserted successfully - ID: $guarantorRequestId, Guarantor CoopID: $guarantor_coop_id");

    // Update loan request status
    updateLoanRequestStatus($db, $loan_request_id);

    // Send notification to guarantor (NOT the requester)
    error_log("Sending guarantor notification - Guarantor CoopID: $guarantor_coop_id, Requester: {$loanRequest['requester_name']}");
    sendGuarantorNotification($db, $guarantor_coop_id, $loanRequest['requester_name'], $loanRequest['requested_amount']);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Guarantor request sent successfully',
        'data' => [
            'id' => $guarantorRequestId,
            'loan_request_id' => $loan_request_id,
            'guarantor_coop_id' => $guarantor_coop_id,
            'status' => 'pending'
        ]
    ]);
    ob_end_flush();
    exit();
}

function respondToGuarantorRequest($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    $guarantor_request_id = isset($input['guarantor_request_id']) ? intval($input['guarantor_request_id']) : 0;
    $response = isset($input['response']) ? trim($input['response']) : ''; // 'approved' or 'rejected'
    $response_notes = isset($input['response_notes']) ? trim($input['response_notes']) : null;

    if (!$guarantor_request_id || !in_array($response, ['approved', 'rejected'])) {
        throw new Exception('guarantor_request_id and valid response (approved/rejected) are required', 400);
    }

    // Get guarantor request details
    $grQuery = "SELECT gr.*, lr.requester_coop_id 
        FROM guarantor_requests gr
        JOIN loan_requests lr ON gr.loan_request_id = lr.id
        WHERE gr.id = :guarantor_request_id";
    
    $grStmt = $db->prepare($grQuery);
    $grStmt->execute([':guarantor_request_id' => $guarantor_request_id]);
    $guarantorRequest = $grStmt->fetch(PDO::FETCH_ASSOC);

    if (!$guarantorRequest) {
        throw new Exception('Guarantor request not found', 404);
    }

    // Update guarantor request
    $updateQuery = "UPDATE guarantor_requests 
        SET status = :status, 
            response_date = NOW(),
            response_notes = :response_notes,
            updated_at = NOW()
        WHERE id = :guarantor_request_id";

    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([
        ':status' => $response,
        ':response_notes' => $response_notes,
        ':guarantor_request_id' => $guarantor_request_id
    ]);

    // Update loan request status
    $loanRequestId = $guarantorRequest['loan_request_id'];
    updateLoanRequestStatus($db, $loanRequestId);

    // Send notification to requester
    sendRequesterNotification($db, $guarantorRequest['requester_coop_id'], $guarantorRequest['requester_name'], $response);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Guarantor request ' . $response . ' successfully',
        'data' => [
            'guarantor_request_id' => $guarantor_request_id,
            'response' => $response
        ]
    ]);
    ob_end_flush();
    exit();
}

function getGuarantorRequests($db) {
    $guarantor_coop_id = isset($_GET['guarantor_coop_id']) ? trim($_GET['guarantor_coop_id']) : null;
    $loan_request_id = isset($_GET['loan_request_id']) ? intval($_GET['loan_request_id']) : null;

    error_log("getGuarantorRequests called - guarantor_coop_id: $guarantor_coop_id, loan_request_id: $loan_request_id");

    if (!$guarantor_coop_id && !$loan_request_id) {
        throw new Exception('Either guarantor_coop_id or loan_request_id is required', 400);
    }

    $query = "SELECT 
        gr.id,
        gr.loan_request_id,
        gr.guarantor_coop_id,
        gr.requester_name,
        gr.requested_amount,
        gr.monthly_repayment,
        gr.status,
        gr.response_date,
        gr.response_notes,
        gr.created_at,
        gr.updated_at,
        lr.requester_coop_id,
        lr.status as loan_status
    FROM guarantor_requests gr
    JOIN loan_requests lr ON gr.loan_request_id = lr.id";

    $params = [];
    if ($guarantor_coop_id) {
        $query .= " WHERE gr.guarantor_coop_id = :guarantor_coop_id";
        $params[':guarantor_coop_id'] = $guarantor_coop_id;
        error_log("Querying for guarantor_coop_id: $guarantor_coop_id");
    } else {
        $query .= " WHERE gr.loan_request_id = :loan_request_id";
        $params[':loan_request_id'] = $loan_request_id;
        error_log("Querying for loan_request_id: $loan_request_id");
    }

    $query .= " ORDER BY gr.created_at DESC";

    error_log("Executing query: $query");
    error_log("With params: " . json_encode($params));

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $guarantorRequests = [];
    $rowCount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rowCount++;
        error_log("Found guarantor request #$rowCount - ID: {$row['id']}, Guarantor CoopID: {$row['guarantor_coop_id']}, Status: {$row['status']}");
        $guarantorRequests[] = [
            'id' => intval($row['id']),
            'loan_request_id' => intval($row['loan_request_id']),
            'guarantor_coop_id' => $row['guarantor_coop_id'],
            'requester_name' => $row['requester_name'],
            'requested_amount' => floatval($row['requested_amount']),
            'monthly_repayment' => floatval($row['monthly_repayment']),
            'status' => $row['status'],
            'response_date' => $row['response_date'],
            'response_notes' => $row['response_notes'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'loan_status' => $row['loan_status']
        ];
    }
    
    error_log("Total guarantor requests found: " . count($guarantorRequests));

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $guarantorRequests
    ]);
    ob_end_flush();
    exit();
}

function updateLoanRequestStatus($db, $loanRequestId) {
    // Count guarantor statuses
    $statusQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM guarantor_requests
    WHERE loan_request_id = :loan_request_id";
    
    $statusStmt = $db->prepare($statusQuery);
    $statusStmt->execute([':loan_request_id' => $loanRequestId]);
    $statusData = $statusStmt->fetch(PDO::FETCH_ASSOC);

    $total = intval($statusData['total']);
    $approved = intval($statusData['approved']);
    $rejected = intval($statusData['rejected']);
    $pending = intval($statusData['pending']);

    $newStatus = 'pending_guarantors';
    
    // Only set to 'submitted' when BOTH guarantors approve
    if ($approved == 2 && $total == 2) {
        // Validate against loan period limit before submitting
        $loanQuery = "SELECT requested_amount, period_id, requester_coop_id FROM loan_requests WHERE id = :loan_request_id LIMIT 1";
        $loanStmt = $db->prepare($loanQuery);
        $loanStmt->execute([':loan_request_id' => $loanRequestId]);
        $loanData = $loanStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($loanData) {
            $periodId = $loanData['period_id'];
            $requestedAmount = floatval($loanData['requested_amount']);
            $requesterCoopId = $loanData['requester_coop_id'];
            
            // Check if limit exists for this period
            $limitQuery = "SELECT limit_amount FROM loan_period_limits WHERE period_id = :period_id LIMIT 1";
            $limitStmt = $db->prepare($limitQuery);
            $limitStmt->execute([':period_id' => $periodId]);
            $limitData = $limitStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($limitData) {
                $limitAmount = floatval($limitData['limit_amount']);
                
                // Calculate current total loans for this period (submitted + approved)
                $totalLoansQuery = "SELECT COALESCE(SUM(requested_amount), 0) as total 
                    FROM loan_requests 
                    WHERE period_id = :period_id 
                    AND status IN ('submitted', 'approved')
                    AND id != :exclude_id";
                $totalLoansStmt = $db->prepare($totalLoansQuery);
                $totalLoansStmt->execute([
                    ':period_id' => $periodId,
                    ':exclude_id' => $loanRequestId
                ]);
                $totalLoansData = $totalLoansStmt->fetch(PDO::FETCH_ASSOC);
                $currentTotal = floatval($totalLoansData['total']);
                
                // Check if adding this loan would exceed the limit
                if (($currentTotal + $requestedAmount) > $limitAmount) {
                    // Reject the loan request due to limit exceeded
                    $rejectQuery = "UPDATE loan_requests 
                        SET status = 'rejected', 
                            updated_at = NOW() 
                        WHERE id = :loan_request_id";
                    $rejectStmt = $db->prepare($rejectQuery);
                    $rejectStmt->execute([':loan_request_id' => $loanRequestId]);
                    
                    // Send rejection notification
                    sendLimitExceededNotification($db, $requesterCoopId, $requestedAmount, $limitAmount, $currentTotal);
                    
                    throw new Exception(
                        "Loan request rejected: Total loan amount for this period would exceed the admin-set limit of ₦" . 
                        number_format($limitAmount, 2) . ". Current total: ₦" . number_format($currentTotal, 2) . 
                        ", Your request: ₦" . number_format($requestedAmount, 2) . 
                        ", Total would be: ₦" . number_format($currentTotal + $requestedAmount, 2)
                    );
                }
            }
        }
        
        $newStatus = 'submitted';
        // Set submitted_at timestamp
        $updateQuery = "UPDATE loan_requests 
            SET status = :status, 
                submitted_at = NOW(),
                updated_at = NOW() 
            WHERE id = :loan_request_id";
    } elseif ($approved == 1 && $rejected == 1) {
        // One approved, one rejected - allow replacement of rejected one
        $newStatus = 'partially_guaranteed';
        $updateQuery = "UPDATE loan_requests SET status = :status, updated_at = NOW() WHERE id = :loan_request_id";
    } elseif ($rejected == 2) {
        // Both rejected - allow replacement of both
        $newStatus = 'rejected';
        $updateQuery = "UPDATE loan_requests SET status = :status, updated_at = NOW() WHERE id = :loan_request_id";
    } elseif ($approved == 1 && $pending == 1) {
        // One approved, one pending
        $newStatus = 'partially_guaranteed';
        $updateQuery = "UPDATE loan_requests SET status = :status, updated_at = NOW() WHERE id = :loan_request_id";
    } elseif ($rejected == 1 && $pending == 1) {
        // One rejected, one pending
        $newStatus = 'pending_guarantors';
        $updateQuery = "UPDATE loan_requests SET status = :status, updated_at = NOW() WHERE id = :loan_request_id";
    } else {
        // Default: pending guarantors
        $updateQuery = "UPDATE loan_requests SET status = :status, updated_at = NOW() WHERE id = :loan_request_id";
    }

    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([
        ':status' => $newStatus,
        ':loan_request_id' => $loanRequestId
    ]);
}

function sendGuarantorNotification($db, $guarantorCoopId, $requesterName, $requestedAmount) {
    try {
        error_log("sendGuarantorNotification called - Guarantor CoopID: $guarantorCoopId");
        
        // Get guarantor's email, mobile, and OneSignal ID
        // Try both oneSignal table and tblemployees.onesignal_id field
        $memberQuery = "SELECT CoopID, EmailAddress, MobileNumber, onesignal_id FROM tblemployees WHERE CoopID = :coop_id";
        $memberStmt = $db->prepare($memberQuery);
        $memberStmt->execute([':coop_id' => $guarantorCoopId]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            error_log("Guarantor not found: $guarantorCoopId");
            return;
        }

        error_log("Guarantor found - CoopID: {$member['CoopID']}, Email: {$member['EmailAddress']}");

        $message = "You have been requested to be a guarantor for a loan request from {$requesterName} for ₦" . number_format($requestedAmount, 2);
        
        // Save in-app notification FOR THE GUARANTOR (not the requester)
        $notifQuery = "INSERT INTO notifications (coop_id, title, message, status) 
            VALUES (:coop_id, 'Guarantor Request', :message, 'unread')";
        $notifStmt = $db->prepare($notifQuery);
        $notifStmt->execute([
            ':coop_id' => $guarantorCoopId,  // IMPORTANT: This should be the guarantor's CoopID
            ':message' => $message
        ]);
        
        error_log("Notification saved for guarantor CoopID: $guarantorCoopId");

        // Try to get OneSignal player_id from oneSignal table first
        $playerId = null;
        $oneSignalQuery = "SELECT player_id FROM oneSignal WHERE coop_id = :coop_id LIMIT 1";
        $oneSignalStmt = $db->prepare($oneSignalQuery);
        $oneSignalStmt->execute([':coop_id' => $guarantorCoopId]);  // IMPORTANT: Using guarantor's CoopID
        $oneSignal = $oneSignalStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oneSignal && !empty($oneSignal['player_id'])) {
            $playerId = $oneSignal['player_id'];
            error_log("Found OneSignal player_id from oneSignal table for guarantor $guarantorCoopId: $playerId");
        } elseif (!empty($member['onesignal_id'])) {
            // Fallback to onesignal_id from tblemployees table
            $playerId = $member['onesignal_id'];
            error_log("Found OneSignal player_id from tblemployees for guarantor $guarantorCoopId: $playerId");
        }

        // Send push notification via OneSignal if player_id is available and function exists
        if ($playerId && function_exists('sendNotificationToDevice')) {
            error_log("Sending OneSignal push notification to guarantor $guarantorCoopId with player_id: $playerId");
            sendNotificationToDevice($playerId, 'Guarantor Request', $message);
        } else {
            if (!$playerId) {
                error_log("No OneSignal player_id found for guarantor: $guarantorCoopId");
            }
            if (!function_exists('sendNotificationToDevice')) {
                error_log("sendNotificationToDevice function not available");
            }
        }

    } catch (Exception $e) {
        error_log("Failed to send guarantor notification: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}

function sendRequesterNotification($db, $requesterCoopId, $requesterName, $response) {
    try {
        $message = $response === 'approved' 
            ? "A guarantor has approved your loan request for {$requesterName}"
            : "A guarantor has rejected your loan request for {$requesterName}. You may need to select another guarantor.";
        
        // Save in-app notification
        $notifQuery = "INSERT INTO notifications (coop_id, title, message, status) 
            VALUES (:coop_id, 'Loan Request Update', :message, 'unread')";
        $notifStmt = $db->prepare($notifQuery);
        $notifStmt->execute([
            ':coop_id' => $requesterCoopId,
            ':message' => $message
        ]);

        // Try to get OneSignal player_id from oneSignal table first
        $playerId = null;
        $oneSignalQuery = "SELECT player_id FROM oneSignal WHERE coop_id = :coop_id LIMIT 1";
        $oneSignalStmt = $db->prepare($oneSignalQuery);
        $oneSignalStmt->execute([':coop_id' => $requesterCoopId]);
        $oneSignal = $oneSignalStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oneSignal && !empty($oneSignal['player_id'])) {
            $playerId = $oneSignal['player_id'];
        } else {
            // Fallback to onesignal_id from tblemployees table
            $memberQuery = "SELECT onesignal_id FROM tblemployees WHERE CoopID = :coop_id LIMIT 1";
            $memberStmt = $db->prepare($memberQuery);
            $memberStmt->execute([':coop_id' => $requesterCoopId]);
            $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member && !empty($member['onesignal_id'])) {
                $playerId = $member['onesignal_id'];
            }
        }

        // Send push notification via OneSignal if player_id is available and function exists
        if ($playerId && function_exists('sendNotificationToDevice')) {
            sendNotificationToDevice($playerId, 'Loan Request Update', $message);
        } else {
            if (!$playerId) {
                error_log("No OneSignal player_id found for requester: $requesterCoopId");
            }
        }

    } catch (Exception $e) {
        error_log("Failed to send requester notification: " . $e->getMessage());
    }
}

function sendLimitExceededNotification($db, $requesterCoopId, $requestedAmount, $limitAmount, $currentTotal) {
    try {
        // Get requester's details
        $memberQuery = "SELECT CoopID, EmailAddress, MobileNumber, onesignal_id FROM tblemployees WHERE CoopID = :coop_id";
        $memberStmt = $db->prepare($memberQuery);
        $memberStmt->execute([':coop_id' => $requesterCoopId]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            error_log("Requester not found: $requesterCoopId");
            return;
        }

        $notificationTitle = 'Loan Request Rejected';
        $notificationMessage = "Your loan request of ₦" . number_format($requestedAmount, 2) . 
            " has been rejected because it would exceed the monthly loan limit of ₦" . number_format($limitAmount, 2) . 
            ". Current total loans: ₦" . number_format($currentTotal, 2);
        
        // Save in-app notification
        $notifQuery = "INSERT INTO notifications (coop_id, title, message, status) 
            VALUES (:coop_id, :title, :message, 'unread')";
        $notifStmt = $db->prepare($notifQuery);
        $notifStmt->execute([
            ':coop_id' => $requesterCoopId,
            ':title' => $notificationTitle,
            ':message' => $notificationMessage
        ]);

        // Get OneSignal device ID
        $deviceId = null;
        
        // Try to get from oneSignal table first
        $oneSignalQuery = "SELECT player_id FROM oneSignal WHERE coop_id = :coop_id LIMIT 1";
        $oneSignalStmt = $db->prepare($oneSignalQuery);
        $oneSignalStmt->execute([':coop_id' => $requesterCoopId]);
        $oneSignal = $oneSignalStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oneSignal && !empty($oneSignal['player_id'])) {
            $deviceId = $oneSignal['player_id'];
        } elseif (!empty($member['onesignal_id'])) {
            $deviceId = $member['onesignal_id'];
        }

        // Send push notification if device ID is available
        if ($deviceId && function_exists('sendNotificationToDevice')) {
            sendNotificationToDevice($deviceId, $notificationTitle, $notificationMessage);
        }
    } catch (Exception $e) {
        error_log("Error sending limit exceeded notification: " . $e->getMessage());
    }
}