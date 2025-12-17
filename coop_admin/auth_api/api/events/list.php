<?php
// api/events/list.php
// Mobile API for listing events

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
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get filter parameter (optional: 'upcoming', 'active', 'past', 'all')
    $filter = $_GET['filter'] ?? 'upcoming';
    $now = date('Y-m-d H:i:s');
    
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
            WHEN e.start_time > :now THEN 'upcoming'
            WHEN e.start_time <= :now AND e.end_time >= :now THEN 'active'
            ELSE 'past'
        END as status,
        CASE 
            WHEN ea.id IS NOT NULL THEN 1
            ELSE 0
        END as has_checked_in
    FROM events e
    LEFT JOIN event_attendance ea ON ea.event_id = e.id AND ea.user_coop_id = :user_coop_id
    WHERE 1=1";
    
    $params = [':now' => $now, ':user_coop_id' => $userCoopId];
    
    // Apply filter
    if ($filter === 'upcoming') {
        $query .= " AND e.start_time > :now";
    } elseif ($filter === 'active') {
        $query .= " AND e.start_time <= :now AND e.end_time >= :now";
    } elseif ($filter === 'past') {
        $query .= " AND e.end_time < :now";
    }
    // 'all' shows everything, no additional WHERE clause
    
    $query .= " ORDER BY e.start_time ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
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
            'status' => $row['status'],
            'has_checked_in' => intval($row['has_checked_in']) === 1,
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $events
    ]);
    
} catch (Exception $e) {
    error_log("Events list API error: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}