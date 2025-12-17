<?php
// api/admin/manual-checkin.php
// Admin API for manually checking in users (override)

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
    $adminUsername = $_SESSION['complete_name'] ?? $_SESSION['SESS_FIRST_NAME'] ?? 'Admin';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }
    
    $eventId = intval($input['event_id'] ?? 0);
    $userCoopId = trim($input['user_coop_id'] ?? '');
    $deviceId = trim($input['device_id'] ?? '') ?: 'admin-override-' . time() . '-' . rand(1000, 9999);
    $latitude = isset($input['latitude']) ? floatval($input['latitude']) : null;
    $longitude = isset($input['longitude']) ? floatval($input['longitude']) : null;
    $skipLocationCheck = isset($input['skip_location_check']) && $input['skip_location_check'] === true;
    
    if (!$eventId) {
        throw new Exception('Event ID is required', 400);
    }
    
    if (empty($userCoopId)) {
        throw new Exception('User Coop ID is required', 400);
    }
    
    // Validate that the Coop ID exists in tblemployees table
    $validateUserQuery = "SELECT CoopID FROM tblemployees WHERE CoopID = :coop_id LIMIT 1";
    $validateStmt = $db->prepare($validateUserQuery);
    $validateStmt->execute([':coop_id' => $userCoopId]);
    
    if ($validateStmt->rowCount() === 0) {
        throw new Exception('Invalid Coop ID. Member not found in the system.', 400);
    }
    
    // Get event details
    $eventQuery = "SELECT 
        id,
        start_time,
        end_time,
        location_lat,
        location_lng,
        geofence_radius,
        COALESCE(grace_period_minutes, 20) as grace_period_minutes
    FROM events
    WHERE id = :event_id";
    
    $eventStmt = $db->prepare($eventQuery);
    $eventStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $eventStmt->execute();
    
    if ($eventStmt->rowCount() === 0) {
        throw new Exception('Event not found', 404);
    }
    
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user has already checked in
    $existingCheckInQuery = "SELECT id FROM event_attendance 
        WHERE event_id = :event_id AND user_coop_id = :user_coop_id";
    $existingStmt = $db->prepare($existingCheckInQuery);
    $existingStmt->execute([
        ':event_id' => $eventId,
        ':user_coop_id' => $userCoopId
    ]);
    
    if ($existingStmt->rowCount() > 0) {
        throw new Exception('User has already checked in to this event', 400);
    }
    
    // Calculate distance if location provided
    $distance = 0;
    if ($latitude !== null && $longitude !== null && !$skipLocationCheck) {
        $distance = calculateDistance(
            floatval($event['location_lat']),
            floatval($event['location_lng']),
            $latitude,
            $longitude
        );
    } elseif ($latitude === null || $longitude === null) {
        // Use event location as default
        $latitude = floatval($event['location_lat']);
        $longitude = floatval($event['location_lng']);
        $distance = 0;
    }
    
    // Save check-in with admin override flag
    $insertQuery = "INSERT INTO event_attendance 
        (event_id, user_coop_id, check_in_lat, check_in_lng, distance_from_event, device_id, status, admin_override, checked_in_by_admin)
        VALUES (:event_id, :user_coop_id, :check_in_lat, :check_in_lng, :distance_from_event, :device_id, 'present', 1, :admin_username)";
    
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([
        ':event_id' => $eventId,
        ':user_coop_id' => $userCoopId,
        ':check_in_lat' => $latitude,
        ':check_in_lng' => $longitude,
        ':distance_from_event' => $distance,
        ':device_id' => $deviceId,
        ':admin_username' => $adminUsername
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User checked in successfully (admin override)',
        'data' => [
            'check_in_time' => date('Y-m-d H:i:s'),
            'distance' => round($distance, 2),
            'admin_override' => true
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Manual check-in API error: " . $e->getMessage());
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

/**
 * Calculate distance between two coordinates using Haversine formula
 * Returns distance in meters
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Earth's radius in meters
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}