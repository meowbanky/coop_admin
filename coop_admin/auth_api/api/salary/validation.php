<?php
// api/salary/validation.php
// Validate salary deduction capacity for loan repayment

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
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    
    require_once '../../config/Database.php';
    require_once '../../utils/JWTHandler.php';
    
    ini_set('display_errors', $oldDisplayErrors);
    error_reporting($oldErrorReporting);

    // Get and validate JWT token
    // Try multiple methods to get Authorization header (GET requests sometimes need special handling)
    $authHeader = '';
    
    // Method 1: Try getallheaders() first
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    
    // Method 2: Try apache_request_headers() if available
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    
    // Method 3: Fallback to $_SERVER (most reliable for GET requests)
    if (empty($authHeader)) {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_getenv') && apache_getenv('HTTP_AUTHORIZATION')) {
            $authHeader = apache_getenv('HTTP_AUTHORIZATION');
        }
    }

    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authorization token required', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $decodedToken = $jwt->validateToken($token);

    if (!$decodedToken) {
        throw new Exception('Invalid token', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    // Get parameters
    $staff_id = isset($_GET['staff_id']) ? filter_var($_GET['staff_id'], FILTER_VALIDATE_INT) : null;
    $coop_id = isset($_GET['coop_id']) ? $_GET['coop_id'] : null;
    $deduction_id = isset($_GET['deduction_id']) ? filter_var($_GET['deduction_id'], FILTER_VALIDATE_INT) : null;
    $monthly_repayment = isset($_GET['monthly_repayment']) ? filter_var($_GET['monthly_repayment'], FILTER_VALIDATE_FLOAT) : null;
    $periodId = isset($_GET['period']) ? filter_var($_GET['period'], FILTER_VALIDATE_INT) : null;

    // If staff_id is not provided, try to get it from coop_id
    if (!$staff_id && $coop_id) {
        $staffQuery = "SELECT StaffID FROM tblemployees WHERE CoopID = :coop_id LIMIT 1";
        $staffStmt = $db->prepare($staffQuery);
        $staffStmt->execute([':coop_id' => $coop_id]);
        $staff_id = $staffStmt->fetchColumn();
        
        if (!$staff_id) {
            throw new Exception('StaffID not found for the provided CoopID', 404);
        }
    }

    // Note: deduction_id parameter is for local reference only
    // For remote API calls, we must use the deduction ID from API config (the one the API key has access to)
    // The API key is configured for deduction ID 48 (cooperative deduction)
    
    if (!$staff_id) {
        throw new Exception('staff_id or coop_id is required', 400);
    }

    if ($monthly_repayment === null || $monthly_repayment <= 0) {
        throw new Exception('monthly_repayment is required and must be greater than 0', 400);
    }

    // Period is REQUIRED - salary data is only available from remote API
    if (!$periodId) {
        throw new Exception('Period is required. Please select a payroll period', 400);
    }
    
    // Call remote OOUTH Salary API to get staff deduction data
    // Salary system is on separate remote server - no local database fallback
    $apiClientPath = __DIR__ . '/../../../classes/OOUTHSalaryAPIClient.php';
    
    if (!file_exists($apiClientPath)) {
        // Try alternative path
        $apiClientPath = __DIR__ . '/../../../../classes/OOUTHSalaryAPIClient.php';
    }
    
    if (!file_exists($apiClientPath)) {
        throw new Exception('Salary API client not configured. Please contact administrator', 500);
    }
    
    // Suppress warnings during require to prevent HTML output
    // OOUTHSalaryAPIClient already requires api_config.php internally
    $oldErrorReporting = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
    $oldDisplayErrors = ini_get('display_errors');
    ini_set('display_errors', 0);
    
    require_once $apiClientPath;
    
    ini_set('display_errors', $oldDisplayErrors);
    error_reporting($oldErrorReporting);
    
    if (!class_exists('OOUTHSalaryAPIClient')) {
        throw new Exception('OOUTHSalaryAPIClient class not found', 500);
    }
    
    $apiClient = new OOUTHSalaryAPIClient();
    
    // Check if getStaffDeduction method exists
    if (!method_exists($apiClient, 'getStaffDeduction')) {
        throw new Exception('Salary API client method not available. Please update OOUTHSalaryAPIClient.php on server', 500);
    }
    
    // Authenticate with remote API
    if (!$apiClient->authenticate()) {
        throw new Exception('Failed to authenticate with remote salary API', 500);
    }
    
    // Call the remote API endpoint: /api/v1/payroll/staff-deduction
    // Use null for deductionId to let the API client use the deduction ID from API config (the one the API key has access to)
    $apiResponse = $apiClient->getStaffDeduction($staff_id, $periodId, null);
    
    if (!$apiResponse) {
        throw new Exception('No response from remote salary API', 500);
    }
    
    // Check if response is valid (not HTML/error page)
    if (is_string($apiResponse) && (strpos($apiResponse, '<') !== false || strpos($apiResponse, '<br') !== false)) {
        throw new Exception('Remote salary API returned invalid response format', 500);
    }
    
    if (!isset($apiResponse['success']) || !$apiResponse['success']) {
        $errorMessage = $apiResponse['error']['message'] ?? $apiResponse['message'] ?? 'Failed to fetch data from remote salary API';
        throw new Exception($errorMessage, 500);
    }
    
    // Extract data from API response
    $apiData = $apiResponse['data'] ?? $apiResponse;
    
    // Parse the response based on actual API response structure
    // API returns: net_pay (net salary after all deductions), deduction_amount (current deduction for this deduction ID)
    $netPay = floatval($apiData['net_pay'] ?? $apiData['net_salary'] ?? $apiData['net'] ?? 0);
    $currentDeductionAmount = floatval($apiData['deduction_amount'] ?? $apiData['total_deductions'] ?? $apiData['deductions'] ?? $apiData['total_deduction'] ?? 0);
    
    // Fallback: try to calculate from other fields if net_pay not available
    if ($netPay == 0) {
        $totalEarnings = floatval($apiData['total_earnings'] ?? $apiData['gross_salary'] ?? $apiData['earnings'] ?? $apiData['gross'] ?? 0);
        $totalDeductions = floatval($apiData['total_deductions'] ?? $apiData['deductions'] ?? $apiData['total_deduction'] ?? 0);
        $netPay = $totalEarnings - $totalDeductions;
    }
    
    // Validation Logic:
    // net_pay = 184249 (net salary AFTER all deductions including current cooperative deduction)
    // deduction_amount = 50000 (current cooperative deduction already being deducted)
    // monthly_repayment = 100000 (total monthly repayment required for the loan)
    // 
    // The user needs to repay 100000 monthly, but already has 50000 deducted.
    // Additional deduction needed = 100000 - 50000 = 50000
    // 
    // Check: Can we deduct additional 50000 from current net_pay (184249)?
    // Answer: YES, because 50000 <= 184249, so PASS
    
    $additionalDeductionNeeded = $monthly_repayment - $currentDeductionAmount;

    // Validate: Can we deduct the additional amount from net_pay?
    // The net_pay is what's left after current deductions, so it represents available capacity
    $canAfford = $netPay >= $additionalDeductionNeeded;
    
    // Also calculate available capacity for display (net_pay represents this)
    $availableCapacity = $netPay;

    // Calculate shortfall (how much more is needed if can't afford)
    $shortfall = $canAfford ? 0 : ($additionalDeductionNeeded - $netPay);
    
    $response = [
        'success' => true,
        'data' => [
            'net_pay' => floatval($netPay),
            'current_deduction_amount' => floatval($currentDeductionAmount),
            'monthly_repayment' => floatval($monthly_repayment),
            'additional_deduction_needed' => floatval($additionalDeductionNeeded),
            'available_capacity' => floatval($availableCapacity),
            'can_afford' => $canAfford,
            'shortfall' => floatval($shortfall),
            'period_id' => $periodId,
            // Keep backward compatibility fields
            'net_salary' => floatval($netPay),
            'current_deductions' => floatval($currentDeductionAmount),
            'total_earnings' => 0 // Not available from this API endpoint
        ]
    ];

    // Clean output buffer and send JSON response
    ob_clean();
    echo json_encode($response);
    ob_end_flush();
    exit();

} catch (Exception $e) {
    // Clean output buffer before sending error
    ob_clean();
    
    error_log("Salary validation error: " . $e->getMessage());

    $status_code = $e->getCode();
    if (!is_int($status_code) || $status_code < 100 || $status_code > 599) {
        $status_code = 500;
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
    
    error_log("Salary validation PHP error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
    ob_end_flush();
    exit();
}