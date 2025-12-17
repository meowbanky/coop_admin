<?php
// api/admin/periods.php
// Admin API for getting payroll periods - uses same salary API as the app

ob_clean();

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
    // Start session for admin authentication
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is admin
    if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] !== 'Admin') {
        throw new Exception('Admin access required', 403);
    }

    // Fetch periods from remote OOUTH Salary API (same as app uses)
    // Using the same API client pattern from the admin panel
    $apiClientPath = __DIR__ . '/../../../classes/OOUTHSalaryAPIClient.php';
    if (!file_exists($apiClientPath)) {
        // Try alternative path
        $apiClientPath = __DIR__ . '/../../../../classes/OOUTHSalaryAPIClient.php';
    }
    
    if (!file_exists($apiClientPath)) {
        throw new Exception('OOUTHSalaryAPIClient class not found. Please ensure the salary API client is available.', 500);
    }
    
    require_once $apiClientPath;
    $apiClient = new OOUTHSalaryAPIClient();
    $result = $apiClient->getPeriods(1, 1000); // Get up to 1000 periods
    
    if ($result && isset($result['success']) && $result['success']) {
        // Format periods for frontend
        $periods = [];
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $period) {
                $periodId = $period['period_id'] ?? $period['id'] ?? null;
                $description = $period['description'] ?? $period['name'] ?? '';
                $year = $period['year'] ?? '';
                $month = $period['month'] ?? '';
                
                $periods[] = [
                    'id' => $periodId,
                    'period_id' => $periodId,
                    'description' => $description,
                    'year' => $year,
                    'month' => $month,
                    'is_active' => $period['is_active'] ?? false,
                    'display_name' => trim($description . ' ' . $year),
                    'PayrollPeriod' => $description, // For compatibility
                    'name' => $description // For compatibility
                ];
            }
        }
        
        // Only return the last/max period (highest period_id) - same as salary periods API
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
    error_log("Admin periods API error: " . $e->getMessage());
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