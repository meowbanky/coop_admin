<?php
// api/events/details.php
// Mobile API for getting event details

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
    require_once '../../config/Database.php';
    require_once '../../utils/JWTHandler.php';
    
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
    
    // Get event ID from query parameter
    $eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$eventId) {
        throw new Exception('Event ID is required', 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get event details
    $query = "SELECT 
        e.id,
        e.title,
        e.description,
        e.start_time,
        e.end_time,
        e.location_lat,
        e.location_lng,
        e.geofence_radius,
        e.created_at,
        CASE 
            WHEN e.start_time > NOW() THEN 'upcoming'
            WHEN e.start_time <= NOW() AND e.end_time >= NOW() THEN 'active'
            ELSE 'past'
        END as status
    FROM events e
    WHERE e.id = :event_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Event not found', 404);
    }
    
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user has already checked in
    $checkInQuery = "SELECT 
        id,
        check_in_time,
        check_in_lat,
        check_in_lng,
        distance_from_event,
        status
    FROM event_attendance
    WHERE event_id = :event_id AND user_coop_id = :user_coop_id
    LIMIT 1";
    
    $checkInStmt = $db->prepare($checkInQuery);
    $checkInStmt->execute([
        ':event_id' => $eventId,
        ':user_coop_id' => $userCoopId
    ]);
    
    $checkIn = null;
    if ($checkInStmt->rowCount() > 0) {
        $checkInData = $checkInStmt->fetch(PDO::FETCH_ASSOC);
        $checkIn = [
            'check_in_time' => $checkInData['check_in_time'],
            'check_in_lat' => floatval($checkInData['check_in_lat']),
            'check_in_lng' => floatval($checkInData['check_in_lng']),
            'distance_from_event' => floatval($checkInData['distance_from_event']),
            'status' => $checkInData['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => intval($event['id']),
            'title' => $event['title'],
            'description' => $event['description'],
            'start_time' => $event['start_time'],
            'end_time' => $event['end_time'],
            'location_lat' => floatval($event['location_lat']),
            'location_lng' => floatval($event['location_lng']),
            'geofence_radius' => intval($event['geofence_radius']),
            'status' => $event['status'],
            'has_checked_in' => $checkIn !== null,
            'check_in' => $checkIn,
            'created_at' => $event['created_at']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Event details API error: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}