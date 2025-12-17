<?php
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


header('Content-Type: application/json; charset=UTF-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    require_once __DIR__ . '/../../config/Database.php';

    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'));
    error_log("Received notification data: " . json_encode($input));

    if (!isset($input->staff_id) || !isset($input->title) || !isset($input->message)) {
        throw new Exception('Staff ID, title and message are required');
    }

    $staff_id = intval($input->staff_id);
    $title = $input->title;
    $message = $input->message;

    $query = "INSERT INTO notifications (staff_id, title, message, status) 
              VALUES (?, ?, ?, 'pending')";

    $stmt = $db->prepare($query);
    $stmt->bind_param("iss", $staff_id, $title, $message);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification saved successfully',
            'notification_id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception('Failed to save notification');
    }

} catch (Exception $e) {
    error_log("Save notification error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}