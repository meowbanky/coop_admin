<?php
if (ob_get_level()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 1728000');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../utils/JWTHandler.php';

try {
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

    error_log("Token: " . $token);

    $jwt = new JWTHandler();
    $userData = $jwt->validateToken($token);

    error_log("User Data: " . print_r($userData, true));

    if (!$userData || !isset($userData['user_id'])) {
        throw new Exception('Invalid token or missing user_id');
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT Bank_Name, bank_code FROM Bank_Sortcodes ORDER BY Bank_Name";
    $stmt = $db->prepare($query);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {
    error_log("Error in get banks list: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>