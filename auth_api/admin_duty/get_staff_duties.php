<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../config/Database.php';
    require_once '../utils/JWTHandler.php';

    // Validate token
    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    if (!$jwt->validateToken($token)) {
        throw new Exception('Invalid token', 401);
    }

    // Get parameters
    $date = $_GET['date'] ?? null;
    $location_id = $_GET['location_id'] ?? null;

    if (!$date) {
        throw new Exception('Date is required');
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT
                sd.*, 
                duty_shifts.title AS shift_title, 
                duty_shifts.start_time, 
                duty_shifts.end_time, 
                locations.location_name, 
                employee.NAME as staff_name
            FROM staff_duties sd
            INNER JOIN locations ON sd.location_id = locations.location_id
            INNER JOIN duty_shifts ON sd.shift_id = duty_shifts.id
            INNER JOIN employee ON sd.staff_id = employee.staff_id
            WHERE sd.duty_date = :date";

    $params = [':date' => $date];

    if ($location_id) {
        $query .= " AND sd.location_id = :location_id";
        $params[':location_id'] = $location_id;
    }

    $query .= " ORDER BY duty_shifts.start_time, employee.NAME";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $duties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $duties
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}