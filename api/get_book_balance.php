<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once('../Connections/coop.php');
require_once('../libs/services/AccountBalanceCalculator.php');
try {
    $account_id = intval($_GET['account_id'] ?? 0);
    $periodid = intval($_GET['periodid'] ?? 0);
    
    if ($account_id <= 0 || $periodid <= 0) {
        throw new Exception('Invalid parameters');
    }
    
    $calculator = new AccountBalanceCalculator($coop, $database);
    $balance = $calculator->getAccountBalance($account_id, $periodid);
    
    echo json_encode([
        'success' => true,
        'balance' => $balance['balance'],
        'debit' => $balance['debit'],
        'credit' => $balance['credit']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
