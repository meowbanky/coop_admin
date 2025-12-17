<?php
// Simple test endpoint to verify API connectivity
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Test 1: Basic PHP execution
    $testResults = [
        'php_version' => PHP_VERSION,
        'server_time' => date('Y-m-d H:i:s'),
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set'
    ];
    
    // Test 2: Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $testResults['session_status'] = 'started';
    $testResults['has_session_id'] = isset($_SESSION['SESS_MEMBER_ID']);
    $testResults['session_role'] = $_SESSION['role'] ?? 'not set';
    
    // Test 3: Database.php file exists
    $dbPath = __DIR__ . '/../../config/Database.php';
    $testResults['database_file_exists'] = file_exists($dbPath);
    $testResults['database_file_path'] = $dbPath;
    
    // Test 4: .env file exists
    $envPath = __DIR__ . '/../../.env';
    $testResults['env_file_exists'] = file_exists($envPath);
    $testResults['env_file_path'] = $envPath;
    
    // Test 5: Try to load Database class
    if (file_exists($dbPath)) {
        try {
            require_once $dbPath;
            $testResults['database_class_loaded'] = true;
            
            // Try to instantiate
            try {
                $database = new Database();
                $testResults['database_instantiated'] = true;
                
                // Try to get connection
                try {
                    $db = $database->getConnection();
                    $testResults['database_connected'] = true;
                } catch (Exception $e) {
                    $testResults['database_connected'] = false;
                    $testResults['database_connection_error'] = $e->getMessage();
                }
            } catch (Exception $e) {
                $testResults['database_instantiated'] = false;
                $testResults['database_instantiation_error'] = $e->getMessage();
            }
        } catch (Exception $e) {
            $testResults['database_class_loaded'] = false;
            $testResults['database_load_error'] = $e->getMessage();
        } catch (Error $e) {
            $testResults['database_class_loaded'] = false;
            $testResults['database_load_error'] = $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Test endpoint working',
        'tests' => $testResults
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Test failed: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}