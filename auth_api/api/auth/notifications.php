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


// Set JSON content type
header('Content-Type: application/json; charset=UTF-8');

try {
    // Include dependencies
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../models/User.php';
    require_once __DIR__ . '/../../utils/JWTHandler.php';

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get the authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authorization token required', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $decodedToken = $jwt->validateToken($token);

    if (!$decodedToken) {
        throw new Exception('Invalid token', 401);
    }

    // Handle different request methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['unread-count'])&& isset($_GET['count'])) {
                getUnreadCount($db);
            } else {
                getNotifications($db);
            }
            break;

        case 'PUT':
            if (preg_match('/\/notifications\.php\/(\d+)\/read$/', $_SERVER['REQUEST_URI'], $matches)) {
                markAsRead($db, $matches[1]); // Pass the extracted notification ID
            } else {
                throw new Exception('Invalid endpoint', 404);
            }

            break;

        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getNotifications($db) {
    if (!isset($_GET['coop_id'])) {
        throw new Exception('Coop ID is required');
    }

    error_log('Getting notifications');
    $coop_id = $_GET['coop_id']; // Don't convert to int since CoopID is string
    error_log($coop_id);
    try {
        $query = "SELECT id, coop_id, title, message, status, created_at, updated_at 
              FROM notifications 
              WHERE coop_id = :coop_id 
              ORDER BY created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':coop_id', $coop_id, PDO::PARAM_STR); // Changed to PARAM_STR

        $stmt->execute();

        $notifications = [];

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $notifications[] = $row;
            }

            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No notifications found for the given Coop ID'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function getUnreadCount($db) {
    error_log('Getting unread count');
    try {
        if (!isset($_GET['coop_id'])) {
            throw new Exception('Coop ID is required');
        }

        $coop_id = $_GET['coop_id']; // Don't convert to int

        $query = "SELECT COUNT(*) as count 
                  FROM notifications 
                  WHERE coop_id = :coop_id AND status != 'read'";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':coop_id', $coop_id, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'count' => intval($result['count'])
        ]);
        error_log('Unread count: ' . $result['count']);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}


function markAsRead($db, $notification_id) {
    try {
        $notification_id = intval($notification_id);

        $query = "UPDATE notifications 
                  SET status = 'read', updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :notification_id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } else {
            throw new Exception('Notification not found or unauthorized');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
