<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once('../Connections/coop.php');
require_once('../libs/services/AccountingEngine.php');
try {
    $memberid = intval($_POST['memberid'] ?? 0);
    $periodid = intval($_POST['periodid'] ?? 0);
    
    if ($memberid <= 0 || $periodid <= 0) {
        throw new Exception('Invalid member or period ID');
    }
    
    $engine = new AccountingEngine($coop, $database);
    
    // Find all journal entries for this member/period
    $sql = "SELECT id, entry_number, source_document, description, total_amount
            FROM coop_journal_entries
            WHERE periodid = ? 
            AND (source_document LIKE ? OR source_document LIKE ?)
            AND status = 'posted'
            ORDER BY id ASC";
    
    $stmt = mysqli_prepare($coop, $sql);
    $contrib_pattern = "CONTRIB-{$memberid}-%";
    $loan_pattern = "LOAN-{$memberid}-%";
    mysqli_stmt_bind_param($stmt, "iss", $periodid, $contrib_pattern, $loan_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $entries_to_reverse = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $entries_to_reverse[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    if (empty($entries_to_reverse)) {
        echo json_encode([
            'success' => true,
            'message' => 'No accounting entries found to reverse',
            'entries_reversed' => 0
        ]);
        exit;
    }
    
    // Reverse each entry
    $reversed_count = 0;
    $errors = [];
    
    foreach ($entries_to_reverse as $entry) {
        $result = $engine->reverseEntry($entry['id'], $_SESSION['user_id'], "Reversal for transaction correction");
        
        if ($result['success']) {
            $reversed_count++;
        } else {
            $errors[] = "Entry {$entry['entry_number']}: {$result['error']}";
        }
    }
    
    if ($reversed_count == count($entries_to_reverse)) {
        echo json_encode([
            'success' => true,
            'message' => "{$reversed_count} journal entries reversed successfully",
            'entries_reversed' => $reversed_count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => "Only {$reversed_count} of " . count($entries_to_reverse) . " entries reversed. Errors: " . implode('; ', $errors)
        ]);
    }
    
} catch (Exception $e) {
    error_log("Transaction reversal error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
