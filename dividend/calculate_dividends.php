<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    $period_from = $_POST['period_from'] ?? null;
    $period_to = $_POST['period_to'] ?? null;

    if (!$period_from || !$period_to) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $query = "
        SELECT 
            t.COOPID,
            CONCAT(e.FirstName, ' ', e.LastName) as FullName,
            SUM(CASE WHEN t.TransactionPeriod <= :period_to THEN t.savingsAmount ELSE 0 END) as Savings,
            SUM(CASE WHEN t.TransactionPeriod <= :period_to THEN t.sharesAmount ELSE 0 END) as Shares,
            SUM(CASE WHEN t.TransactionPeriod BETWEEN :period_from AND :period_to THEN t.InterestPaid ELSE 0 END) as Interest,
           Bank_Sortcodes.Bank_Name,
            a.AccountNo,
            Bank_Sortcodes.bank_code
        FROM tbl_mastertransact t
        JOIN tblemployees e ON t.COOPID = e.CoopID
        LEFT JOIN tblaccountno a ON e.CoopID = a.COOPNO
		LEFT JOIN Bank_Sortcodes ON Bank_Sortcodes.bank_code = a.bank_code
        WHERE e.Status = 'Active'
        GROUP BY t.COOPID, e.FirstName, e.LastName, e.BankName, e.AccountNumber, a.bank_code
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':period_from' => $period_from,
        ':period_to' => $period_to
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>