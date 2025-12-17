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

try {
    // Get parameters
    $period = isset($_POST['period']) ? (int)$_POST['period'] : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    
    // Build the query (same as getMemberContributions.php but without pagination)
    $query = "
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
        $query .= " WHERE period = :period1";
    }
    
    $query .= "
        ) monthly_contrib ON tblemployees.CoopID = monthly_contrib.coopID
        LEFT JOIN (
            SELECT COOPID, Amount as savings_amount 
            FROM tbl_loansavings";
    
    // Add period filter for loansavings if specified
    if (!empty($period)) {
        $query .= " WHERE period = :period2";
    }
    
    $query .= "
        ) savings_contrib ON tblemployees.CoopID = savings_contrib.COOPID
        WHERE 1=1";
    
    // Add status filter if specified
    if (!empty($status)) {
        $query .= " AND tblemployees.Status = :status";
    } else {
        // Default to Active if no status specified
        $query .= " AND tblemployees.Status = 'Active'";
    }
    
    // Add HAVING clause to filter out zero contributions
    $query .= " HAVING total_contribution > 0 ORDER BY tblemployees.CoopID ASC";
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($period)) {
        $stmt->bindParam(':period1', $period, PDO::PARAM_INT);
        $stmt->bindParam(':period2', $period, PDO::PARAM_INT);
    }
    if (!empty($status)) {
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get period name for filename
    $periodName = 'All_Periods';
    if (!empty($period)) {
        $periodStmt = $conn->prepare("SELECT PayrollPeriod FROM tbpayrollperiods WHERE id = ?");
        $periodStmt->execute([$period]);
        $periodData = $periodStmt->fetch(PDO::FETCH_ASSOC);
        if ($periodData) {
            $periodName = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $periodData['PayrollPeriod']);
        }
    }
    
    // Set headers for Excel download
    $filename = "Member_Contributions_{$periodName}_" . date('Y-m-d') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content using HTML table (simpler approach)
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
    
    // Define Styles
    echo '<Styles>';
    echo '<Style ss:ID="Title">';
    echo '<Font ss:Size="16" ss:Bold="1" ss:Color="#000000"/>';
    echo '<Alignment ss:Horizontal="Center"/>';
    echo '</Style>';
    echo '<Style ss:ID="Subtitle">';
    echo '<Font ss:Size="12" ss:Bold="1" ss:Color="#666666"/>';
    echo '<Alignment ss:Horizontal="Center"/>';
    echo '</Style>';
    echo '<Style ss:ID="Header">';
    echo '<Font ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/>';
    echo '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>';
    echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
    echo '<Borders>';
    echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '</Borders>';
    echo '</Style>';
    echo '<Style ss:ID="Data">';
    echo '<Font ss:Size="9"/>';
    echo '<Alignment ss:Vertical="Center"/>';
    echo '<Borders>';
    echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '</Borders>';
    echo '</Style>';
    echo '<Style ss:ID="Total">';
    echo '<Font ss:Size="9" ss:Bold="1"/>';
    echo '<Alignment ss:Vertical="Center"/>';
    echo '<Borders>';
    echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>';
    echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
    echo '</Borders>';
    echo '</Style>';
    echo '</Styles>';
    
    echo '<Worksheet ss:Name="Member Contributions">';
    echo '<Table>';
    
    // Report Title
    echo '<Row>';
    echo '<Cell ss:StyleID="Title"><Data ss:Type="String">MEMBER CONTRIBUTIONS REPORT</Data></Cell>';
    echo '</Row>';
    echo '<Row>';
    echo '<Cell ss:StyleID="Subtitle"><Data ss:Type="String">Period: ' . ($periodName !== 'All_Periods' ? $periodName : 'All Periods') . '</Data></Cell>';
    echo '</Row>';
    echo '<Row>';
    echo '<Cell ss:StyleID="Subtitle"><Data ss:Type="String">Generated: ' . date('Y-m-d H:i:s') . '</Data></Cell>';
    echo '</Row>';
    echo '<Row></Row>'; // Empty row for spacing
    
    // Headers
    echo '<Row ss:StyleID="Header">';
    echo '<Cell><Data ss:Type="String">S/N</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Coop ID</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Full Name</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Department</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Monthly Contribution</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Savings Amount</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Total Contribution</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Signature</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Date</Data></Cell>';
    echo '</Row>';
    
    // Data rows
    $rowNumber = 1;
    $totalMonthlyContribution = 0;
    $totalSavingsAmount = 0;
    $grandTotal = 0;
    
    foreach ($members as $member) {
        $fullName = trim($member['LastName'] . ', ' . $member['FirstName'] . ' ' . ($member['MiddleName'] ?? ''));
        $monthlyContribution = (float)$member['monthly_contribution'];
        $savingsAmount = (float)$member['savings_amount'];
        $totalContribution = (float)$member['total_contribution'];
        
        $totalMonthlyContribution += $monthlyContribution;
        $totalSavingsAmount += $savingsAmount;
        $grandTotal += $totalContribution;
        
        echo '<Row ss:StyleID="Data">';
        echo '<Cell><Data ss:Type="Number">' . $rowNumber . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($member['CoopID']) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($fullName) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($member['Department'] ?? 'N/A') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . number_format($monthlyContribution, 2) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . number_format($savingsAmount, 2) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . number_format($totalContribution, 2) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String"></Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . date('Y-m-d') . '</Data></Cell>';
        echo '</Row>';
        
        $rowNumber++;
    }
    
    // Totals row
    echo '<Row ss:StyleID="Total">';
    echo '<Cell><Data ss:Type="String">TOTALS</Data></Cell>';
    echo '<Cell><Data ss:Type="String"></Data></Cell>';
    echo '<Cell><Data ss:Type="String"></Data></Cell>';
    echo '<Cell><Data ss:Type="String"></Data></Cell>';
    echo '<Cell><Data ss:Type="Number">' . number_format($totalMonthlyContribution, 2) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="Number">' . number_format($totalSavingsAmount, 2) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="Number">' . number_format($grandTotal, 2) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String"></Data></Cell>';
    echo '<Cell><Data ss:Type="String"></Data></Cell>';
    echo '</Row>';
    
    echo '</Table>';
    echo '</Worksheet>';
    echo '</Workbook>';
    
} catch (Exception $e) {
    error_log("Error in exportMemberContributions.php: " . $e->getMessage());
    
    // If there's an error, send a simple error response
    header('Content-Type: text/plain');
    echo "Error exporting data: " . $e->getMessage();
}
?>
