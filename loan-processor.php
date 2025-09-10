<?php
/**
 * Loan Processor Controller
 * Modern MVC implementation for loan processing system
 */

// Start session
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Include required files
require_once('Connections/coop.php');
require_once('classes/LoanProcessorManager.php');
require_once('classes/ResponseHandler.php');
require_once('classes/LoanValidator.php');

// Initialize classes with error handling
try {
    $loanProcessorManager = new LoanProcessorManager($coop, $database);
    $responseHandler = new ResponseHandler();
    $validator = new LoanValidator();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    if ($action !== 'index') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    } else {
        die('Database connection failed. Please try again later.');
    }
}

// Handle different actions
$action = $_GET['action'] ?? 'index';
$response = [];

try {
    switch ($action) {
        case 'index':
            // Get all data needed for the main page
            $employees = $loanProcessorManager->getAllEmployees();
            $payrollPeriods = $loanProcessorManager->getPayrollPeriods();
            $loanData = $loanProcessorManager->getLoanData();
            
            // Include the view
            include 'views/loan-processor.php';
            break;
            
        case 'search_employee':
        case 'get_employee_details':
        case 'get_loan_calculation':
        case 'update_loan':
        case 'get_loan_list':
            // Redirect API calls to the dedicated API endpoint
            header('Location: api/loan-processor.php?' . http_build_query($_GET));
            exit();
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
} catch (Exception $e) {
    error_log("Loan Processor Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

// If this is an API call, return JSON response
if ($action !== 'index') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>