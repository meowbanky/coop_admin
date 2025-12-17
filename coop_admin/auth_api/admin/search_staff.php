<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
try {
    $db = $database->getConnection();
} catch (\Exception $e) {
    echo json_encode([]);
    exit;
}

$term = $_GET['term'] ?? '';
$term = "%$term%";

$query = "SELECT staff_id, NAME, MOBILE_NO, EMAIL, alternate_email 
          FROM employee 
          WHERE NAME LIKE ? AND  STATUSCD = 'A'
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->execute([$term]);

$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = [
        'label' => $row['NAME'],
        'value' => $row['NAME'],
        'staff_id' => $row['staff_id'],
        'NAME' => $row['NAME'],
        'MOBILE_NO' => $row['MOBILE_NO'],
        'EMAIL' => $row['EMAIL'],
        'alternate_email' => $row['alternate_email']
    ];
}

echo json_encode($results);