<?php
/**
 * Batch API Endpoint
 * Handles AJAX requests for batch operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once('../Connections/coop.php');
require_once('../classes/BatchManager.php');
require_once('../classes/ValidationHelper.php');
require_once('../classes/ResponseHandler.php');

// Initialize classes
$batchManager = new BatchManager($coop, $database_coop);
$validator = new ValidationHelper();
$responseHandler = new ResponseHandler();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $response = $batchManager->handleRequest($input);
            break;
            
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'generate_batch':
                        $response = [
                            'success' => true,
                            'data' => ['batch_number' => $batchManager->generateBatchNumber()]
                        ];
                        break;
                    case 'get_batches':
                        $batches = $batchManager->getAllBatches();
                        $response = [
                            'success' => true,
                            'data' => $batches
                        ];
                        break;
                    case 'get_statistics':
                        $stats = $batchManager->getBatchStatistics();
                        $response = [
                            'success' => true,
                            'data' => $stats
                        ];
                        break;
                    default:
                        $response = ['success' => false, 'message' => 'Invalid action'];
                }
            } else {
                $response = ['success' => false, 'message' => 'No action specified'];
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