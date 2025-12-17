<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once('../Connections/coop.php');
require_once('../libs/services/PeriodClosingProcessor.php');
try {
    $periodid = intval($_POST['periodid'] ?? 0);
    
    if ($periodid <= 0) {
        throw new Exception('Invalid period ID');
    }
    
    // Collect appropriation data
    $appropriation = [
        'surplus_amount' => floatval($_POST['surplus_amount'] ?? 0),
        'dividend' => floatval($_POST['dividend'] ?? 0),
        'interest_to_members' => floatval($_POST['interest_to_members'] ?? 0),
        'reserve_fund' => floatval($_POST['reserve_fund'] ?? 0),
        'bonus' => floatval($_POST['bonus'] ?? 0),
        'education_fund' => floatval($_POST['education_fund'] ?? 0),
        'honorarium' => floatval($_POST['honorarium'] ?? 0),
        'general_reserve' => floatval($_POST['general_reserve'] ?? 0),
        'welfare_fund' => floatval($_POST['welfare_fund'] ?? 0)
    ];
    
    // Calculate retained earnings
    $total_appropriated = array_sum(array_filter($appropriation, fn($k) => $k !== 'surplus_amount', ARRAY_FILTER_USE_KEY));
    $appropriation['retained_earnings'] = $appropriation['surplus_amount'] - $total_appropriated;
    
    // Close period
    $processor = new PeriodClosingProcessor($coop, $database);
    $result = $processor->closePeriod($periodid, $_SESSION['user_id'], $appropriation);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'entries_created' => $result['entries_created'] ?? [],
            'message' => $result['message']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'issues' => $result['issues'] ?? []
        ]);
    }
    
} catch (Exception $e) {
    error_log("Period closing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
