<?php
require_once('Connections/coop.php');
header('Content-Type: application/json');

$type = $_REQUEST['type'] ?? '';
$action = $_REQUEST['action'] ?? '';

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    if (!$name || !$type) {
        echo json_encode(['error' => 'Category name and type required.']); exit;
    }
    $stmt = $coop->prepare("INSERT INTO coop_categories (name, type) VALUES (?, ?)");
    $stmt->bind_param('ss', $name, $type);
    $ok = $stmt->execute();
    if ($ok) echo json_encode(['success' => 'Category added.']);
    else echo json_encode(['error' => 'Category already exists or DB error.']);
    $stmt->close();
    exit;
}
if ($action === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if (!$id || !$name) { echo json_encode(['error' => 'ID and name required.']); exit; }
    $stmt = $coop->prepare("UPDATE coop_categories SET name=? WHERE id=?");
    $stmt->bind_param('si', $name, $id);
    $ok = $stmt->execute();
    if ($ok) echo json_encode(['success' => 'Category updated.']);
    else echo json_encode(['error' => 'Update failed.']);
    $stmt->close();
    exit;
}
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'ID required.']); exit; }
    $stmt = $coop->prepare("DELETE FROM coop_categories WHERE id=?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    if ($ok) echo json_encode(['success' => 'Category deleted.']);
    else echo json_encode(['error' => 'Delete failed.']);
    $stmt->close();
    exit;
}
// Default: fetch categories by type
if (!$type) { echo json_encode([]); exit; }
$stmt = $coop->prepare("SELECT id, name FROM coop_categories WHERE type=? ORDER BY name");
$stmt->bind_param('s', $type);
$stmt->execute();
$res = $stmt->get_result();
$cats = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
echo json_encode($cats); 