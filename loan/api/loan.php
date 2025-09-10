<?php
// Suppress warnings and errors for clean JSON output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once('../Connections/coop.php');
require_once('../classes/LoanManager.php');
require_once('../classes/ResponseHandler.php');

// Start session
session_start();

header('Content-Type: application/json');

// Initialize classes
$loanManager = new LoanManager($coop, $database_coop);
$responseHandler = new ResponseHandler();

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
                case 'insert_loan':
                    $response = $loanManager->insertLoan($input);
                    break;
                    
                case 'post_account':
                    error_log("Post Account API called with input: " . json_encode($input));
                    
                    if (empty($input['batch_number']) || empty($input['loan_period']) || empty($input['payroll_period_id'])) {
                        $response = ['success' => false, 'message' => 'Missing required fields: batch_number, loan_period, payroll_period_id'];
                        error_log("Missing required fields: " . json_encode($input));
                        break;
                    }
                    
                    $selectedBeneficiaries = $input['selected_beneficiaries'] ?? null;
                    error_log("Selected beneficiaries count: " . (is_array($selectedBeneficiaries) ? count($selectedBeneficiaries) : 'null'));
                    
                    $response = $loanManager->postAccount($input['batch_number'], $input['loan_period'], $input['payroll_period_id'], $selectedBeneficiaries);
                    error_log("Post Account API response: " . json_encode($response));
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
                case 'get_payroll_periods':
                    $periods = $loanManager->getPayrollPeriods();
                    $response = [
                        'success' => true,
                        'data' => $periods
                    ];
                    break;
                    
                case 'get_loans_by_period':
                    $payrollPeriodId = $_GET['payroll_period_id'] ?? '';
                    if (empty($payrollPeriodId)) {
                        $response = ['success' => false, 'message' => 'Payroll period ID is required'];
                        break;
                    }
                    
                    $loans = $loanManager->getLoansByPeriod($payrollPeriodId);
                    $response = [
                        'success' => true,
                        'data' => $loans
                    ];
                    break;
                    
                case 'get_loan_statistics':
                    $stats = $loanManager->getLoanStatistics();
                    $response = [
                        'success' => true,
                        'data' => $stats
                    ];
                    break;
                    
                case 'get_batch_beneficiaries':
                    $batchNumber = $_GET['batch_number'] ?? '';
                    if (empty($batchNumber)) {
                        $response = ['success' => false, 'message' => 'Batch number is required'];
                        break;
                    }
                    
                    $beneficiaries = $loanManager->getBatchBeneficiaries($batchNumber);
                    $response = [
                        'success' => true,
                        'data' => $beneficiaries
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
    error_log("API Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

// Send response
echo json_encode($response);
exit;
?>