<?php
ini_set('max_execution_time', '300');
session_start();

require_once('Connections/coop.php');
include_once('classes/model.php');

// Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get parameters
    $period = isset($_POST['period']) ? (int)$_POST['period'] : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $recordsPerPage = isset($_POST['records_per_page']) ? (int)$_POST['records_per_page'] : 50;
    
    // Validate parameters
    if ($page < 1) $page = 1;
    if ($recordsPerPage < 1) $recordsPerPage = 50;
    
    $offset = ($page - 1) * $recordsPerPage;
    
    // Debug logging
    error_log("Member Contributions API - Period: $period, Status: $status, Page: $page, RecordsPerPage: $recordsPerPage");
    
    // Build the base query - use subqueries to get period-specific contributions
    $baseQuery = "
        SELECT 
            tblemployees.CoopID,
            tblemployees.StaffID,
            tblemployees.CourtesyTitle,
            tblemployees.FirstName,
            tblemployees.MiddleName,
            tblemployees.LastName,
            tblemployees.Gender,
            tblemployees.Department,
            tblemployees.Status,
            COALESCE(monthly_contrib.monthly_contribution, 0) as monthly_contribution,
            COALESCE(savings_contrib.savings_amount, 0) as savings_amount,
            (COALESCE(monthly_contrib.monthly_contribution, 0) + COALESCE(savings_contrib.savings_amount, 0)) as total_contribution
        FROM tblemployees
        LEFT JOIN (
            SELECT coopID, MonthlyContribution as monthly_contribution 
            FROM tbl_monthlycontribution";
    
    // Add period filter for monthly contribution if specified
    if (!empty($period)) {
        $baseQuery .= " WHERE period = :period1";
    }
    
    $baseQuery .= "
        ) monthly_contrib ON tblemployees.CoopID = monthly_contrib.coopID
        LEFT JOIN (
            SELECT COOPID, Amount as savings_amount 
            FROM tbl_loansavings";
    
    // Add period filter for loansavings if specified
    if (!empty($period)) {
        $baseQuery .= " WHERE period = :period2";
    }
    
    $baseQuery .= "
        ) savings_contrib ON tblemployees.CoopID = savings_contrib.COOPID
        WHERE 1=1";
    
    // Add status filter if specified
    if (!empty($status)) {
        $baseQuery .= " AND tblemployees.Status = :status";
    } else {
        // Default to Active if no status specified
        $baseQuery .= " AND tblemployees.Status = 'Active'";
    }
    
    // Add HAVING clause to filter out zero contributions
    $baseQuery .= " HAVING total_contribution > 0";
    
    // Count query
    $countQuery = "SELECT COUNT(*) as total FROM (" . $baseQuery . ") as count_table";
    
    // Prepare count statement
    $countStmt = $conn->prepare($countQuery);
    
    // Bind parameters for count
    if (!empty($period)) {
        $countStmt->bindParam(':period1', $period, PDO::PARAM_INT);
        $countStmt->bindParam(':period2', $period, PDO::PARAM_INT);
    }
    if (!empty($status)) {
        $countStmt->bindParam(':status', $status, PDO::PARAM_STR);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    // Add ordering and pagination to main query
    $mainQuery = $baseQuery . " ORDER BY tblemployees.CoopID ASC LIMIT :offset, :limit";
    
    // Prepare main statement
    $stmt = $conn->prepare($mainQuery);
    
    // Bind parameters for main query
    if (!empty($period)) {
        $stmt->bindParam(':period1', $period, PDO::PARAM_INT);
        $stmt->bindParam(':period2', $period, PDO::PARAM_INT);
    }
    if (!empty($status)) {
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    }
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
    
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total contributions for all rows (not just current page)
    $totalContributionsQuery = "
        SELECT SUM(
            COALESCE(monthly_contrib.monthly_contribution, 0) + 
            COALESCE(savings_contrib.savings_amount, 0)
        ) as total_contributions
        FROM tblemployees
        LEFT JOIN (
            SELECT coopID, MonthlyContribution as monthly_contribution 
            FROM tbl_monthlycontribution";
    
    if (!empty($period)) {
        $totalContributionsQuery .= " WHERE period = :period1";
    }
    
    $totalContributionsQuery .= "
        ) monthly_contrib ON tblemployees.CoopID = monthly_contrib.coopID
        LEFT JOIN (
            SELECT COOPID, Amount as savings_amount 
            FROM tbl_loansavings";
    
    if (!empty($period)) {
        $totalContributionsQuery .= " WHERE period = :period2";
    }
    
    $totalContributionsQuery .= "
        ) savings_contrib ON tblemployees.CoopID = savings_contrib.COOPID
        WHERE 1=1";
    
    // Add status filter if specified
    if (!empty($status)) {
        $totalContributionsQuery .= " AND tblemployees.Status = :status";
    } else {
        // Default to Active if no status specified
        $totalContributionsQuery .= " AND tblemployees.Status = 'Active'";
    }
    
    // Debug: Log the generated SQL query
    error_log("Total Contributions Query: " . $totalContributionsQuery);
    
    $totalContribStmt = $conn->prepare($totalContributionsQuery);
    
    // Bind parameters for total contributions query
    if (!empty($period)) {
        $totalContribStmt->bindParam(':period1', $period, PDO::PARAM_INT);
        $totalContribStmt->bindParam(':period2', $period, PDO::PARAM_INT);
    }
    if (!empty($status)) {
        $totalContribStmt->bindParam(':status', $status, PDO::PARAM_STR);
    }
    
    $totalContribStmt->execute();
    $totalContributions = $totalContribStmt->fetchColumn() ?: 0;
    
    // Debug logging
    error_log("Query executed successfully. Total records: $totalRecords, Members found: " . count($members) . ", Total contributions: $totalContributions");
    
    // Format the response
    $response = [
        'success' => true,
        'members' => $members,
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'records_per_page' => $recordsPerPage,
        'total_contributions' => $totalContributions,
        'has_previous' => $page > 1,
        'has_next' => $page < $totalPages,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'records_per_page' => $recordsPerPage,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error in getMemberContributions.php: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'message' => 'An error occurred while fetching member contributions',
        'error' => $e->getMessage()
    ];
    
    echo json_encode($response);
}
?>