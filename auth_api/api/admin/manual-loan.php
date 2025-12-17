<?php
// api/admin/manual-loan.php
// Admin API for manually creating loan requests

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
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
    $adminUsername = $_SESSION['complete_name'] ?? 'Admin';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        createManualLoan($db, $adminUsername);
    } else {
        throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Manual loan API error: " . $e->getMessage());
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

function createManualLoan($db, $adminUsername) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    $requester_coop_id = isset($input['requester_coop_id']) ? trim($input['requester_coop_id']) : '';
    $requested_amount = isset($input['requested_amount']) ? floatval($input['requested_amount']) : 0;
    $period_id = isset($input['period_id']) ? intval($input['period_id']) : null;
    $status = isset($input['status']) ? trim($input['status']) : 'draft';
    $approved_amount = isset($input['approved_amount']) ? floatval($input['approved_amount']) : null;
    $skip_guarantors = isset($input['skip_guarantors']) ? (bool)$input['skip_guarantors'] : false;
    $admin_notes = isset($input['admin_notes']) ? trim($input['admin_notes']) : null;
    $payslip_file_path = isset($input['payslip_file_path']) ? trim($input['payslip_file_path']) : null;
    $guarantors = isset($input['guarantors']) && is_array($input['guarantors']) ? $input['guarantors'] : [];

    // Validate required fields
    if (empty($requester_coop_id) || $requested_amount <= 0) {
        throw new Exception('requester_coop_id and requested_amount are required', 400);
    }
    
    if (!$period_id) {
        throw new Exception('period_id is required', 400);
    }

    // Validate status
    $validStatuses = ['draft', 'pending_guarantors', 'submitted', 'approved'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status. Must be one of: ' . implode(', ', $validStatuses), 400);
    }

    // If approved, validate approved_amount
    if ($status === 'approved') {
        if ($approved_amount === null || $approved_amount <= 0) {
            $approved_amount = $requested_amount; // Default to full approval
        }
        if ($approved_amount > $requested_amount) {
            throw new Exception('Approved amount cannot exceed requested amount', 400);
        }
    } else {
        $approved_amount = null;
    }

    // Calculate outstanding amount if partial approval
    $outstanding_amount = ($status === 'approved' && $approved_amount < $requested_amount) 
        ? ($requested_amount - $approved_amount) 
        : null;

    // Calculate monthly repayment (10% of approved amount if approved, otherwise 10% of requested)
    $base_amount = ($status === 'approved' && $approved_amount) ? $approved_amount : $requested_amount;
    $monthly_repayment = $base_amount * 0.10;

    // Verify member exists and get StaffID
    $memberQuery = "SELECT CoopID, StaffID FROM tblemployees WHERE CoopID = :coop_id AND Status = 'Active' LIMIT 1";
    $memberStmt = $db->prepare($memberQuery);
    $memberStmt->execute([':coop_id' => $requester_coop_id]);
    $memberData = $memberStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memberData) {
        throw new Exception('Member not found or inactive', 404);
    }
    
    $staff_id = isset($memberData['StaffID']) ? intval($memberData['StaffID']) : null;
    
    // Default deduction_id to 48 (cooperative deduction)
    $deduction_id = 48;

    // Check if user already has a pending request for this period (unless we're creating approved)
    if ($status !== 'approved') {
        $existingQuery = "SELECT id, status FROM loan_requests 
            WHERE requester_coop_id = :coop_id 
            AND period_id = :period_id 
            AND status IN ('draft', 'pending_guarantors', 'partially_guaranteed', 'submitted', 'approved')
            LIMIT 1";
        $existingStmt = $db->prepare($existingQuery);
        $existingStmt->execute([
            ':coop_id' => $requester_coop_id,
            ':period_id' => $period_id
        ]);
        $existingRequest = $existingStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRequest) {
            throw new Exception('Member already has a loan request for this period', 400);
        }
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Insert loan request
        $insertQuery = "INSERT INTO loan_requests 
            (requester_coop_id, requested_amount, approved_amount, outstanding_amount, monthly_repayment, 
             payslip_file_path, staff_id, deduction_id, period_id, status, approved_by, approved_at, submitted_at)
            VALUES (:requester_coop_id, :requested_amount, :approved_amount, :outstanding_amount, :monthly_repayment,
                    :payslip_file_path, :staff_id, :deduction_id, :period_id, :status, :approved_by, :approved_at, :submitted_at)";

        $approved_by = ($status === 'approved') ? $adminUsername : null;
        $approved_at = ($status === 'approved') ? date('Y-m-d H:i:s') : null;
        $submitted_at = ($status === 'submitted' || $status === 'approved') ? date('Y-m-d H:i:s') : null;

        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            ':requester_coop_id' => $requester_coop_id,
            ':requested_amount' => $requested_amount,
            ':approved_amount' => $approved_amount,
            ':outstanding_amount' => $outstanding_amount,
            ':monthly_repayment' => $monthly_repayment,
            ':payslip_file_path' => $payslip_file_path,
            ':staff_id' => $staff_id,
            ':deduction_id' => $deduction_id,
            ':period_id' => $period_id,
            ':status' => $status,
            ':approved_by' => $approved_by,
            ':approved_at' => $approved_at,
            ':submitted_at' => $submitted_at
        ]);

        $loanRequestId = $db->lastInsertId();

        // Get requester name for guarantor requests
        $requesterNameQuery = "SELECT CONCAT(FirstName, ' ', LastName) as full_name FROM tblemployees WHERE CoopID = :coop_id LIMIT 1";
        $requesterNameStmt = $db->prepare($requesterNameQuery);
        $requesterNameStmt->execute([':coop_id' => $requester_coop_id]);
        $requesterNameData = $requesterNameStmt->fetch(PDO::FETCH_ASSOC);
        $requesterName = $requesterNameData['full_name'] ?? 'Unknown';

        // Add guarantors if provided and not skipping
        if (!$skip_guarantors && !empty($guarantors) && $status !== 'approved') {
            // Validate guarantors
            if (count($guarantors) > 2) {
                throw new Exception('Maximum of 2 guarantors allowed', 400);
            }

            foreach ($guarantors as $guarantorCoopId) {
                $guarantorCoopId = trim($guarantorCoopId);
                
                if (empty($guarantorCoopId)) {
                    continue; // Skip empty guarantors
                }

                // Check if guarantor is different from requester
                if ($requester_coop_id === $guarantorCoopId) {
                    throw new Exception('Guarantor cannot be the same as the requester', 400);
                }

                // Verify guarantor exists and is active
                $guarantorQuery = "SELECT CoopID FROM tblemployees WHERE CoopID = :coop_id AND Status = 'Active' LIMIT 1";
                $guarantorStmt = $db->prepare($guarantorQuery);
                $guarantorStmt->execute([':coop_id' => $guarantorCoopId]);
                
                if ($guarantorStmt->rowCount() === 0) {
                    throw new Exception("Guarantor {$guarantorCoopId} not found or inactive", 404);
                }

                // Insert guarantor request
                $guarantorInsertQuery = "INSERT INTO guarantor_requests 
                    (loan_request_id, guarantor_coop_id, requester_name, requested_amount, monthly_repayment, status)
                    VALUES (:loan_request_id, :guarantor_coop_id, :requester_name, :requested_amount, :monthly_repayment, 'pending')";
                
                $guarantorInsertStmt = $db->prepare($guarantorInsertQuery);
                $guarantorInsertStmt->execute([
                    ':loan_request_id' => $loanRequestId,
                    ':guarantor_coop_id' => $guarantorCoopId,
                    ':requester_name' => $requesterName,
                    ':requested_amount' => $requested_amount,
                    ':monthly_repayment' => $monthly_repayment
                ]);

                // Send notification to guarantor
                sendGuarantorNotification($db, $guarantorCoopId, $requesterName, $requested_amount);
            }

            // Update loan status based on guarantor count
            if (count($guarantors) >= 2) {
                $updateStatusQuery = "UPDATE loan_requests SET status = 'pending_guarantors' WHERE id = :id";
                $updateStatusStmt = $db->prepare($updateStatusQuery);
                $updateStatusStmt->execute([':id' => $loanRequestId]);
            }
        }

        // If approved, send notification
        if ($status === 'approved') {
            sendLoanApprovalNotification($db, $requester_coop_id, $approved_amount, $requested_amount);
        }

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => $status === 'approved' 
                ? ($approved_amount < $requested_amount ? 'Loan partially approved and created successfully' : 'Loan fully approved and created successfully')
                : 'Loan request created successfully',
            'data' => [
                'id' => $loanRequestId,
                'requester_coop_id' => $requester_coop_id,
                'requested_amount' => $requested_amount,
                'approved_amount' => $approved_amount,
                'outstanding_amount' => $outstanding_amount,
                'status' => $status,
                'period_id' => $period_id
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function sendGuarantorNotification($db, $guarantorCoopId, $requesterName, $requestedAmount) {
    try {
        // Get guarantor's details
        $memberQuery = "SELECT CoopID, EmailAddress, MobileNumber, onesignal_id FROM tblemployees WHERE CoopID = :coop_id";
        $memberStmt = $db->prepare($memberQuery);
        $memberStmt->execute([':coop_id' => $guarantorCoopId]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            error_log("Guarantor not found: $guarantorCoopId");
            return;
        }

        $notificationTitle = 'Guarantor Request';
        $notificationMessage = "You have been requested to be a guarantor for a loan request from {$requesterName} for ₦" . number_format($requestedAmount, 2);

        // Save in-app notification
        $notifQuery = "INSERT INTO notifications (coop_id, title, message, status)
            VALUES (:coop_id, :title, :message, 'unread')";
        $notifStmt = $db->prepare($notifQuery);
        $notifStmt->execute([
            ':coop_id' => $guarantorCoopId,
            ':title' => $notificationTitle,
            ':message' => $notificationMessage
        ]);

        // Get OneSignal device ID
        $deviceId = null;
        $oneSignalQuery = "SELECT player_id FROM oneSignal WHERE coop_id = :coop_id LIMIT 1";
        $oneSignalStmt = $db->prepare($oneSignalQuery);
        $oneSignalStmt->execute([':coop_id' => $guarantorCoopId]);
        $oneSignal = $oneSignalStmt->fetch(PDO::FETCH_ASSOC);

        if ($oneSignal && !empty($oneSignal['player_id'])) {
            $deviceId = $oneSignal['player_id'];
        } elseif (!empty($member['onesignal_id'])) {
            $deviceId = $member['onesignal_id'];
        }

        // Send push notification if device ID is available
        if ($deviceId) {
            $possiblePaths = [
                __DIR__ . '/../../onesignal/send_notification.php',
                dirname(__DIR__, 2) . '/onesignal/send_notification.php',
            ];
            $notificationPath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $notificationPath = $path;
                    break;
                }
            }
            if ($notificationPath && function_exists('sendNotificationToDevice')) {
                require_once $notificationPath;
                sendNotificationToDevice($deviceId, $notificationTitle, $notificationMessage);
            }
        }
    } catch (Exception $e) {
        error_log("Error sending guarantor notification: " . $e->getMessage());
    }
}

