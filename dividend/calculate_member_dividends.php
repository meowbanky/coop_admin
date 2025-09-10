<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    $member_names = $_POST['member_names'] ?? null;

    if (!$member_names) {
        echo json_encode(['success' => false, 'message' => 'No member names provided']);
        exit;
    }

    // Split and clean member names
    $names = array_filter(array_map('trim', explode(',', $member_names)));
    $placeholders = implode(',', array_fill(0, count($names), '?'));

    $startPeriod = 185; // Jan 2024
    $endPeriod = 196;   // Dec 2024
    $totalMonths = $endPeriod - $startPeriod + 1; // 12 month

    // Query to get each member's last transaction period and sums up to that period
    $query = "
        WITH LastPeriods AS (
            SELECT COOPID, MAX(TransactionPeriod) as LastPeriod
            FROM tbl_mastertransact
            GROUP BY COOPID
        )
        SELECT 
            t.COOPID,LastPeriod,
            CONCAT(e.FirstName, ' ', e.LastName) as FullName,
            lp.LastPeriod,
            SUM(CASE WHEN t.TransactionPeriod <= lp.LastPeriod THEN t.savingsAmount ELSE 0 END) as Savings,
            SUM(CASE WHEN t.TransactionPeriod <= lp.LastPeriod THEN t.sharesAmount ELSE 0 END) as Shares,
            SUM(CASE WHEN t.TransactionPeriod BETWEEN 185 AND 196 THEN t.InterestPaid ELSE 0 END) as Interest,
            Bank_Sortcodes.Bank_Name,
            a.AccountNo,
            Bank_Sortcodes.bank_code,
            CASE 
                WHEN lp.LastPeriod >= 196 THEN 100
                WHEN lp.LastPeriod < 185 THEN 0
                ELSE ROUND(((lp.LastPeriod - 184) / $totalMonths) * 100, 2)
            END as CoveragePercentage
        FROM tbl_mastertransact t
        JOIN tblemployees e ON t.COOPID = e.CoopID
        JOIN LastPeriods lp ON t.COOPID = lp.COOPID
        LEFT JOIN tblaccountno a ON e.CoopID = a.COOPNO
        JOIN Bank_Sortcodes ON Bank_Sortcodes.bank_code = a.bank_code
        AND e.CoopID IN ($placeholders)
        GROUP BY t.COOPID, e.FirstName, e.LastName, lp.LastPeriod, Bank_Sortcodes.Bank_Name, a.AccountNo, Bank_Sortcodes.bank_code
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($names);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>