<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['SESS_MEMBER_ID'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once('../Connections/coop.php');
require_once('../libs/services/PeriodClosingProcessor.php');
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $periodid = intval($input['periodid'] ?? 0);
    $reason = trim($input['reason'] ?? '');
    
    if ($periodid <= 0) {
        throw new Exception('Invalid period ID');
    }
    
    if (empty($reason)) {
        throw new Exception('Reason for reopening is required');
    }
    
    $processor = new PeriodClosingProcessor($coop, $database);
    $result = $processor->reopenPeriod($periodid, $_SESSION['SESS_MEMBER_ID'], $reason);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Period reopening error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
