<?php
// api/salary/periods.php
// Fetch payroll periods from remote OOUTH Salary API

if (ob_get_level()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

    // Get and validate JWT token
    $authHeader = '';
    
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    
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

    // Fetch periods from remote OOUTH Salary API
    // Using the same API client pattern from the admin panel
    $apiClientPath = __DIR__ . '/../../../classes/OOUTHSalaryAPIClient.php';
    if (!file_exists($apiClientPath)) {
        // Try alternative path
        $apiClientPath = __DIR__ . '/../../../../classes/OOUTHSalaryAPIClient.php';
    }
    
    if (file_exists($apiClientPath)) {
        require_once $apiClientPath;
        $apiClient = new OOUTHSalaryAPIClient();
        $result = $apiClient->getPeriods(1, 1000); // Get up to 1000 periods
    } else {
        throw new Exception('OOUTHSalaryAPIClient class not found', 500);
    }
    
    if ($result && isset($result['success']) && $result['success']) {
        // Format periods for frontend
        $periods = [];
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $period) {
                $periods[] = [
                    'period_id' => $period['period_id'] ?? $period['id'] ?? null,
                    'description' => $period['description'] ?? $period['name'] ?? '',
                    'year' => $period['year'] ?? '',
                    'month' => $period['month'] ?? '',
                    'is_active' => $period['is_active'] ?? false,
                    'display_name' => ($period['description'] ?? '') . ' ' . ($period['year'] ?? '')
                ];
            }
        }
        
        // Only return the last/max period (highest period_id)
        $maxPeriod = null;
        $maxPeriodId = 0;
        foreach ($periods as $period) {
            $periodId = intval($period['period_id'] ?? 0);
            if ($periodId > $maxPeriodId) {
                $maxPeriodId = $periodId;
                $maxPeriod = $period;
            }
        }
        
        // If no period found, return empty array
        $finalPeriods = $maxPeriod ? [$maxPeriod] : [];
        
        echo json_encode([
            'success' => true,
            'data' => $finalPeriods,
            'message' => 'Period loaded successfully'
        ]);
    } else {
        throw new Exception($result['error']['message'] ?? 'Failed to fetch periods from remote API', 500);
    }

} catch (Exception $e) {
    error_log("Salary periods error: " . $e->getMessage());
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