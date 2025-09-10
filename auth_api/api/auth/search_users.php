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


require_once __DIR__ . '/../../config/Database.php';
header('Content-Type: application/json');

try {
    $query = $_GET['query'] ?? '';
    if (strlen($query) < 3) {
        throw new Exception('Search query must be at least 3 characters');
    }

    $database = new Database();
    $db = $database->getConnection();

    $sql = "SELECT CoopID, FirstName, LastName, EmailAddress 
            FROM tblemployees 
            WHERE CONCAT(FirstName, ' ', LastName) LIKE :query AND Status = 'Active'
            LIMIT 10";

    $stmt = $db->prepare($sql);
    $searchQuery = "%$query%";
    $stmt->bindParam(':query', $searchQuery);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>