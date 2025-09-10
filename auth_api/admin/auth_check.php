<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}


// Optional: Check if the admin's session is still valid
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT u.id, e.is_admin 
    FROM tbl_users u
    JOIN employee e ON u.staff_id = e.staff_id
    WHERE u.id = ? AND e.is_admin = 1
");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>