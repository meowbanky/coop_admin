<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    $member_ids_raw = $_POST['member_ids'] ?? null;
    $period_start = (int)($_POST['period_start'] ?? 185);
    $period_end = (int)($_POST['period_end'] ?? 196);

    if (!$member_ids_raw) {
        echo json_encode(['success' => false, 'message' => 'No member IDs provided']);
        exit;
    }

    $raw_input = array_filter(array_map('trim', explode("\n", str_replace(',', "\n", $member_ids_raw))));
    $member_overrides = [];
    $member_ids = [];

    foreach ($raw_input as $item) {
        if (strpos($item, ':') !== false) {
            list($id, $month) = explode(':', $item);
            $id = trim($id);
            $month = (int)trim($month);
            if ($id) {
                $member_ids[] = $id;
                $member_overrides[$id] = $month;
            }
        } else {
            $item = trim($item);
            if ($item) $member_ids[] = $item;
        }
    }

    if (empty($member_ids)) {
        echo json_encode(['success' => false, 'message' => 'Invalid member IDs']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($member_ids), '?'));
    $totalMonths = 12; // Jan-Dec

    $query = "
        WITH LastPeriods AS (
            SELECT COOPID, MAX(TransactionPeriod) as LastPeriod
            FROM tbl_mastertransact
            WHERE TransactionPeriod <= ?
            GROUP BY COOPID
        )
        SELECT 
            t.COOPID,
            CONCAT(e.FirstName, ' ', e.LastName) as FullName,
            lp.LastPeriod,
            p.PayrollPeriod as LastPeriodName,
            SUM(CASE WHEN t.TransactionPeriod <= lp.LastPeriod THEN t.savingsAmount ELSE 0 END) as Savings,
            SUM(CASE WHEN t.TransactionPeriod <= lp.LastPeriod THEN t.sharesAmount ELSE 0 END) as Shares,
            SUM(CASE WHEN t.TransactionPeriod BETWEEN ? AND ? THEN t.InterestPaid ELSE 0 END) as Interest,
            bs.Bank_Name,
            a.AccountNo,
            bs.bank_code
        FROM tbl_mastertransact t
        JOIN tblemployees e ON t.COOPID = e.CoopID
        JOIN LastPeriods lp ON t.COOPID = lp.COOPID
        LEFT JOIN tbpayrollperiods p ON lp.LastPeriod = p.id
        LEFT JOIN tblaccountno a ON e.CoopID = a.COOPNO
        LEFT JOIN Bank_Sortcodes bs ON bs.bank_code = a.bank_code
        WHERE t.COOPID IN ($placeholders)
        GROUP BY t.COOPID, e.FirstName, e.LastName, lp.LastPeriod, p.PayrollPeriod, bs.Bank_Name, a.AccountNo, bs.bank_code
    ";

    $stmt = $pdo->prepare($query);
    $params = array_merge([$period_end, $period_start, $period_end], $member_ids);
    
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $row) {
        $coopId = $row['COOPID'];
        
        // Months Active logic: Override from CSV/Manual or use LastPeriod month number
        // Jan=1, Feb=2, etc. (relative to start of year)
        if (isset($member_overrides[$coopId])) {
            $monthsActive = $member_overrides[$coopId];
        } else {
            // Map period ID back to 1-12. 185=1, 196=12
            $monthsActive = $row['LastPeriod'] - $period_start + 1;
        }

        // Clamp monthsActive between 0 and 12
        $monthsActive = max(0, min(12, $monthsActive));
        $coveragePerc = round(($monthsActive / $totalMonths) * 100, 2);

        $row['MonthsActive'] = $monthsActive;
        $row['CoveragePercentage'] = $coveragePerc;
        $results[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
