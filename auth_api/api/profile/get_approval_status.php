<?php
// api/profile/get_approval_status.php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/Database.php';
require_once '../../utils/JWTHandler.php';

try {
    // Validate token
    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $jwt = new JWTHandler();
    $token_data = $jwt->validateToken($matches[1]);

    if (!$token_data) {
        throw new Exception('Invalid token', 401);
    }

    if (!isset($_GET['staff_id'])) {
        throw new Exception('Staff ID is required', 400);
    }

    $staff_id = $_GET['staff_id'];

    $database = new Database();
    $db = $database->getConnection();

    // First check if there are any pending changes
    $pending_check = $db->prepare("
        SELECT COUNT(*) as pending_count 
        FROM pending_profile_changes 
        WHERE staff_id = :staff_id 
        AND status = 'pending'
        UNION ALL
        SELECT COUNT(*) 
        FROM pending_qualification_changes 
        WHERE staff_id = :staff_id 
        AND status = 'pending'
    ");

    $pending_check->execute([':staff_id' => $staff_id]);
    $has_pending = false;
    while ($row = $pending_check->fetch(PDO::FETCH_ASSOC)) {
        if ($row['pending_count'] > 0) {
            $has_pending = true;
            break;
        }
    }

    if (!$has_pending) {
        // If no pending changes, return a properly structured response
        echo json_encode([
            'success' => true,
            'data' => [
                'status' => [
                    'status' => null,
                    'staff_id' => $staff_id,
                    'submitted_at' => null,
                    'submitted_by' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejection_reason' => null
                ],
                'changes' => []
            ]
        ]);
        exit;
    }

    // If there are pending changes, get the full status
    $query = "SELECT * FROM pending_changes_status WHERE staff_id = :staff_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':staff_id' => $staff_id]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get pending changes details
    $changes_query = "
        SELECT field_name, old_value, new_value, status
        FROM pending_profile_changes
        WHERE staff_id = :staff_id
        AND status = 'pending'
        UNION
        SELECT 
            'qualification' as field_name,
            NULL as old_value,
            CONCAT(change_type, ' qualification') as new_value,
            status
        FROM pending_qualification_changes
        WHERE staff_id = :staff_id
        AND status = 'pending'
    ";

    $changes_stmt = $db->prepare($changes_query);
    $changes_stmt->execute([':staff_id' => $staff_id]);
    $changes = $changes_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'status' => $status,
            'changes' => $changes
        ]
    ]);

} catch (Exception $e) {
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