<?php
// api/duty/rota.php

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    require_once 'save_notification.php';

    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $token = $jwt->validateToken($token);

    if (!$token) {
        throw new Exception('Invalid token', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    $action = $_GET['action'] ?? '';

    switch($action) {
        case 'list_shifts':
            $query = "SELECT * FROM duty_shifts ORDER BY start_time";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $shifts
            ]);
            break;

        case 'list_locations':
            $query = "SELECT location_id as id, location_name as duty_locations FROM locations ORDER BY location_name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $locations
            ]);
            break;

        case 'get_duties':
            $staff_id = filter_var($_GET['staff_id'], FILTER_VALIDATE_INT);
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;

            $query = "SELECT
	sd.*, 
	duty_shifts.title AS shift_title, 
	duty_shifts.start_time, 
	duty_shifts.end_time, 
	locations.location_name, 
	sd.`status`, 
	sd.location_id, 
	sd.duty_date, 
	sd.id, 
	sd.staff_id, 
	employee.`NAME` as staff_name
FROM
	staff_duties AS sd
	INNER JOIN
	locations
	ON 
		sd.location_id = locations.location_id
	INNER JOIN
	duty_shifts
	ON 
		sd.shift_id = duty_shifts.id
	INNER JOIN
	employee
	ON 
		sd.staff_id = employee.staff_id
WHERE
	1 = 1";

            $params = [];

            if ($staff_id) {
                $query .= " AND sd.staff_id = :staff_id";
                $params[':staff_id'] = $staff_id;
            }

            if ($start_date && $end_date) {
                $query .= " AND sd.duty_date BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $start_date;
                $params[':end_date'] = $end_date;
            }

            $query .= " ORDER BY duty_date DESC, start_time";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $duties = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $duties
            ]);
            break;

        case 'assign_duty':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['staff_id'], $data['shift_id'], $data['duty_date'], $data['location_id'])) {
                throw new Exception('Missing required fields');
            }

            // Check if duty already exists for this staff and date
            $checkQuery = "SELECT COUNT(*) FROM staff_duties 
                         WHERE staff_id = :staff_id 
                         AND duty_date = :duty_date";

            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([
                ':staff_id' => $data['staff_id'],
                ':duty_date' => $data['duty_date']
            ]);

            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception('Duty already assigned for this date');
            }

            $query = "INSERT INTO staff_duties (staff_id, shift_id, duty_date, location_id, status) 
                    VALUES (:staff_id, :shift_id, :duty_date, :location_id, 'pending')";

            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':staff_id' => $data['staff_id'],
                ':shift_id' => $data['shift_id'],
                ':duty_date' => $data['duty_date'],
                ':location_id' => $data['location_id']
            ]);



            $sql_location = "SELECT * FROM locations WHERE location_id = :location_id";

            $locationStmt = $db->prepare($sql_location);
            $locationStmt->execute([
                ':location_id' => $data['location_id']
            ]);
            $result_location = $locationStmt->fetch(PDO::FETCH_ASSOC);
            $location = $result_location['location_name'];

            $sql_shift = "SELECT  * FROM duty_shifts WHERE id = :id";

            $shiftstmt = $db->prepare($sql_shift);
            $shiftstmt->execute([
                ':id' => $data['shift_id']
            ]);

            $result_shift = $shiftstmt->fetch(PDO::FETCH_ASSOC);
            $shift = $result_shift['title'];


            $sql_device = "SELECT
                ifnull(tbl_users.device_id,'') as device_id, employee.`NAME` FROM employee
                INNER JOIN tbl_users ON 
		        employee.staff_id = tbl_users.staff_id WHERE employee.staff_id = :staff_id";

            $devicestmt = $db->prepare($sql_device);
            $devicestmt->execute([
                ':staff_id' => $data['staff_id']
            ]);

            $result_device = $devicestmt->fetch(PDO::FETCH_ASSOC);
            $deviceId = $result_device['device_id'];
            $name = $result_device['NAME'];

            $title = "Shift Assignment";
            $message = "Dear {$name} you have been assigned {$shift} shift at {$location} on {$data['duty_date']}";

            error_log($message.'-'.$title.'-'.$deviceId.'-'.$title);

             saveNotification($db, $data['staff_id'], $title, $message);


            // Send to specific device
            $deviceId ? sendNotificationToDevice($deviceId, $title, $message) : null;

            echo json_encode(['success' => $result]);

            break;

        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['duty_id'], $data['status'])) {
                throw new Exception('Missing required fields');
            }

            $valid_statuses = ['pending', 'completed', 'absent'];
            if (!in_array($data['status'], $valid_statuses)) {
                throw new Exception('Invalid status value');
            }

            $query = "UPDATE staff_duties 
                    SET status = :status,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :duty_id";

            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':duty_id' => $data['duty_id'],
                ':status' => $data['status']
            ]);

            echo json_encode(['success' => $result]);
            break;

        case 'delete_duty':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['duty_id'])) {
                throw new Exception('Missing duty_id');
            }

            $query = "DELETE FROM staff_duties WHERE id = :duty_id";

            $stmt = $db->prepare($query);
            $result = $stmt->execute([':duty_id' => $data['duty_id']]);

            echo json_encode(['success' => $result]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    error_log("Duty rota error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $status_code = $e->getCode();
    if (!is_int($status_code) || $status_code < 100 || $status_code > 599) {
        $status_code = 400;
    }

    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}