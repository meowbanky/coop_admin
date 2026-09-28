<?php
require_once('../includes/session_helper.php');
require_once('../includes/admin_access_helper.php');
require_once('../includes/import_response_helper.php');

initSession();

// This import rewrites members' contributions: admins only. Checked before the
// upload or the database is touched.
$accessError = findAdminAccessError($_SESSION);
if ($accessError !== null) {
    reportImportFailure(403, $accessError);
    exit;
}

ini_set('max_execution_time', '0');
require_once('../Connections/coop.php');
$recordtime = date('Y-m-d H:i:s');
require 'vendor/autoload.php'; // Load Composer autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;

$notfound = [];
$source = [];
$excelFile = $_FILES["file"]["tmp_name"];
$hasHeaders = isset($_POST['hasHeaders']);
$periodID = isset($_POST['period']) ? (int)$_POST['period'] : 0;
$startRow = $hasHeaders ? 3 : 0; // Adjust starting row based on headers

// Validate PeriodID
if ($periodID <= 0) {
    reportImportFailure(400, 'Invalid or missing PeriodID.');
    exit;
}

// Progress and information divs
?>
<div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
<div id="information" style="width:100%"></div>
<div id="message" style="width:100%"></div>
<?php

// Start transaction
mysqli_begin_transaction($coop);

// Process Excel data
try {
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray();

    for ($i = $startRow; $i < count($data); $i++) {
        // Skip rows where StaffID is null, empty, or not numeric
        if (!isset($data[$i][1]) || $data[$i][1] === '' || $data[$i][1] === null) {
            continue;
        }

        $id = trim((string)$data[$i][1]); // StaffID
        $value = isset($data[$i][3]) && $data[$i][3] !== '' && $data[$i][3] !== null ? str_replace(",", "", $data[$i][3]) : 0; // Amount

        // Skip invalid or non-numeric IDs
        if (!is_numeric($id) || $id <= 0) {
            continue;
        }

        $percent = intval(($i - $startRow) / (count($data) - $startRow) * 100) . "%";
        $source[] = $id; // Add valid ID to source array

        mysqli_select_db($coop, $database);
        $sqlStaff_id = "SELECT tblemployees.StaffID, tblemployees.status, tblemployees.CoopID, 
                        IFNULL(tbl_extra.Amount, 0) AS savings 
                        FROM tblemployees 
                        LEFT JOIN tbl_extra ON tblemployees.CoopID = tbl_extra.COOPID
                        WHERE StaffID = ?
                        ORDER BY (tblemployees.status = 'Active') DESC
                        LIMIT 1"; // a returning member shares the StaffID of their old record: credit the active one
        $stmt = mysqli_prepare($coop, $sqlStaff_id);
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $Staff_id = mysqli_stmt_get_result($stmt);
        $row_Staff_id = mysqli_fetch_assoc($Staff_id);
        $total_Staff_id = mysqli_num_rows($Staff_id);
        mysqli_stmt_close($stmt);

        if ($total_Staff_id > 0) {
            // if ($row_Staff_id['status'] == 'In-Active') {
            //     $notfound[] = "$id - $value";
            //     continue;
            // }
            $coop_id = $row_Staff_id['CoopID'];
            $new_value = $value - floatval($row_Staff_id['savings']);
            $loan_savings = floatval($row_Staff_id['savings']);

            // Check if record exists for coopID and period
            $check_sql = "SELECT COUNT(*) AS count FROM tbl_monthlycontribution WHERE coopID = ? AND period = ?";
            $check_stmt = mysqli_prepare($coop, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "si", $coop_id, $periodID);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $count);
            mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt); 

            if ($count > 0) {
                // Update existing record
                $sql = "UPDATE tbl_monthlycontribution 
                        SET MonthlyContribution = ? 
                        WHERE coopID = ? AND period = ?";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "dsi", $new_value, $coop_id, $periodID);
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_monthlycontribution (coopID, MonthlyContribution, period) 
                        VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "sdi", $coop_id, $new_value, $periodID);
            }
            mysqli_stmt_execute($stmt) or throw new Exception(mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);

            // Check if record exists for coopID and period
            $check_sql = "SELECT COUNT(*) AS count FROM tbl_loansavings WHERE COOPID = ? AND period = ?";
            $check_stmt = mysqli_prepare($coop, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "si", $coop_id, $periodID);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $count);
            mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt);

    

            if ($count > 0) {
                $sql = "UPDATE tbl_loansavings 
                        SET Amount = ? 
                        WHERE COOPID = ? AND period = ?";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "dsi", $loan_savings, $coop_id, $periodID);
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_loansavings (COOPID, Amount, period) 
                        VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "sdi", $coop_id, $loan_savings, $periodID);
            }
            mysqli_stmt_execute($stmt) or throw new Exception(mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            
        } else {
            $notfound[] = "$id - $value";
        }

        // Update progress bar
        echo str_repeat(' ', 1024 * 64);
        echo '<script>
            parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $percent . ';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); text-align:center;color:white;height:35px;display:block;\">' . $percent . '</div>";
        </script>';
        ob_flush();
        flush();
    }

    // Build $src string for valid IDs only
    $src = implode(',', array_filter($source, 'is_numeric'));
    if (empty($src)) {
        $src = '0'; // Prevent empty IN clause
    }

    // Update MonthlyContribution for non-matching StaffIDs for the specified period
    $update = "UPDATE tbl_monthlycontribution 
               SET MonthlyContribution = 0 
               WHERE period = ? AND CoopID IN (
                   SELECT tblemployees.CoopID 
                   FROM tblemployees 
                   WHERE StaffID NOT IN ($src)
               )";
    $stmt = mysqli_prepare($coop, $update);
    mysqli_stmt_bind_param($stmt, "i", $periodID);
    mysqli_stmt_execute($stmt) or throw new Exception(mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    // Update LoanSavings for non-matching StaffIDs
    $update2 = "UPDATE tbl_loansavings 
                SET Amount = 0 
                WHERE COOPID IN (
                    SELECT tblemployees.CoopID 
                    FROM tblemployees 
                    WHERE StaffID NOT IN ($src)
                )";
    mysqli_select_db($coop, $database);
    mysqli_query($coop, $update2) or throw new Exception(mysqli_error($coop));

    // Commit transaction
    mysqli_commit($coop);

    // Display not found information
    $displayNF = !empty($notfound) ? implode(', ', $notfound) : 'All records processed successfully.';
    echo str_repeat(' ', 1024 * 64);
    echo '<script>
        parent.document.getElementById("information").innerHTML="' . escapeForInlineScript($displayNF) . '";
        parent.document.getElementById("message").innerHTML="Import completed successfully.";
    </script>';
} catch (Throwable $e) {
    // Throwable, not Exception: a TypeError from a failed prepare must also
    // roll back and be reported instead of ending the response mid-import.
    mysqli_rollback($coop);
    // The detail goes to the log; the browser gets a fixed message only.
    error_log('Import failed (' . get_class($e) . ' at ' . $e->getFile() . ':' . $e->getLine() . '): ' . $e->getMessage());
    reportImportFailure(500, describeImportFailure($e));
}

ob_flush();
flush();

// Close connection
mysqli_close($coop);
?>