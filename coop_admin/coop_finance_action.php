<?php
require_once('Connections/coop.php');
session_start();
header('Content-Type: application/json');

// Validate input
$periodid = intval($_POST['periodid'] ?? 0);
$type = $_POST['type'] ?? '';
$category_id = intval($_POST['category_id'] ?? 0);
$amount = floatval(str_replace(',', '', $_POST['amount'] ?? '0'));
$description = trim($_POST['description'] ?? '');
$recorded_by = $_SESSION['user_id'] ?? null;

if (!$periodid || !$type || !$category_id || !$amount) {
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}
if (!in_array($type, ['income', 'expenditure'])) {
    echo json_encode(['error' => 'Invalid type.']);
    exit;
}

$stmt = $coop->prepare("INSERT INTO coop_transactions (periodid, amount, type, category_id, description, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('idsssi', $periodid, $amount, $type, $category_id, $description, $recorded_by);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success' => 'Transaction recorded.']);
} else {
    echo json_encode(['error' => 'Database error.']);
} 