<?php
// api/admin/events.php
// Admin API for managing events

// Clean output buffer
ob_clean();

// Set CORS headers FIRST
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request IMMEDIATELY
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
    if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || ($_SESSION['role'] ?? '') !== 'Admin') {
        throw new Exception('Admin access required', 403);
    }

    $database = new Database();
    $db = $database->getConnection();
    $adminUsername = $_SESSION['complete_name'] ?? 'Admin';

    // Get event ID from URL if present
    $eventId = null;
    $pathParts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $lastPart = end($pathParts);
    if (is_numeric($lastPart) && $lastPart != 'events.php') {
        $eventId = intval($lastPart);
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($eventId) {
                getEventDetails($db, $eventId);
            } else {
                getEvents($db);
            }
            break;
        case 'POST':
            createEvent($db, $adminUsername);
            break;
        case 'PUT':
            if (!$eventId) {
                throw new Exception('Event ID required', 400);
            }
            updateEvent($db, $eventId, $adminUsername);
            break;
        case 'DELETE':
            if (!$eventId) {
                throw new Exception('Event ID required', 400);
            }
            deleteEvent($db, $eventId);
            break;
        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Events API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $statusCode = $e->getCode();
    if (!is_int($statusCode) || $statusCode < 100 || $statusCode > 599) {
        $statusCode = 500;
    }
    
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getEvents($db) {
    $query = "SELECT 
        e.id,
        e.title,
        e.description,
        e.start_time,
        e.end_time,
        e.location_lat,
        e.location_lng,
        e.geofence_radius,
        e.grace_period_minutes,
        e.created_by,
        e.created_at,
        e.updated_at,
        COUNT(DISTINCT ea.id) as attendance_count
    FROM events e
    LEFT JOIN event_attendance ea ON ea.event_id = e.id
    GROUP BY e.id
    ORDER BY e.start_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'id' => intval($row['id']),
            'title' => $row['title'],
            'description' => $row['description'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'location_lat' => floatval($row['location_lat']),
            'location_lng' => floatval($row['location_lng']),
            'geofence_radius' => intval($row['geofence_radius']),
            'grace_period_minutes' => intval($row['grace_period_minutes'] ?? 20),
            'created_by' => $row['created_by'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'attendance_count' => intval($row['attendance_count'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $events
    ]);
}

function getEventDetails($db, $eventId) {
    $query = "SELECT 
        e.id,
        e.title,
        e.description,
        e.start_time,
        e.end_time,
        e.location_lat,
        e.location_lng,
        e.geofence_radius,
        e.grace_period_minutes,
        e.created_by,
        e.created_at,
        e.updated_at
    FROM events e
    WHERE e.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Event not found', 404);
    }
    
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get attendance list
    $attendanceQuery = "SELECT 
        ea.id,
        ea.user_coop_id,
        ea.check_in_time,
        ea.check_in_lat,
        ea.check_in_lng,
        ea.distance_from_event,
        ea.status,
        ea.device_id,
        ea.admin_override,
        ea.checked_in_by_admin,
        CONCAT(e.FirstName, ' ', e.LastName) as user_name
    FROM event_attendance ea
    LEFT JOIN tblemployees e ON e.CoopID = ea.user_coop_id
    WHERE ea.event_id = :event_id
    ORDER BY ea.check_in_time DESC";
    
    $attendanceStmt = $db->prepare($attendanceQuery);
    $attendanceStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $attendanceStmt->execute();
    
    $attendance = [];
    while ($row = $attendanceStmt->fetch(PDO::FETCH_ASSOC)) {
        $attendance[] = [
            'id' => intval($row['id']),
            'user_coop_id' => $row['user_coop_id'],
            'user_name' => $row['user_name'] ?? 'Unknown',
            'check_in_time' => $row['check_in_time'],
            'check_in_lat' => floatval($row['check_in_lat']),
            'check_in_lng' => floatval($row['check_in_lng']),
            'distance_from_event' => floatval($row['distance_from_event']),
            'status' => $row['status'],
            'device_id' => $row['device_id'],
            'admin_override' => intval($row['admin_override']) === 1,
            'checked_in_by_admin' => $row['checked_in_by_admin']
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
            'grace_period_minutes' => intval($event['grace_period_minutes'] ?? 20),
            'created_by' => $event['created_by'],
            'created_at' => $event['created_at'],
            'updated_at' => $event['updated_at'],
            'attendance' => $attendance,
            'attendance_count' => count($attendance)
        ]
    ]);
}

function createEvent($db, $adminUsername) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }
    
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $startTime = $input['start_time'] ?? '';
    $endTime = $input['end_time'] ?? '';
    $locationLat = floatval($input['location_lat'] ?? 0);
    $locationLng = floatval($input['location_lng'] ?? 0);
    $geofenceRadius = intval($input['geofence_radius'] ?? 50);
    $gracePeriodMinutes = isset($input['grace_period_minutes']) ? intval($input['grace_period_minutes']) : 20;
    
    // Validation
    if (empty($title)) {
        throw new Exception('Event title is required', 400);
    }
    
    if (empty($startTime) || empty($endTime)) {
        throw new Exception('Start time and end time are required', 400);
    }
    
    if ($locationLat == 0 || $locationLng == 0) {
        throw new Exception('Event location is required', 400);
    }
    
    if ($geofenceRadius < 10 || $geofenceRadius > 1000) {
        throw new Exception('Geofence radius must be between 10 and 1000 meters', 400);
    }
    
    if ($gracePeriodMinutes < 0 || $gracePeriodMinutes > 120) {
        throw new Exception('Grace period must be between 0 and 120 minutes', 400);
    }
    
    // Validate datetime format
    $startDateTime = date_create($startTime);
    $endDateTime = date_create($endTime);
    
    if (!$startDateTime || !$endDateTime) {
        throw new Exception('Invalid date format', 400);
    }
    
    if ($endDateTime <= $startDateTime) {
        throw new Exception('End time must be after start time', 400);
    }
    
    $query = "INSERT INTO events 
        (title, description, start_time, end_time, location_lat, location_lng, geofence_radius, grace_period_minutes, created_by)
        VALUES (:title, :description, :start_time, :end_time, :location_lat, :location_lng, :geofence_radius, :grace_period_minutes, :created_by)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':title' => $title,
        ':description' => $description ?: null,
        ':start_time' => $startTime,
        ':end_time' => $endTime,
        ':location_lat' => $locationLat,
        ':location_lng' => $locationLng,
        ':geofence_radius' => $geofenceRadius,
        ':grace_period_minutes' => $gracePeriodMinutes,
        ':created_by' => $adminUsername
    ]);
    
    $eventId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Event created successfully',
        'data' => ['id' => intval($eventId)]
    ]);
}

