<?php
/**
 * Fetch API Data Endpoint
 * Handles fetching data from OOUTH Salary API
 */

// Start output buffering
ob_start();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Set headers
header('Content-Type: application/json');

// Load API client
require_once(__DIR__ . '/../classes/OOUTHSalaryAPIClient.php');

try {
    $action = $_GET['action'] ?? '';
    
    // Initialize API client
    $apiClient = new OOUTHSalaryAPIClient();
    
    switch ($action) {
        case 'get_periods':
            // Get all payroll periods from API
            $result = $apiClient->getPeriods(1, 1000); // Get up to 1000 periods
            
            if ($result && isset($result['success']) && $result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data'],
                    'message' => 'Periods loaded successfully'
                ]);
            } else {
                throw new Exception($result['error']['message'] ?? 'Failed to fetch periods from API');
            }
            break;
            
        case 'get_active_period':
            // Get active period
            $result = $apiClient->getActivePeriod();
            
            if ($result && isset($result['success']) && $result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data']['period'],
                    'message' => 'Active period retrieved successfully'
                ]);
            } else {
                throw new Exception($result['error']['message'] ?? 'Failed to fetch active period from API');
            }
            break;
            
        case 'get_data':
            // Get deduction/allowance data for specific period
            $periodId = $_GET['period'] ?? null;
            
            if (!$periodId) {
                throw new Exception('Period ID is required');
            }
            
            // Fetch data based on resource type configured in api_config.php
            $result = $apiClient->getResourceData($periodId);
            
            if ($result && isset($result['success']) && $result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => $result['data'],
                    'metadata' => $result['metadata'],
                    'message' => 'Data retrieved successfully'
                ]);
            } else {
                throw new Exception($result['error']['message'] ?? 'Failed to fetch data from API');
            }
            break;
            
        case 'get_local_periods':
            // Get local payroll periods from database
            require_once(__DIR__ . '/../Connections/coop.php');
            mysqli_select_db($coop, $database);
            
            $query = "SELECT id, PayrollPeriod, PhysicalYear, PhysicalMonth 
                     FROM tbpayrollperiods 
                     ORDER BY id DESC";
            $result = mysqli_query($coop, $query);
            
            if ($result) {
                $periods = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $periods[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $periods,
                    'message' => 'Local periods loaded successfully'
                ]);
            } else {
                throw new Exception('Failed to fetch local periods: ' . mysqli_error($coop));
            }
            
            mysqli_close($coop);
            break;
            
        case 'test_connection':
            // Test API connection
            if ($apiClient->authenticate()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'API connection successful',
                    'data' => [
                        'resource_type' => OOUTH_RESOURCE_TYPE,
                        'resource_id' => OOUTH_RESOURCE_ID,
                        'resource_name' => OOUTH_RESOURCE_NAME,
                        'organization_id' => OOUTH_ORGANIZATION_ID
                    ]
                ]);
            } else {
                throw new Exception('Failed to authenticate with API');
            }
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();