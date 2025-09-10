<?php
// api/profile/get_salary_info.php

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../../config/Database.php';
    require_once '../../utils/JWTHandler.php';

    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $token = $jwt->validateToken($token);

    if (!$token) {
        throw new Exception('Invalid token', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    $staff_id = filter_var($_GET['staff_id'], FILTER_VALIDATE_INT);

    if (!$staff_id) {
        throw new Exception('Invalid staff ID', 400);
    }

    // Get current period
    $periodQuery = "SELECT periodId FROM payperiods WHERE active = 0 ORDER BY periodid DESC LIMIT 1";
    $periodStmt = $db->prepare($periodQuery);
    $periodStmt->execute();
    $periodId = $periodStmt->fetchColumn();

    if (!$periodId) {
        throw new Exception('No active pay period found', 404);
    }

    // Get salary information
    $salaryQuery = "SELECT 
        m.staff_id,
        SUM(CASE WHEN ed.code = 1 THEN m.allow ELSE 0 END) as total_earnings,
        ed.ed as description
    FROM tbl_master m
    LEFT JOIN tbl_earning_deduction ed ON m.allow_id = ed.ed_id
    WHERE m.staff_id = :staff_id 
    AND m.period = :period
    GROUP BY m.staff_id";

    $salaryStmt = $db->prepare($salaryQuery);
    $salaryStmt->execute([
        ':staff_id' => $staff_id,
        ':period' => $periodId
    ]);

    $salaryInfo = $salaryStmt->fetch(PDO::FETCH_ASSOC);

    if (!$salaryInfo) {
        throw new Exception('Salary information not found', 404);
    }

    // Get annual salary (monthly * 12)
    $annualSalary = $salaryInfo['total_earnings'] * 12;

    $response = [
        'success' => true,
        'data' => [
            'monthly_salary' => $salaryInfo['total_earnings'],
            'annual_salary' => $annualSalary,
            'period_id' => $periodId
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Salary info error: " . $e->getMessage());
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