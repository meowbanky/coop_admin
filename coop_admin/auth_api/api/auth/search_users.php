<?php
// Set CORS headers FIRST - before any output
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 1728000');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set content type
header('Content-Type: application/json; charset=UTF-8');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $query = $_GET['query'] ?? '';
    
    if (empty($query)) {
        throw new Exception('Search query is required');
    }
    
    if (strlen($query) < 2) {
        throw new Exception('Search query must be at least 2 characters');
    }

    // Use the same database connection logic as login.php
    require_once __DIR__ . '/../../config/Database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Use PDO (same as login.php)
    // Search by both name and CoopID
    $sql = "SELECT CoopID, FirstName, LastName, MiddleName, EmailAddress, MobileNumber
            FROM tblemployees 
            WHERE (
                CONCAT(FirstName, ' ', IFNULL(MiddleName, ''), ' ', LastName) LIKE :query 
                OR FirstName LIKE :query
                OR LastName LIKE :query
                OR CoopID LIKE :query
            ) AND Status = 'Active'
            ORDER BY 
                CASE WHEN CoopID = :exact_query THEN 1 ELSE 2 END,
                LastName, FirstName
            LIMIT 10";

    $stmt = $db->prepare($sql);
    $searchQuery = "%$query%";
    $stmt->bindParam(':query', $searchQuery);
    $stmt->bindParam(':exact_query', $query); // For exact CoopID match priority
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $results
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    $errorDetails = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    // Log detailed error for debugging (only in development)
    if (ini_get('display_errors')) {
        $errorDetails['file'] = $e->getFile();
        $errorDetails['line'] = $e->getLine();
        $errorDetails['trace'] = $e->getTraceAsString();
    }
    
    error_log("Search Users API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    echo json_encode($errorDetails, JSON_UNESCAPED_UNICODE);
}