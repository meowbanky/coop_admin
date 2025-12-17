<?php
// api/events/checkin.php
// Mobile API for checking in to an event

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
    require_once '../../utils/JWTHandler.php';
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }
    
    // Authenticate user
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }
    
    $token = $matches[1];
    $jwtHandler = new JWTHandler();
    $decoded = $jwtHandler->validateToken($token);
    
    if (!$decoded || !isset($decoded['user_id'])) {
        throw new Exception('Invalid or expired token', 401);
    }
    
    $userCoopId = $decoded['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }
    
    $eventId = intval($input['event_id'] ?? 0);
    $userLat = floatval($input['latitude'] ?? 0);
    $userLng = floatval($input['longitude'] ?? 0);
    $deviceId = trim($input['device_id'] ?? '');
    
    if (!$eventId) {
        throw new Exception('Event ID is required', 400);
    }
    
    if ($userLat == 0 || $userLng == 0) {
        throw new Exception('User location is required', 400);
    }
    
    if (empty($deviceId)) {
        throw new Exception('Device ID is required', 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
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
    $now = date('Y-m-d H:i:s');
    $gracePeriodMinutes = intval($event['grace_period_minutes']);
    
    // Calculate check-in deadline (end_time + grace period)
    $endTime = new DateTime($event['end_time']);
    $endTime->modify("+{$gracePeriodMinutes} minutes");
    $checkInDeadline = $endTime->format('Y-m-d H:i:s');
    
    // Check time window validation
    if ($now < $event['start_time']) {
        $startTime = new DateTime($event['start_time']);
        throw new Exception('Check-in is only available during the event. Event starts at ' . $startTime->format('F j, Y g:i A'), 400);
    }
    
    if ($now > $checkInDeadline) {
        $deadlineTime = new DateTime($checkInDeadline);
        throw new Exception('Check-in period has ended. The grace period expired at ' . $deadlineTime->format('F j, Y g:i A'), 400);
    }
    
    // Check if user has already checked in
    $existingCheckInQuery = "SELECT id FROM event_attendance 
        WHERE event_id = :event_id AND user_coop_id = :user_coop_id";
    $existingStmt = $db->prepare($existingCheckInQuery);
    $existingStmt->execute([
        ':event_id' => $eventId,
        ':user_coop_id' => $userCoopId
    ]);
    
    if ($existingStmt->rowCount() > 0) {
        throw new Exception('You have already checked in to this event', 400);
    }
    
    // Check if device has been used by another user for this event
    $deviceCheckQuery = "SELECT user_coop_id FROM event_attendance 
        WHERE event_id = :event_id AND device_id = :device_id AND user_coop_id != :user_coop_id
        LIMIT 1";
    $deviceStmt = $db->prepare($deviceCheckQuery);
    $deviceStmt->execute([
        ':event_id' => $eventId,
        ':device_id' => $deviceId,
        ':user_coop_id' => $userCoopId
    ]);
    
    if ($deviceStmt->rowCount() > 0) {
        $deviceUsedBy = $deviceStmt->fetch(PDO::FETCH_ASSOC);
        throw new Exception('This device has already been used to check in another user for this event', 400);
    }
    
    // Calculate distance using Haversine formula
    $eventLat = floatval($event['location_lat']);
    $eventLng = floatval($event['location_lng']);
    $geofenceRadius = intval($event['geofence_radius']);
    
    $distance = calculateDistance($userLat, $userLng, $eventLat, $eventLng);
    
    // Check if user is within geofence
    if ($distance > $geofenceRadius) {
        echo json_encode([
            'success' => false,
            'message' => 'You are too far from the event location',
            'distance' => round($distance, 2),
            'required_radius' => $geofenceRadius,
            'within_range' => false
        ]);
        exit();
    }
    
    // Save check-in with device ID
    $insertQuery = "INSERT INTO event_attendance 
        (event_id, user_coop_id, check_in_lat, check_in_lng, distance_from_event, device_id, status, admin_override)
        VALUES (:event_id, :user_coop_id, :check_in_lat, :check_in_lng, :distance_from_event, :device_id, 'present', 0)";
    
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([
        ':event_id' => $eventId,
        ':user_coop_id' => $userCoopId,
        ':check_in_lat' => $userLat,
        ':check_in_lng' => $userLng,
        ':distance_from_event' => $distance,
        ':device_id' => $deviceId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Check-in successful',
        'data' => [
            'check_in_time' => $now,
            'distance' => round($distance, 2),
            'within_range' => true
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Event check-in API error: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
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