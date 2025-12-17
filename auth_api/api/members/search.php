<?php
// api/members/search.php
// Search coop members by name

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

    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $decodedToken = $jwt->validateToken($token);

    if (!$decodedToken) {
        throw new Exception('Invalid token', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

    if (empty($query) || strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit();
    }

    // Search members by name (FirstName, MiddleName, LastName)
    $searchQuery = "SELECT 
        CoopID,
        CONCAT(FirstName, ' ', IFNULL(MiddleName, ''), ' ', LastName) AS full_name,
        FirstName,
        MiddleName,
        LastName,
        EmailAddress,
        MobileNumber,
        Department,
        JobPosition,
        Status
    FROM tblemployees
    WHERE Status = 'Active'
    AND (
        FirstName LIKE :query 
        OR LastName LIKE :query 
        OR MiddleName LIKE :query
        OR CONCAT(FirstName, ' ', IFNULL(MiddleName, ''), ' ', LastName) LIKE :query
        OR CoopID LIKE :query
    )
    ORDER BY LastName, FirstName
    LIMIT :limit";

    $stmt = $db->prepare($searchQuery);
    $searchTerm = '%' . $query . '%';
    $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $members = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $members[] = [
            'coop_id' => $row['CoopID'],
            'full_name' => trim($row['full_name']),
            'first_name' => $row['FirstName'],
            'middle_name' => $row['MiddleName'],
            'last_name' => $row['LastName'],
            'email' => $row['EmailAddress'],
            'mobile' => $row['MobileNumber'],
            'department' => $row['Department'],
            'job_position' => $row['JobPosition']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $members
    ]);

} catch (Exception $e) {
    error_log("Member search error: " . $e->getMessage());
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

