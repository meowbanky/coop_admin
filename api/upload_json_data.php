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
    
    // Select database (PDO handles this in connection, but ensuring we are good)
    // No need for mysqli_select_db
    
    // Start transaction
    $coop->beginTransaction();
    
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
                     WHERE StaffID = ? AND Status = 'Active'";
        $stmt = $coop->prepare($sqlStaff);
        $stmt->execute([$staffId]);
        $staffRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $staffFound = $staffRow ? true : false;
        
        if ($staffFound) {
            $coopId = $staffRow['CoopID'];
            $loanSavings = floatval($staffRow['savings']);
            $newValue = $amount - $loanSavings;
            
            $processedStaffIds[] = $staffId;
            
            // 1. Update/Insert tbl_monthlycontribution
            $checkSql = "SELECT COUNT(*) AS count FROM tbl_monthlycontribution WHERE coopID = ? AND period = ?";
            $checkStmt = $coop->prepare($checkSql);
            $checkStmt->execute([$coopId, $localPeriodId]);
            $count = $checkStmt->fetchColumn();
            
            if ($count > 0) {
                // Update existing record
                $sql = "UPDATE tbl_monthlycontribution 
                        SET MonthlyContribution = ? 
                        WHERE coopID = ? AND period = ?";
                $stmt = $coop->prepare($sql);
                $stmt->execute([$newValue, $coopId, $localPeriodId]);
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_monthlycontribution (coopID, MonthlyContribution, period) 
                        VALUES (?, ?, ?)";
                $stmt = $coop->prepare($sql);
                $stmt->execute([$coopId, $newValue, $localPeriodId]);
            }
            
            // 2. Update/Insert tbl_loansavings
            $checkSql2 = "SELECT COUNT(*) AS count FROM tbl_loansavings WHERE COOPID = ? AND period = ?";
            $checkStmt2 = $coop->prepare($checkSql2);
            $checkStmt2->execute([$coopId, $localPeriodId]);
            $count2 = $checkStmt2->fetchColumn();
            
            if ($count2 > 0) {
                $sql2 = "UPDATE tbl_loansavings 
                        SET Amount = ? 
                        WHERE COOPID = ? AND period = ?";
                $stmt2 = $coop->prepare($sql2);
                $success = $stmt2->execute([$loanSavings, $coopId, $localPeriodId]);
            } else {
                $sql2 = "INSERT INTO tbl_loansavings (COOPID, Amount, period) 
                        VALUES (?, ?, ?)";
                $stmt2 = $coop->prepare($sql2);
                $success = $stmt2->execute([$coopId, $loanSavings, $localPeriodId]);
            }
            
            if ($success) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Failed to update loan savings for {$staffId}";
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
            $srcArray = array_fill(0, count(array_filter($processedStaffIds, 'is_numeric')), '?');
            $srcPlaceholders = implode(',', $srcArray);
            
            $update1 = "UPDATE tbl_monthlycontribution 
                       SET MonthlyContribution = 0 
                       WHERE period = ? AND CoopID IN (
                           SELECT tblemployees.CoopID 
                           FROM tblemployees 
                           WHERE StaffID NOT IN ($srcPlaceholders)
                       )";
            $stmt1 = $coop->prepare($update1);
            $params = array_merge([$localPeriodId], array_filter($processedStaffIds, 'is_numeric'));
            $stmt1->execute($params);
            
            // Update LoanSavings for non-matching StaffIDs
            $update2 = "UPDATE tbl_loansavings 
                        SET Amount = 0 
                        WHERE period = ? AND COOPID IN (
                            SELECT tblemployees.CoopID 
                            FROM tblemployees 
                            WHERE StaffID NOT IN ($srcPlaceholders)
                        )";
            $stmt2 = $coop->prepare($update2);
            // Re-use params as they are the same structure
            $stmt2->execute($params);
        }
    }
    
    // Commit transaction if most records were successful
    if ($successCount > 0 && $errorCount < ($successCount / 2)) {
        $coop->commit();
        
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
        $coop->rollBack();
        throw new Exception("Upload failed: Too many errors ({$errorCount} errors out of " . count($data) . " records). Transaction rolled back.");
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($coop)) {
        $coop->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

// Close database connection
// PDO closes automatically or set to null
//$coop = null;

ob_end_flush();