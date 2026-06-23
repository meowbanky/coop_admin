<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once('../Connections/coop.php');
$entry_id = isset($_GET['entry_id']) ? intval($_GET['entry_id']) : 0;
if ($entry_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid entry ID']);
    exit;
}
try {
    $sql = "SELECT 
                jel.*,
                a.account_code,
                a.account_name
            FROM coop_journal_entry_lines jel
            JOIN coop_accounts a ON jel.account_id = a.id
            WHERE jel.journal_entry_id = ?
            ORDER BY jel.line_number";
    
    $stmt = $coop->prepare($sql);
    $stmt->execute([$entry_id]);
    
    $lines = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lines[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'lines' => $lines
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
