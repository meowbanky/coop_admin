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

    // Use direct connection from coop.php (supports both mysqli and PDO)
    $coopPath = __DIR__ . '/../../../../Connections/coop.php';
    if (!file_exists($coopPath)) {
        throw new Exception('Database configuration file not found at: ' . $coopPath);
    }
    
    require_once $coopPath;
    
    // Try PDO first (if available), then fallback to mysqli
    if (isset($conn) && $conn instanceof PDO) {
        $sql = "SELECT CoopID, FirstName, LastName, EmailAddress 
                FROM tblemployees 
                WHERE CONCAT(FirstName, ' ', LastName) LIKE :query AND Status = 'Active'
                LIMIT 10";

        $stmt = $conn->prepare($sql);
        $searchQuery = "%$query%";
        $stmt->bindParam(':query', $searchQuery);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($coop) && (($coop instanceof mysqli) || is_resource($coop))) {
        // Use mysqli
        $searchQuery = "%$query%";
        $sql = "SELECT CoopID, FirstName, LastName, EmailAddress 
                FROM tblemployees 
                WHERE CONCAT(FirstName, ' ', LastName) LIKE ? AND Status = 'Active'
                LIMIT 10";
        
        $stmt = mysqli_prepare($coop, $sql);
        if (!$stmt) {
            throw new Exception('Database query preparation failed: ' . mysqli_error($coop));
        }
        
        mysqli_stmt_bind_param($stmt, "s", $searchQuery);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Query execution failed: ' . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            throw new Exception('Failed to get result: ' . mysqli_error($coop));
        }
        
        $results = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
        mysqli_stmt_close($stmt);
    } else {
        // Debug: Check what's available
        $available = [];
        if (isset($conn)) $available[] = 'conn (type: ' . gettype($conn) . ')';
        if (isset($coop)) $available[] = 'coop (type: ' . gettype($coop) . ')';
        throw new Exception('Database connection not available. Available: ' . implode(', ', $available));
    }

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