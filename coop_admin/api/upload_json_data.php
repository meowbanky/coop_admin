<?php
/**
 * Upload JSON Data Endpoint
 * Processes and uploads data from OOUTH Salary API to database
 * Follows import_office.php logic for tbl_monthlycontribution and tbl_loansavings
 */

// Start output buffering
ob_start();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Set headers
header('Content-Type: application/json');

// Database connection
require_once(__DIR__ . '/../Connections/coop.php');

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    
    if (!$request) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($request['local_period']) || !isset($request['data']) || !is_array($request['data'])) {
        throw new Exception('Missing required fields: local_period and data');
    }
    
    $localPeriodId = (int)$request['local_period']; // Period ID for tbpayrollperiods
    $apiPeriodInfo = $request['api_period_info'] ?? null;
    $localPeriodInfo = $request['local_period_info'] ?? null;
    $resourceType = $request['resource_type'] ?? 'deduction';
    $resourceId = $request['resource_id'] ?? null;
    $resourceName = $request['resource_name'] ?? 'Unknown';
    $data = $request['data'];
    
    if (empty($data)) {
        throw new Exception('No data to upload');
    }
    
    if ($localPeriodId <= 0) {
        throw new Exception('Invalid local period ID');
    }
    
    // Select database
    mysqli_select_db($coop, $database);
    
    // Start transaction
    mysqli_begin_transaction($coop);
    
    $successCount = 0;
    $errorCount = 0;
    $notFound = [];
    $processedStaffIds = [];
    $errors = [];
    
    // Process each record (following import_office.php logic)
    foreach ($data as $record) {
        $staffId = trim((string)$record['staff_id']);
        $amount = floatval($record['amount']);
        
        // Skip invalid or non-numeric IDs
        if (!is_numeric($staffId) || $staffId <= 0) {
            continue;
        }
        
        // Get employee info from database
        $sqlStaff = "SELECT tblemployees.StaffID, tblemployees.status, tblemployees.CoopID, 
                     IFNULL(tbl_extra.Amount, 0) AS savings 
                     FROM tblemployees 
                     LEFT JOIN tbl_extra ON tblemployees.CoopID = tbl_extra.COOPID 
                     WHERE StaffID = ?";
        $stmt = mysqli_prepare($coop, $sqlStaff);
        mysqli_stmt_bind_param($stmt, "s", $staffId);
        mysqli_stmt_execute($stmt);
        $staffResult = mysqli_stmt_get_result($stmt);
        $staffRow = mysqli_fetch_assoc($staffResult);
        $staffFound = mysqli_num_rows($staffResult) > 0;
        mysqli_stmt_close($stmt);
        
        if ($staffFound) {
            $coopId = $staffRow['CoopID'];
            $loanSavings = floatval($staffRow['savings']);
            $newValue = $amount - $loanSavings;
            
            $processedStaffIds[] = $staffId;
            
            // 1. Update/Insert tbl_monthlycontribution
            $checkSql = "SELECT COUNT(*) AS count FROM tbl_monthlycontribution WHERE coopID = ? AND period = ?";
            $checkStmt = mysqli_prepare($coop, $checkSql);
            mysqli_stmt_bind_param($checkStmt, "si", $coopId, $localPeriodId);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_bind_result($checkStmt, $count);
            mysqli_stmt_fetch($checkStmt);
            mysqli_stmt_close($checkStmt);
            
            if ($count > 0) {
                // Update existing record
                $sql = "UPDATE tbl_monthlycontribution 
                        SET MonthlyContribution = ? 
                        WHERE coopID = ? AND period = ?";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "dsi", $newValue, $coopId, $localPeriodId);
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_monthlycontribution (coopID, MonthlyContribution, period) 
                        VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($coop, $sql);
                mysqli_stmt_bind_param($stmt, "sdi", $coopId, $newValue, $localPeriodId);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                
                // 2. Update/Insert tbl_loansavings
                $checkSql2 = "SELECT COUNT(*) AS count FROM tbl_loansavings WHERE COOPID = ? AND period = ?";
                $checkStmt2 = mysqli_prepare($coop, $checkSql2);
                mysqli_stmt_bind_param($checkStmt2, "si", $coopId, $localPeriodId);
                mysqli_stmt_execute($checkStmt2);
                mysqli_stmt_bind_result($checkStmt2, $count2);
                mysqli_stmt_fetch($checkStmt2);
                mysqli_stmt_close($checkStmt2);
                
                if ($count2 > 0) {
                    $sql2 = "UPDATE tbl_loansavings 
                            SET Amount = ? 
                            WHERE COOPID = ? AND period = ?";
                    $stmt2 = mysqli_prepare($coop, $sql2);
                    mysqli_stmt_bind_param($stmt2, "dsi", $loanSavings, $coopId, $localPeriodId);
                } else {
                    $sql2 = "INSERT INTO tbl_loansavings (COOPID, Amount, period) 
                            VALUES (?, ?, ?)";
                    $stmt2 = mysqli_prepare($coop, $sql2);
                    mysqli_stmt_bind_param($stmt2, "sdi", $coopId, $loanSavings, $localPeriodId);
                }
                
                if (mysqli_stmt_execute($stmt2)) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Failed to update loan savings for {$staffId}: " . mysqli_stmt_error($stmt2);
                }
                mysqli_stmt_close($stmt2);
            } else {
                $errorCount++;
                $errors[] = "Failed to update monthly contribution for {$staffId}: " . mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            // Staff not found - include name from API data if available
            $staffName = $record['name'] ?? 'Unknown';
            $notFound[] = [
                'staff_id' => $staffId,
                'name' => $staffName,
                'amount' => $amount
            ];
            $errorCount++;
        }
    }
    
    // Update records not in the uploaded data to 0 (following import_office.php logic)
    if (!empty($processedStaffIds)) {
        $src = implode(',', array_filter($processedStaffIds, 'is_numeric'));
        
        if (!empty($src)) {
            // Update MonthlyContribution for non-matching StaffIDs
            $update1 = "UPDATE tbl_monthlycontribution 
                       SET MonthlyContribution = 0 
                       WHERE period = ? AND CoopID IN (
                           SELECT tblemployees.CoopID 
                           FROM tblemployees 
                           WHERE StaffID NOT IN ($src)
                       )";
            $stmt1 = mysqli_prepare($coop, $update1);
            mysqli_stmt_bind_param($stmt1, "i", $localPeriodId);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);
            
            // Update LoanSavings for non-matching StaffIDs
            $update2 = "UPDATE tbl_loansavings 
                        SET Amount = 0 
                        WHERE period = ? AND COOPID IN (
                            SELECT tblemployees.CoopID 
                            FROM tblemployees 
                            WHERE StaffID NOT IN ($src)
                        )";
            $stmt2 = mysqli_prepare($coop, $update2);
            mysqli_stmt_bind_param($stmt2, "i", $localPeriodId);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
        }
    }
    
    // Commit transaction if most records were successful
    if ($successCount > 0 && $errorCount < ($successCount / 2)) {
        mysqli_commit($coop);
        
        // Format not found staff with names
        $displayNF = 'All records processed successfully.';
        $notFoundDetails = [];
        
        if (!empty($notFound)) {
            $notFoundList = [];
            foreach ($notFound as $staff) {
                $notFoundList[] = "{$staff['staff_id']} ({$staff['name']}) - ₦" . number_format($staff['amount'], 2);
                $notFoundDetails[] = $staff;
            }
            $displayNF = 'Staff not found in database: ' . implode(', ', $notFoundList);
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Upload completed: {$successCount} records processed successfully",
            'details' => "{$successCount} succeeded, {$errorCount} failed",
            'not_found' => $displayNF,
            'data' => [
                'total' => count($data),
                'success' => $successCount,
                'errors' => $errorCount,
                'not_found_count' => count($notFound),
                'not_found_list' => $notFoundDetails,
                'error_messages' => $errors
            ]
        ]);
    } else {
        // Rollback if too many errors
        mysqli_rollback($coop);
        throw new Exception("Upload failed: Too many errors ({$errorCount} errors out of " . count($data) . " records). Transaction rolled back.");
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($coop)) {
        mysqli_rollback($coop);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

// Close database connection
if (isset($coop)) {
    mysqli_close($coop);
}

ob_end_flush();