function sendLoanApprovalNotification($db, $coopId, $approvedAmount, $requestedAmount) {
    try {
        // Get requester's details
        $memberQuery = "SELECT CoopID, EmailAddress, MobileNumber, onesignal_id FROM tblemployees WHERE CoopID = :coop_id";
        $memberStmt = $db->prepare($memberQuery);
        $memberStmt->execute([':coop_id' => $coopId]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            error_log("Requester not found: $coopId");
            return;
        }

        $notificationTitle = 'Loan Request Approved';
        if ($approvedAmount < $requestedAmount) {
            $notificationMessage = "Your loan request of ₦" . number_format($requestedAmount, 2) . 
                " has been partially approved. Approved amount: ₦" . number_format($approvedAmount, 2) . 
                ". Outstanding amount: ₦" . number_format($requestedAmount - $approvedAmount, 2) . 
                " can be requested in the next period.";
        } else {
            $notificationMessage = "Your loan request of ₦" . number_format($requestedAmount, 2) . " has been fully approved.";
        }

        // Save in-app notification
        $notifQuery = "INSERT INTO notifications (coop_id, title, message, status)
            VALUES (:coop_id, :title, :message, 'unread')";
        $notifStmt = $db->prepare($notifQuery);
        $notifStmt->execute([
            ':coop_id' => $coopId,
            ':title' => $notificationTitle,
            ':message' => $notificationMessage
        ]);

        // Get OneSignal device ID
        $deviceId = null;
        $oneSignalQuery = "SELECT player_id FROM oneSignal WHERE coop_id = :coop_id LIMIT 1";
        $oneSignalStmt = $db->prepare($oneSignalQuery);
        $oneSignalStmt->execute([':coop_id' => $coopId]);
        $oneSignal = $oneSignalStmt->fetch(PDO::FETCH_ASSOC);

        if ($oneSignal && !empty($oneSignal['player_id'])) {
            $deviceId = $oneSignal['player_id'];
        } elseif (!empty($member['onesignal_id'])) {
            $deviceId = $member['onesignal_id'];
        }

        // Send push notification if device ID is available
        if ($deviceId) {
            // Include notification helper
            $possiblePaths = [
                __DIR__ . '/../../onesignal/send_notification.php',
                dirname(__DIR__, 2) . '/onesignal/send_notification.php',
            ];
            $notificationPath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $notificationPath = $path;
                    break;
                }
            }
            if ($notificationPath && function_exists('sendNotificationToDevice')) {
                require_once $notificationPath;
                sendNotificationToDevice($deviceId, $notificationTitle, $notificationMessage);
            }
        }
    } catch (Exception $e) {
        error_log("Error sending loan approval notification: " . $e->getMessage());
    }
}