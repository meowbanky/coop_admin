<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once('../Connections/coop.php');
require_once('../libs/services/BankReconciliationService.php');
try {
    $reconciliation_data = [
        'periodid' => intval($_POST['periodid'] ?? 0),
        'bank_account_id' => intval($_POST['bank_account_id'] ?? 0),
        'reconciliation_date' => $_POST['reconciliation_date'] ?? date('Y-m-d'),
        'bank_statement_balance' => floatval($_POST['bank_statement_balance'] ?? 0),
        'book_balance' => floatval($_POST['book_balance'] ?? 0),
        'outstanding_deposits' => floatval($_POST['outstanding_deposits'] ?? 0),
        'outstanding_withdrawals' => floatval($_POST['outstanding_withdrawals'] ?? 0),
        'bank_charges' => floatval($_POST['bank_charges'] ?? 0),
        'bank_interest' => floatval($_POST['bank_interest'] ?? 0),
        'reconciled_by' => intval($_POST['reconciled_by'] ?? $_SESSION['user_id']),
        'notes' => trim($_POST['notes'] ?? '')
    ];
    
    if ($reconciliation_data['periodid'] <= 0 || $reconciliation_data['bank_account_id'] <= 0) {
        throw new Exception('Period and bank account are required');
    }
    
    $service = new BankReconciliationService($coop, $database);
    $result = $service->createReconciliation($reconciliation_data);
    
    if ($result['success']) {
        $adjusting_entries = ($reconciliation_data['bank_charges'] > 0 || $reconciliation_data['bank_interest'] > 0);
        
        echo json_encode([
            'success' => true,
            'reconciliation_id' => $result['reconciliation_id'],
            'is_balanced' => $result['is_balanced'],
            'variance' => $result['variance'],
            'adjusting_entries' => $adjusting_entries
        ]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Bank reconciliation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
