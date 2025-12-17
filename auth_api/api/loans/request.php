<?php
// api/loans/request.php
// Create and manage loan requests

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
    $decodedToken = $jwt->validateToken($token);

    if (!$decodedToken) {
        throw new Exception('Invalid token', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            createLoanRequest($db);
            break;
        case 'GET':
            getLoanRequests($db);
            break;
        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Loan request error: " . $e->getMessage());
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

function createLoanRequest($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    $requester_coop_id = isset($input['requester_coop_id']) ? trim($input['requester_coop_id']) : '';
    $requested_amount = isset($input['requested_amount']) ? floatval($input['requested_amount']) : 0;
    $staff_id = isset($input['staff_id']) ? intval($input['staff_id']) : null;
    $deduction_id = isset($input['deduction_id']) ? intval($input['deduction_id']) : null;
    $payslip_file_path = isset($input['payslip_file_path']) ? trim($input['payslip_file_path']) : null;
    $period_id = isset($input['period_id']) ? intval($input['period_id']) : null;

    // Validate required fields
    if (empty($requester_coop_id) || $requested_amount <= 0) {
        throw new Exception('requester_coop_id and requested_amount are required', 400);
    }
    
    // Period ID is required
    if (!$period_id) {
        throw new Exception('period_id is required', 400);
    }
    
    // Check if user already has a pending or submitted request for this period
    $existingQuery = "SELECT id, status FROM loan_requests 
        WHERE requester_coop_id = :coop_id 
        AND period_id = :period_id 
        AND status IN ('draft', 'pending_guarantors', 'partially_guaranteed', 'submitted')
        LIMIT 1";
    $existingStmt = $db->prepare($existingQuery);
    $existingStmt->execute([
        ':coop_id' => $requester_coop_id,
        ':period_id' => $period_id
    ]);
    $existingRequest = $existingStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingRequest) {
        // Human-friendly error messages based on status
        $statusMessages = [
            'draft' => 'You already have a draft loan request for this payroll period. Please complete or cancel your existing request first.',
            'pending_guarantors' => 'You already have a loan request waiting for guarantor approval for this payroll period. Please wait for your guarantors to respond or check your loan request status.',
            'partially_guaranteed' => 'You already have a loan request with partial guarantor approval for this payroll period. Please wait for all guarantors to respond.',
            'submitted' => 'You already have a submitted loan request for this payroll period. Please wait for admin approval or check your loan request status.'
        ];
        
        $status = $existingRequest['status'];
        $message = $statusMessages[$status] ?? "You already have a {$status} loan request for this payroll period. Please check your loan request status.";
        
        throw new Exception($message, 400);
    }

    // Calculate monthly repayment (10% of requested amount)
    $monthly_repayment = $requested_amount * 0.10;

    // Verify member exists and get StaffID if not provided
    $memberQuery = "SELECT CoopID, StaffID FROM tblemployees WHERE CoopID = :coop_id AND Status = 'Active' LIMIT 1";
    $memberStmt = $db->prepare($memberQuery);
    $memberStmt->execute([':coop_id' => $requester_coop_id]);
    $memberData = $memberStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memberData) {
        throw new Exception('Member not found or inactive', 404);
    }
    
    // If staff_id is not provided, get it from the member data
    if (!$staff_id && isset($memberData['StaffID'])) {
        $staff_id = intval($memberData['StaffID']);
    }
    
    // Default deduction_id to 48 if not provided (cooperative deduction)
    if (!$deduction_id) {
        $deduction_id = 48;
    }

    // Insert loan request
    $insertQuery = "INSERT INTO loan_requests 
        (requester_coop_id, requested_amount, monthly_repayment, payslip_file_path, staff_id, deduction_id, period_id, status)
        VALUES (:requester_coop_id, :requested_amount, :monthly_repayment, :payslip_file_path, :staff_id, :deduction_id, :period_id, 'draft')";

    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([
        ':requester_coop_id' => $requester_coop_id,
        ':requested_amount' => $requested_amount,
        ':monthly_repayment' => $monthly_repayment,
        ':payslip_file_path' => $payslip_file_path,
        ':staff_id' => $staff_id,
        ':deduction_id' => $deduction_id,
        ':period_id' => $period_id
    ]);

    $loanRequestId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Loan request created successfully',
        'data' => [
            'id' => $loanRequestId,
            'requester_coop_id' => $requester_coop_id,
            'requested_amount' => $requested_amount,
            'monthly_repayment' => $monthly_repayment,
            'status' => 'draft'
        ]
    ]);
}

function getLoanRequests($db) {
    $requester_coop_id = isset($_GET['requester_coop_id']) ? trim($_GET['requester_coop_id']) : null;

    if (!$requester_coop_id) {
        throw new Exception('requester_coop_id is required', 400);
    }

    $query = "SELECT 
        lr.id,
        lr.requester_coop_id,
        lr.requested_amount,
        lr.monthly_repayment,
        lr.payslip_file_path,
        lr.status,
        lr.period_id,
        lr.created_at,
        lr.updated_at,
        lr.submitted_at,
        (SELECT COUNT(*) FROM guarantor_requests gr WHERE gr.loan_request_id = lr.id AND gr.status = 'approved') as approved_guarantors,
        (SELECT COUNT(*) FROM guarantor_requests gr WHERE gr.loan_request_id = lr.id AND gr.status = 'rejected') as rejected_guarantors,
        (SELECT COUNT(*) FROM guarantor_requests gr WHERE gr.loan_request_id = lr.id) as total_guarantors
    FROM loan_requests lr
    WHERE lr.requester_coop_id = :requester_coop_id
    ORDER BY lr.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([':requester_coop_id' => $requester_coop_id]);

    $loanRequests = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $loanRequests[] = [
            'id' => intval($row['id']),
            'requester_coop_id' => $row['requester_coop_id'],
            'requested_amount' => floatval($row['requested_amount']),
            'monthly_repayment' => floatval($row['monthly_repayment']),
            'payslip_file_path' => $row['payslip_file_path'],
            'status' => $row['status'],
            'period_id' => $row['period_id'] ? intval($row['period_id']) : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'submitted_at' => $row['submitted_at'],
            'approved_guarantors' => intval($row['approved_guarantors']),
            'rejected_guarantors' => intval($row['rejected_guarantors']),
            'total_guarantors' => intval($row['total_guarantors'])
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $loanRequests
    ]);
}