<?php
require_once('Connections/coop.php');
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Permission denied.']); exit;
}
$id = intval($_POST['id'] ?? 0);
$deleted_by = $_SESSION['user_id'];
if (!$id) {
    echo json_encode(['error' => 'Invalid input.']); exit;
}
$stmt = $coop->prepare("UPDATE coop_transactions SET deleted_by=?, deleted_at=NOW() WHERE id=?");
$stmt->bind_param('ii', $deleted_by, $id);
$ok = $stmt->execute();
$stmt->close();
if ($ok) {
    echo json_encode(['success' => 'Transaction deleted.']);
} else {
    echo json_encode(['error' => 'Database error.']);
} 