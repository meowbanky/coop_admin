<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    $period_from = $_POST['period_from'] ?? null;
    $period_to = $_POST['period_to'] ?? null;
    $excluded_ids_raw = $_POST['excluded_ids'] ?? '';

    if (!$period_from || !$period_to) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $excluded_ids = [];
    if (!empty($excluded_ids_raw)) {
        $excluded_ids = array_filter(array_map('trim', explode(',', $excluded_ids_raw)));
    }

    $exclusion_clause = "";
    if (!empty($excluded_ids)) {
        $placeholders = implode(',', array_fill(0, count($excluded_ids), '?'));
        $exclusion_clause = "AND t.COOPID NOT IN ($placeholders)";
    }

    error_log("Raw excluded_ids_raw from POST: '" . $excluded_ids_raw . "'");
    error_log("Parsed excluded_ids array: " . print_r($excluded_ids, true));

    $query = "
        SELECT 
            t.COOPID,
            CONCAT(e.FirstName, ' ', e.LastName) as FullName,
            SUM(CASE WHEN t.TransactionPeriod <= ? THEN t.savingsAmount ELSE 0 END) as Savings,
            SUM(CASE WHEN t.TransactionPeriod <= ? THEN t.sharesAmount ELSE 0 END) as Shares,
            SUM(CASE WHEN t.TransactionPeriod BETWEEN ? AND ? THEN t.InterestPaid ELSE 0 END) as Interest,
           Bank_Sortcodes.Bank_Name,
            a.AccountNo,
            Bank_Sortcodes.bank_code
        FROM tbl_mastertransact t
        JOIN tblemployees e ON t.COOPID = e.CoopID
        LEFT JOIN tblaccountno a ON e.CoopID = a.COOPNO
		LEFT JOIN Bank_Sortcodes ON Bank_Sortcodes.bank_code = a.bank_code
        WHERE e.Status = 'Active' $exclusion_clause
        GROUP BY t.COOPID, e.FirstName, e.LastName, e.BankName, e.AccountNumber, a.bank_code
    ";

    $stmt = $pdo->prepare($query);
    $params = array_merge([$period_to, $period_to, $period_from, $period_to], $excluded_ids);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>