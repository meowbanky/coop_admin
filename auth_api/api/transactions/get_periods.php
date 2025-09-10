<?php
// periods/get_periods.php
if (ob_get_level()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set all required CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 1728000');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed', 405);
    }

    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../utils/JWTHandler.php';

    // Get and validate JWT token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authorization token required', 401);
    }

    $jwt = new JWTHandler();
    $token = $matches[1];
    $decoded = $jwt->validateToken($token);

    if (!$decoded) {
        throw new Exception('Invalid token', 401);
    }

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Query to get periods
    $query = "SELECT 
        id,
        PayrollPeriod,
        PhysicalYear,
        PhysicalMonth
    FROM tbpayrollperiods 
    ORDER BY id DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($periods) {
        $response = [
            'success' => true,
            'message' => 'Periods retrieved successfully',
            'data' => $periods
        ];
        http_response_code(200);
    } else {
        $response = [
            'success' => false,
            'message' => 'No periods found',
            'data' => []
        ];
        http_response_code(404);
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Periods error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}