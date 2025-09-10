<?php
// api/profile/get_change_history.php

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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $offset = ($page - 1) * $per_page;

    $database = new Database();
    $db = $database->getConnection();

    // Get total count
    $count_stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM profile_change_log 
        WHERE staff_id = :staff_id
    ");
    $count_stmt->execute([':staff_id' => $staff_id]);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get changes with pagination
    $query = "
        SELECT 
            pcl.*,
            e.NAME as changed_by_name
        FROM profile_change_log pcl
        LEFT JOIN employee e ON e.staff_id = pcl.changed_by
        WHERE pcl.staff_id = :staff_id
        ORDER BY pcl.changed_at DESC
        LIMIT :offset, :limit
    ";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':staff_id', $staff_id, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->execute();

    $changes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'changes' => $changes,
            'pagination' => [
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total / $per_page)
            ]
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