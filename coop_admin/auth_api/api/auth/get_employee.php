<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Database.php';


$database = new Database();
$db = $database->getConnection();


try {
    if (!isset($_GET['staff_id'])) {
        throw new Exception('Staff ID is required');
    }

    $staff_id = $_GET['staff_id'];

    $query = "SELECT staff_id, EMAIL, MOBILE_NO, NAME FROM employee WHERE staff_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$staff_id]);

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        echo json_encode([
            'success' => true,
            'data' => $employee
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}