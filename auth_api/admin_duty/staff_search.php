<?php
header('Content-Type: application/json');
require_once '../config/Database.php';
require_once '../utils/JWTHandler.php';

// Verify token...

$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$sql = "SELECT
	employee.staff_id, 
	employee.`NAME` AS `name`, 
	tbl_dept.dept
FROM
	employee
	INNER JOIN
	tbl_dept
	ON 
		employee.DEPTCD = tbl_dept.dept_id
        WHERE (NAME LIKE :query OR staff_id LIKE :query) AND STATUSCD = 'A' 
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute([':query' => "%$query%"]);

echo json_encode([
    'success' => true,
    'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);