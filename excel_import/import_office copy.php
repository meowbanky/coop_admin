<?php
ini_set('max_execution_time', '0');
require_once('../Connections/coop.php');
session_start();
require 'vendor/autoload.php'; // PhpSpreadsheet autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;

// --------------------
// SESSION & AUTH CHECK
// --------------------
if (!isset($_SESSION['SESS_MEMBER_ID']) || empty($_SESSION['SESS_MEMBER_ID'])) {
    exit('<script>parent.document.getElementById("information").innerHTML="Unauthorized access.";</script>');
}

// --------------------
// INPUT VALIDATION
// --------------------
$periodID = filter_input(INPUT_POST, 'period', FILTER_VALIDATE_INT);
$hasHeaders = isset($_POST['hasHeaders']);

if (!$periodID || $periodID <= 0) {
    exit('<script>parent.document.getElementById("information").innerHTML="Invalid or missing PeriodID.";</script>');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    exit('<script>parent.document.getElementById("information").innerHTML="No file uploaded or upload error.";</script>');
}

// --------------------
// FILE VALIDATION
// --------------------
$excelFile = $_FILES['file']['tmp_name'];

// Limit file size to 10MB
if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
    exit('<script>parent.document.getElementById("information").innerHTML="File size exceeds 10MB limit.";</script>');
}

// Restrict allowed MIME types
$allowedMimeTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'text/csv'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $excelFile);
finfo_close($finfo);

if (!in_array($mime, $allowedMimeTypes)) {
    exit('<script>parent.document.getElementById("information").innerHTML="Invalid file type.";</script>');
}

// --------------------
// INITIALIZE
// --------------------
$recordtime = date('Y-m-d H:i:s');
$startRow = $hasHeaders ? 3 : 0;
$notfound = [];
$source = [];

echo '<div id="progress" style="border:1px solid #ccc; border-radius:5px;"></div>';
echo '<div id="information" style="width:100%"></div>';
echo '<div id="message" style="width:100%"></div>';

// --------------------
// PROCESS EXCEL
// --------------------
mysqli_begin_transaction($coop, MYSQLI_TRANS_START_READ_WRITE);

try {
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray();

    $totalRows = count($data);

    for ($i = $startRow; $i < $totalRows; $i++) {
        // Skip empty StaffID
        if (!isset($data[$i][1]) || $data[$i][1] === '' || $data[$i][1] === null) continue;

        $id = trim((string)$data[$i][1]);
        $value = isset($data[$i][3]) && $data[$i][3] !== '' ? floatval(str_replace(',', '', $data[$i][3])) : 0;

        if (!is_numeric($id) || $id <= 0) continue;

        $percent = intval(($i - $startRow) * 100 / ($totalRows - $startRow)) . "%";
        $source[] = $id;

        // --------------------
        // FETCH EMPLOYEE DATA
        // --------------------
        mysqli_select_db($coop, $database);
        $sqlStaff_id = "SELECT tblemployees.StaffID, tblemployees.status, tblemployees.CoopID, 
                        IFNULL(tbl_extra.Amount,0) AS savings 
                        FROM tblemployees 
                        LEFT JOIN tbl_extra ON tblemployees.CoopID = tbl_extra.COOPID 
                        WHERE StaffID = ?";
        $stmt = mysqli_prepare($coop, $sqlStaff_id);
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row_Staff_id = mysqli_fetch_assoc($result);
        $total_Staff_id = mysqli_num_rows($result);
        mysqli_stmt_close($stmt);

        if ($total_Staff_id > 0) {
            $coop_id = $row_Staff_id['CoopID'];
            $new_value = $value - floatval($row_Staff_id['savings']);
            $loan_savings = floatval($row_Staff_id['savings']);

            // --------------------
            // MONTHLY CONTRIBUTION
            // --------------------
            $check_sql = "SELECT COUNT(*) AS count FROM tbl_monthlycontribution WHERE coopID = ? AND period = ?";
            $check_stmt = mysqli_prepare($coop, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "si", $coop_id, $periodID);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $count);
            mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt);

            if ($count > 0) {
                $sql = "UPDATE tbl_monthlycontribution SET MonthlyContribution = ? WHERE coopID = ? AND period = ?";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "dsi", $new_value, $coop_id, $periodID);
            } else {
                $sql = "INSERT INTO tbl_monthlycontribution (coopID, MonthlyContribution, period) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "sdi", $coop_id, $new_value, $periodID);
            }
            mysqli_stmt_execute($stmt) or throw new Exception('Database error on monthly contribution.');
            mysqli_stmt_close($stmt);

            // --------------------
            // LOAN SAVINGS
            // --------------------
            $check_sql = "SELECT COUNT(*) AS count FROM tbl_loansavings WHERE COOPID = ? AND period = ?";
            $check_stmt = mysqli_prepare($coop, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "si", $coop_id, $periodID);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $count);
            mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt);

            if ($count > 0) {
                $sql = "UPDATE tbl_loansavings SET Amount = ? WHERE COOPID = ? AND period = ?";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "dsi", $loan_savings, $coop_id, $periodID);
            } else {
                $sql = "INSERT INTO tbl_loansavings (COOPID, Amount, period) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "sdi", $coop_id, $loan_savings, $periodID);
            }
            mysqli_stmt_execute($stmt) or throw new Exception('Database error on loan savings.');
            mysqli_stmt_close($stmt);
        } else {
            $notfound[] = "$id - $value";
        }

        // --------------------
        // PROGRESS BAR
        // --------------------
        echo str_repeat(' ', 1024 * 64);
        echo '<script>
            parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $percent . ';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); text-align:center;color:white;height:35px;display:block;\">' . $percent . '</div>";
        </script>';
        ob_flush(); flush();
    }

    // --------------------
    // UPDATE NON-MATCHING STAFF
    // --------------------
    $ids = array_filter($source, 'is_numeric');
    $src = !empty($ids) ? implode(',', $ids) : '0';

    // MonthlyContribution
    $update = "UPDATE tbl_monthlycontribution 
               SET MonthlyContribution = 0 
               WHERE period = ? AND CoopID IN (
                   SELECT CoopID FROM tblemployees WHERE StaffID NOT IN ($src)
               )";
    $stmt = mysqli_prepare($coop, $update);
    mysqli_stmt_bind_param($stmt, "i", $periodID);
    mysqli_stmt_execute($stmt) or throw new Exception('Error updating non-matching monthly contributions.');
    mysqli_stmt_close($stmt);

    // LoanSavings
    $update2 = "UPDATE tbl_loansavings 
                SET Amount = 0 
                WHERE COOPID IN (
                    SELECT CoopID FROM tblemployees WHERE StaffID NOT IN ($src)
                )";
    mysqli_query($coop, $update2) or throw new Exception('Error updating non-matching loan savings.');

    // --------------------
    // COMMIT
    // --------------------
    mysqli_commit($coop);

    $displayNF = !empty($notfound) ? implode(', ', $notfound) : 'All records processed successfully.';
    echo str_repeat(' ', 1024 * 64);
    echo '<script>
        parent.document.getElementById("information").innerHTML=' . json_encode($displayNF) . ';
        parent.document.getElementById("message").innerHTML=' . json_encode("Import completed successfully.") . ';
    </script>';

} catch (Exception $e) {
    mysqli_rollback($coop);
    error_log("Excel import failed: " . $e->getMessage());
    echo '<script>
        parent.document.getElementById("information").innerHTML=' . json_encode("Error during import.") . ';
    </script>';
}

// --------------------
// CLEANUP
// --------------------
unlink($excelFile);
mysqli_close($coop);
?>