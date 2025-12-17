// api/profile/get_emergency_contact.php
<?php
require_once '../config/Database.php';
require_once '../utils/JWTHandler.php';

header('Content-Type: application/json');

try {
    // Validate JWT token
    $headers = getallheaders();
    $jwt = new JWTHandler();
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = $jwt->validateToken($token);

    if (!$decoded) {
        throw new Exception('Invalid token', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT 
        nokfirstname,
        nokmiddlename,
        noklastname,
        noktel
    FROM nok 
    WHERE COOPID = :coop_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':coop_id', $decoded->coopId);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'nokfirstname' => '',
                'nokmiddlename' => '',
                'noklastname' => '',
                'noktel' => ''
            ]
        ]);
    }
} catch(Exception $e) {
    error_log("Get emergency contact error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}