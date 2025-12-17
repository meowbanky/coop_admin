<?php
require_once('Connections/coop.php');
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Permission denied.']); exit;
}
$id = intval($_POST['id'] ?? 0);
$amount = floatval(str_replace(',', '', $_POST['amount'] ?? '0'));
$description = trim($_POST['description'] ?? '');
$updated_by = $_SESSION['user_id'];
if (!$id || $amount <= 0) {
    echo json_encode(['error' => 'Invalid input.']); exit;
}
$stmt = $coop->prepare("UPDATE coop_transactions SET amount=?, description=?, updated_by=?, updated_at=NOW() WHERE id=?");
$stmt->bind_param('dsii', $amount, $description, $updated_by, $id);
$ok = $stmt->execute();
$stmt->close();
if ($ok) {
    echo json_encode(['success' => 'Transaction updated.']);
} else {
    echo json_encode(['error' => 'Database error.']);
} 