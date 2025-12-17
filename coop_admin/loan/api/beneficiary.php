<?php
// Suppress warnings and errors for clean JSON output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once('../Connections/coop.php');
require_once('../classes/BeneficiaryManager.php');
require_once('../classes/BeneficiaryValidator.php');
require_once('../classes/ResponseHandler.php');

// Start session
session_start();

header('Content-Type: application/json');

// Initialize classes
$beneficiaryManager = new BeneficiaryManager($coop, $database);
$validator = new BeneficiaryValidator();
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
                case 'add_beneficiary':
                    // Validate input
                    $validation = $validator->validateBeneficiaryData($input);
                    if (!$validation['valid']) {
                        $response = [
                            'success' => false,
                            'message' => implode(', ', $validation['errors'])
                        ];
                        break;
                    }
                    
                    $response = $beneficiaryManager->addBeneficiary($input);
                    break;
                    
                case 'update_beneficiary':
                    if (empty($input['beneficiary_code']) || empty($input['amount'])) {
                        $response = ['success' => false, 'message' => 'Missing required fields'];
                        break;
                    }
                    
                    $batch = $input['batch'] ?? $_SESSION['Batch'] ?? '';
                    $response = $beneficiaryManager->updateBeneficiaryAmount($input['beneficiary_code'], $input['amount'], $batch);
                    break;
                    
                case 'delete_beneficiary':
                    if (empty($input['beneficiary_code'])) {
                        $response = ['success' => false, 'message' => 'Beneficiary code is required'];
                        break;
                    }
                    
                    $batch = $input['batch'] ?? $_SESSION['Batch'] ?? '';
                    $response = $beneficiaryManager->deleteBeneficiary($input['beneficiary_code'], $batch);
                    break;
                    
                case 'update_bank_details':
                    if (empty($input['coop_id'])) {
                        $response = ['success' => false, 'message' => 'Member ID is required'];
                        break;
                    }
                    
                    $response = $beneficiaryManager->updateMemberAccount($input);
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
                case 'search_employees':
                    if (empty($_GET['q'])) {
                        $response = ['success' => true, 'data' => []];
                        break;
                    }
                    
                    $employees = $beneficiaryManager->searchEmployees($_GET['q']);
                    $response = [
                        'success' => true,
                        'data' => $employees
                    ];
                    break;
                    
                case 'get_beneficiaries':
                    $batch = $_GET['batch'] ?? $_SESSION['Batch'] ?? '';
                    if (empty($batch)) {
                        $response = ['success' => false, 'message' => 'Batch is required'];
                        break;
                    }
                    
                    $beneficiaries = $beneficiaryManager->getBeneficiaries($batch);
                    $response = [
                        'success' => true,
                        'data' => $beneficiaries
                    ];
                    break;
                    
                case 'get_batch_total':
                    $batch = $_GET['batch'] ?? $_SESSION['Batch'] ?? '';
                    if (empty($batch)) {
                        $response = ['success' => false, 'message' => 'Batch is required'];
                        break;
                    }
                    
                    $total = $beneficiaryManager->getBatchTotal($batch);
                    $response = [
                        'success' => true,
                        'data' => ['total' => $total]
                    ];
                    break;
                    
                case 'get_banks':
                    $banks = $beneficiaryManager->getBanks();
                    $response = [
                        'success' => true,
                        'data' => $banks
                    ];
                    break;
                    
                case 'get_bank_code':
                    $bankName = $_GET['bank_name'] ?? '';
                    if (empty($bankName)) {
                        $response = ['success' => false, 'message' => 'Bank name is required'];
                        break;
                    }
                    
                    $bankCode = $beneficiaryManager->getBankCode($bankName);
                    $response = [
                        'success' => true,
                        'data' => ['bank_code' => $bankCode]
                    ];
                    break;
                    
                case 'get_member_bank_details':
                    $coopId = $_GET['coop_id'] ?? '';
                    if (empty($coopId)) {
                        $response = ['success' => false, 'message' => 'Member ID is required'];
                        break;
                    }
                    
                    $bankDetails = $beneficiaryManager->getMemberBankDetails($coopId);
                    if ($bankDetails) {
                        $response = [
                            'success' => true,
                            'data' => $bankDetails
                        ];
                    } else {
                        $response = ['success' => false, 'message' => 'Member bank details not found'];
                    }
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