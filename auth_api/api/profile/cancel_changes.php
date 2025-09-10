<?php
// api/profile/cancel_changes.php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['staff_id'])) {
        throw new Exception('Staff ID is required', 400);
    }

    $staff_id = $data['staff_id'];

    $database = new Database();
    $db = $database->getConnection();

    // Start transaction
    $db->beginTransaction();

    // Delete pending profile changes
    $profile_stmt = $db->prepare("
        DELETE FROM pending_profile_changes 
        WHERE staff_id = ? AND status = 'pending'
    ");
    $profile_stmt->execute([$staff_id]);

    // Delete pending qualification changes
    $qual_stmt = $db->prepare("
        DELETE FROM pending_qualification_changes 
        WHERE staff_id = ? AND status = 'pending'
    ");
    $qual_stmt->execute([$staff_id]);

    // Update employee status
    $update_stmt = $db->prepare("
        UPDATE employee 
        SET has_pending_changes = false 
        WHERE staff_id = ?
    ");
    $update_stmt->execute([$staff_id]);

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pending changes canceled successfully'
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }

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