function updateEvent($db, $eventId, $adminUsername) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }
    
    // Check if event exists
    $checkQuery = "SELECT id FROM events WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Event not found', 404);
    }
    
    // Build update query dynamically based on provided fields
    $updateFields = [];
    $params = [':id' => $eventId];
    
    if (isset($input['title'])) {
        $updateFields[] = 'title = :title';
        $params[':title'] = trim($input['title']);
    }
    
    if (isset($input['description'])) {
        $updateFields[] = 'description = :description';
        $params[':description'] = trim($input['description']) ?: null;
    }
    
    if (isset($input['start_time'])) {
        $updateFields[] = 'start_time = :start_time';
        $params[':start_time'] = $input['start_time'];
    }
    
    if (isset($input['end_time'])) {
        $updateFields[] = 'end_time = :end_time';
        $params[':end_time'] = $input['end_time'];
    }
    
    if (isset($input['location_lat'])) {
        $updateFields[] = 'location_lat = :location_lat';
        $params[':location_lat'] = floatval($input['location_lat']);
    }
    
    if (isset($input['location_lng'])) {
        $updateFields[] = 'location_lng = :location_lng';
        $params[':location_lng'] = floatval($input['location_lng']);
    }
    
    if (isset($input['geofence_radius'])) {
        $radius = intval($input['geofence_radius']);
        if ($radius < 10 || $radius > 1000) {
            throw new Exception('Geofence radius must be between 10 and 1000 meters', 400);
        }
        $updateFields[] = 'geofence_radius = :geofence_radius';
        $params[':geofence_radius'] = $radius;
    }
    
    if (isset($input['grace_period_minutes'])) {
        $gracePeriod = intval($input['grace_period_minutes']);
        if ($gracePeriod < 0 || $gracePeriod > 120) {
            throw new Exception('Grace period must be between 0 and 120 minutes', 400);
        }
        $updateFields[] = 'grace_period_minutes = :grace_period_minutes';
        $params[':grace_period_minutes'] = $gracePeriod;
    }
    
    if (empty($updateFields)) {
        throw new Exception('No fields to update', 400);
    }
    
    $query = "UPDATE events SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Event updated successfully'
    ]);
}

function deleteEvent($db, $eventId) {
    // Check if event exists
    $checkQuery = "SELECT id FROM events WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('Event not found', 404);
    }
    
    // Delete event (cascade will delete attendance records)
    $query = "DELETE FROM events WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Event deleted successfully'
    ]);
}