<?php
// api/admin/search-members.php
// Admin API for searching members by name or Coop ID

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
    
    // Start session for admin authentication
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is admin
    if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] !== 'Admin') {
        throw new Exception('Admin access required', 403);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed', 405);
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $searchTerm = trim($_GET['q'] ?? '');
    
    if (strlen($searchTerm) < 2) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit();
    }
    
    $searchPattern = '%' . $searchTerm . '%';
    
    // Search members by name or Coop ID
    $query = "SELECT 
        CoopID,
        FirstName,
        MiddleName,
        LastName,
        CONCAT(FirstName, ' ', COALESCE(MiddleName, ''), ' ', LastName) as FullName,
        EmailAddress,
        MobileNumber
    FROM tblemployees
    WHERE (CoopID LIKE :search 
        OR FirstName LIKE :search 
        OR LastName LIKE :search 
        OR MiddleName LIKE :search
        OR CONCAT(FirstName, ' ', COALESCE(MiddleName, ''), ' ', LastName) LIKE :search)
    ORDER BY FirstName, LastName
    LIMIT 20";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':search' => $searchPattern]);
    
    $members = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $members[] = [
            'coop_id' => $row['CoopID'],
            'full_name' => trim($row['FullName']),
            'first_name' => $row['FirstName'],
            'middle_name' => $row['MiddleName'],
            'last_name' => $row['LastName'],
            'email' => $row['EmailAddress'],
            'mobile' => $row['MobileNumber'],
            'label' => $row['CoopID'] . ' - ' . trim($row['FullName']),
            'value' => $row['CoopID']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $members
    ]);
    
} catch (Exception $e) {
    error_log("Search members API error: " . $e->getMessage());
    $code = $e->getCode();
    $httpCode = is_numeric($code) ? intval($code) : 500;
    if ($httpCode < 100 || $httpCode > 599) {
        $httpCode = 500;
    }
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}

