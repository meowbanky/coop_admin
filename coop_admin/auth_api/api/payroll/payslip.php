<?php
// api/payroll/payslip.php

// Clear any previous output and start fresh
ob_clean();

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Include required files
    require_once '../../config/Database.php';
    require_once '../../utils/JWTHandler.php';

    // Get JWT token from header
    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];

    // Verify token
    $jwt = new JWTHandler();
    $token = $jwt->validateToken($token);

    if (!$token) {
        throw new Exception('Invalid token', 401);
    }

    // Get period from query params (optional)
    $periodId = isset($_GET['periodId']) ? filter_var($_GET['periodId'], FILTER_VALIDATE_INT) : null;
    $user_id = isset($_GET['userId']) ? filter_var($_GET['userId'], FILTER_VALIDATE_INT) : null;

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // First get employee details
    $employeeQuery = "SELECT
	tbl_bank.BNAME, 
	master_staff.`NAME`, 
	master_staff.staff_id, 
	master_staff.ACCTNO, 
	master_staff.GRADE, 
	master_staff.STEP, 
	tbl_dept.dept,tbl_salaryType.SalaryType
FROM
	master_staff
	LEFT JOIN
	employee
	ON 
		master_staff.staff_id = employee.staff_id
	LEFT JOIN
	tbl_dept
	ON 
		master_staff.DEPTCD = tbl_dept.dept_id
	LEFT JOIN
	tbl_bank
	ON 
		master_staff.BCODE = tbl_bank.BCODE
	LEFT JOIN
	tbl_salaryType 
	ON employee.CATEGORY = tbl_salaryType.salaryType_id  
    WHERE master_staff.staff_id = :staff_id AND master_staff.period = :period";

    $empStmt = $db->prepare($employeeQuery);
    $empStmt->bindParam(':staff_id', $user_id);
    $empStmt->bindParam(':period', $periodId);
    $empStmt->execute();

    $employee = $empStmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Employee not found', 404);
    }

    // Get current period if not specified
    if (!$periodId) {
        $periodQuery = "SELECT periodId FROM master_staff WHERE staff_id = :staff_id ORDER BY periodId DESC LIMIT 1";
        $periodStmt = $db->prepare($periodQuery);
        $periodStmt->bindParam(':staff_id', $user_id);
        $periodStmt->execute();
        $periodId = $periodStmt->fetchColumn();

        if (!$periodId) {
            throw new Exception('No payroll period found', 404);
        }
    }

    // Get earnings and deductions
    $masterQuery = "SELECT m.*, ed.ed as description, ed.code as type 
                   FROM tbl_master m
                   LEFT JOIN tbl_earning_deduction ed ON m.allow_id = ed.ed_id
                   WHERE m.staff_id = :staff_id 
                   AND m.period = :period
                   ORDER BY ed.ed_id";

    $masterStmt = $db->prepare($masterQuery);
    $masterStmt->bindParam(':staff_id', $user_id);
    $masterStmt->bindParam(':period', $periodId);
    $masterStmt->execute();

    $earnings = [];
    $deductions = [];
    $totalEarnings = 0;
    $totalDeductions = 0;

    while ($row = $masterStmt->fetch(PDO::FETCH_ASSOC)) {
        $item = [
            'description' => $row['description'],
            'amount' => $row['type'] == 1 ? $row['allow'] : $row['deduc']
        ];

        if ($row['type'] == 1) { // Earnings
            $earnings[] = $item;
            $totalEarnings += $row['allow'];
        } else { // Deductions
            $deductions[] = $item;
            $totalDeductions += $row['deduc'];
        }
    }

    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'employeeInfo' => [
                'name' => $employee['NAME'],
                'staffId' => $employee['staff_id'],
                'department' => $employee['dept'],
//                'grade' => $employee['GRADE'],
                'grade_step' => $employee['GRADE'].'/'.$employee['STEP'],
                'bank' => $employee['BNAME'],
                'accountno' => $employee['ACCTNO'],
                'salarytype' => $employee['SalaryType'],
            ],
            'payrollInfo' => [
                'periodId' => $periodId,
                'earnings' => $earnings,
                'deductions' => $deductions,
                'totalEarnings' => $totalEarnings,
                'totalDeductions' => $totalDeductions,
                'netPay' => $totalEarnings - $totalDeductions
            ]
        ]
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Payslip error: " . $e->getMessage());
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