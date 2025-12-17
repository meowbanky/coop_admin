<?php
// api/admin/reset-device-lock.php
// Admin API for resetting device locks for an event

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../../config/Database.php';
    
    // Start session for admin authentication
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is admin
    if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] !== 'Admin') {
        throw new Exception('Admin access required', 403);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }
    
    $eventId = intval($input['event_id'] ?? 0);
    $deviceId = trim($input['device_id'] ?? '');
    
    if (!$eventId) {
        throw new Exception('Event ID is required', 400);
    }
    
    if (empty($deviceId)) {
        throw new Exception('Device ID is required', 400);
    }
    
    // Check if event exists
    $eventQuery = "SELECT id FROM events WHERE id = :event_id";
    $eventStmt = $db->prepare($eventQuery);
    $eventStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $eventStmt->execute();
    
    if ($eventStmt->rowCount() === 0) {
        throw new Exception('Event not found', 404);
    }
    
    // Check if device has any check-ins for this event
    $checkQuery = "SELECT id, user_coop_id FROM event_attendance 
        WHERE event_id = :event_id AND device_id = :device_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([
        ':event_id' => $eventId,
        ':device_id' => $deviceId
    ]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('No check-in found for this device and event', 404);
    }
    
    // Delete the attendance record(s) for this device
    // This will free up the device for another user
    $deleteQuery = "DELETE FROM event_attendance 
        WHERE event_id = :event_id AND device_id = :device_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([
        ':event_id' => $eventId,
        ':device_id' => $deviceId
    ]);
    
    $deletedCount = $deleteStmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => "Device lock reset successfully. {$deletedCount} check-in record(s) removed.",
        'data' => [
            'event_id' => $eventId,
            'device_id' => $deviceId,
            'records_deleted' => $deletedCount
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Reset device lock API error: " . $e->getMessage());
    $code = $e->getCode();
    $httpCode = is_numeric($code) ? intval($code) : 500;
    if ($httpCode < 100 || $httpCode > 599) {
        $httpCode = 500;
    }
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}