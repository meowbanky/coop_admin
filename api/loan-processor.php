<?php
/**
 * Loan Processor API Endpoints
 * Handles AJAX requests for loan processing operations
 */

// Suppress warnings and errors for clean JSON output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once('../Connections/coop.php');
require_once('../classes/LoanProcessorManager.php');
require_once('../classes/LoanValidator.php');
require_once('../classes/ResponseHandler.php');

// Start session
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Initialize classes with error handling
try {
    $loanProcessorManager = new LoanProcessorManager($coop, $database);
    $validator = new LoanValidator();
    $responseHandler = new ResponseHandler();
} catch (Exception $e) {
    error_log("API Database connection error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            if (!isset($input['action'])) {
                $response = ['success' => false, 'message' => 'No action specified'];
                break;
            }
            
            switch ($input['action']) {
                case 'update_loan':
                    // Validate input
                    $validation = $validator->validateLoanUpdate($input);
                    if (!$validation['valid']) {
                        $response = [
                            'success' => false,
                            'message' => implode(', ', $validation['errors'])
                        ];
                        break;
                    }
                    
                    $result = $loanProcessorManager->updateLoan($input);
                    $response = $result;
                    break;
                    
                case 'delete_loan':
                    $loanApprovalId = $input['loan_id'] ?? '';
                    
                    if (empty($loanApprovalId)) {
                        $response = ['success' => false, 'message' => 'Loan ID is required'];
                        break;
                    }
                    
                    $result = $loanProcessorManager->deleteLoan($loanApprovalId);
                    $response = $result;
                    break;
                    
                default:
                    $response = ['success' => false, 'message' => 'Invalid action'];
            }
            break;
            
        case 'GET':
            if (!isset($_GET['action'])) {
                $response = ['success' => false, 'message' => 'No action specified'];
                break;
            }
            
            switch ($_GET['action']) {
                case 'search_employee':
                    $searchTerm = $_GET['q'] ?? '';
                    $validation = $validator->validateEmployeeSearch($searchTerm);
                    if (!$validation['valid']) {
                        $response = [
                            'success' => false,
                            'message' => implode(', ', $validation['errors'])
                        ];
                        break;
                    }
                    
                    $employees = $loanProcessorManager->searchEmployees($searchTerm);
                    $response = [
                        'success' => true,
                        'data' => $employees
                    ];
                    break;
                    
                case 'get_employee_details':
                    $coopId = $_GET['coop_id'] ?? '';
                    if (empty($coopId)) {
                        $response = ['success' => false, 'message' => 'Employee ID is required'];
                        break;
                    }
                    
                    $employeeDetails = $loanProcessorManager->getEmployeeDetails($coopId);
                    if ($employeeDetails) {
                        $response = [
                            'success' => true,
                            'data' => $employeeDetails
                        ];
                    } else {
                        $response = ['success' => false, 'message' => 'Employee not found'];
                    }
                    break;
                    
                case 'get_loan_calculation':
                    $coopId = $_GET['coop_id'] ?? '';
                    $periodId = $_GET['period_id'] ?? '';
                    
                    if (empty($coopId) || empty($periodId)) {
                        $response = ['success' => false, 'message' => 'Employee ID and Period ID are required'];
                        break;
                    }
                    
                    $calculation = $loanProcessorManager->calculateLoanDetails($coopId, $periodId);
                    $response = [
                        'success' => true,
                        'data' => $calculation
                    ];
                    break;
                    
                case 'get_loan_list':
                    $coopId = $_GET['coop_id'] ?? '';
                    $periodId = $_GET['period_id'] ?? null;
                    
                    if (empty($coopId)) {
                        $response = ['success' => false, 'message' => 'Employee ID is required'];
                        break;
                    }
                    
                    $loans = $loanProcessorManager->getLoanList($coopId, $periodId);
                    $response = [
                        'success' => true,
                        'data' => $loans
                    ];
                    break;
                    
                case 'get_loan_approval_data':
                    $periodId = $_GET['period_id'] ?? '';
                    
                    if (empty($periodId)) {
                        $response = ['success' => false, 'message' => 'Period ID is required'];
                        break;
                    }
                    
                    $approvals = $loanProcessorManager->getLoanApprovalData($periodId);
                    $response = [
                        'success' => true,
                        'data' => $approvals
                    ];
                    break;
                    
                case 'get_current_loan_balance':
                    $coopId = $_GET['coop_id'] ?? '';
                    
                    if (empty($coopId)) {
                        $response = ['success' => false, 'message' => 'Employee ID is required'];
                        break;
                    }
                    
                    $loanBalance = $loanProcessorManager->getCurrentLoanBalance($coopId);
                    $response = [
                        'success' => true,
                        'data' => $loanBalance
                    ];
                    break;
                    
                case 'get_current_period_loans':
                    $periodId = $_GET['period_id'] ?? '';
                    
                    if (empty($periodId)) {
                        $response = ['success' => false, 'message' => 'Period ID is required'];
                        break;
                    }
                    
                    $loans = $loanProcessorManager->getCurrentPeriodLoans($periodId);
                    $response = [
                        'success' => true,
                        'data' => $loans
                    ];
                    break;
                    
                default:
                    $response = ['success' => false, 'message' => 'Invalid action'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Method not allowed'];
    }
    
} catch (Exception $e) {
    error_log("Loan Processor API Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

// Send response
echo json_encode($response);
exit();
?>
