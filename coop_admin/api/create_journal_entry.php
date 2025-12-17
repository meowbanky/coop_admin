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
    // Validate required fields
    if (empty($_POST['periodid']) || empty($_POST['entry_date']) || empty($_POST['description']) || empty($_POST['lines'])) {
        throw new Exception('Missing required fields');
    }
    
    $periodid = intval($_POST['periodid']);
    $entry_date = $_POST['entry_date'];
    $entry_type = $_POST['entry_type'] ?? 'manual';
    $description = trim($_POST['description']);
    $source_document = !empty($_POST['source_document']) ? trim($_POST['source_document']) : null;
    $created_by = $_SESSION['user_id'];
    
    // Parse journal lines
    $lines = [];
    foreach ($_POST['lines'] as $line_data) {
        $account_id = intval($line_data['account_id'] ?? 0);
        $debit = floatval($line_data['debit'] ?? 0);
        $credit = floatval($line_data['credit'] ?? 0);
        $line_description = trim($line_data['description'] ?? '');
        
        // Skip empty lines
        if ($account_id == 0 || ($debit == 0 && $credit == 0)) {
            continue;
        }
        
        $lines[] = [
            'account_id' => $account_id,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'description' => $line_description
        ];
    }
    
    if (count($lines) < 2) {
        throw new Exception('Journal entry must have at least 2 lines');
    }
    
    // Create accounting engine
    $accountingEngine = new AccountingEngine($coop, $database);
    
    // Create journal entry
    $result = $accountingEngine->createJournalEntry(
        $periodid,
        $entry_date,
        $entry_type,
        $description,
        $lines,
        $created_by,
        $source_document
    );
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    // Post the entry immediately
    $post_result = $accountingEngine->postEntry($result['entry_id']);
    
    if (!$post_result['success']) {
        throw new Exception('Entry created but failed to post: ' . $post_result['error']);
    }
    
    // Success
    echo json_encode([
        'success' => true,
        'entry_id' => $result['entry_id'],
        'entry_number' => $result['entry_number'],
        'total_amount' => $result['total_amount']
    ]);
    
} catch (Exception $e) {
    error_log("Manual journal entry error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
