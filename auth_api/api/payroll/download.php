<?php
// api/payroll/download.php

// CORS headers
header('Access-Control-Allow-Origin: http://localhost:51380');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Accept, Content-Type');
header('Access-Control-Max-Age: 3600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Buffer control
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

// Error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    error_log("Starting PDF generation process...");

    // Validate token
    $headers = getallheaders();
    $headers = array_change_key_case($headers, CASE_LOWER);

    if (!isset($headers['authorization'])) {
        throw new Exception('Authorization header missing', 401);
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $headers['authorization'], $matches)) {
        throw new Exception('Invalid authorization format', 401);
    }

    $token = $matches[1];

    // Load dependencies
    $tcpdfPath = dirname(__FILE__) . '/../../lib/tcpdf/';
    if (!file_exists($tcpdfPath . 'tcpdf.php')) {
        throw new Exception("TCPDF library not found at: $tcpdfPath", 500);
    }

    define('TCPDF_PATH', $tcpdfPath);
    require_once '../../config/Database.php';
    require_once '../../utils/JWTHandler.php';
    require_once TCPDF_PATH . 'tcpdf.php';

    // Validate token and get user
    $jwt = new JWTHandler();
    $user_id = $jwt->validateToken($token);
    if (!$user_id) {
        throw new Exception('Invalid token', 401);
    }

    // Get period ID
    $periodId = filter_var($_GET['periodId'] ?? null, FILTER_VALIDATE_INT);
    if (!$periodId) {
        throw new Exception('Invalid period ID', 400);
    }

    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get period details
    $periodQuery = "SELECT periodId, concat(payperiods.description,'-', payperiods.periodYear) as period 
                   FROM payperiods 
                   WHERE periodId = :period_id";
    $periodStmt = $db->prepare($periodQuery);
    $periodStmt->bindParam(':period_id', $periodId);
    $periodStmt->execute();
    $periodData = $periodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$periodData) {
        throw new Exception('Period not found', 404);
    }

    // Get employee data
    $empQuery = "SELECT
        e.*,
        e.staff_id,
        e.NAME,
        e.OGNO,
        e.GRADE,
        e.STEP,
        e.ACCTNO,
        d.dept,
        b.BNAME,
        st.SalaryType
    FROM
        employee e
        LEFT JOIN tbl_dept d ON e.DEPTCD = d.dept_id
        LEFT JOIN tbl_bank b ON e.BANK_ID = b.bank_ID
        LEFT JOIN tbl_salaryType st ON e.SALARY_TYPE = st.salaryType_id
    WHERE
        e.staff_id = :staff_id";

    $empStmt = $db->prepare($empQuery);
    $empStmt->bindParam(':staff_id', $user_id);
    $empStmt->execute();
    $employee = $empStmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Employee not found', 404);
    }

    // Get payroll data
    $payrollQuery = "SELECT m.*, ed.ed as description, ed.edType, ed.type 
                     FROM tbl_master m
                     LEFT JOIN tbl_earning_deduction ed ON m.allow_id = ed.ed_id
                     WHERE m.staff_id = :staff_id 
                     AND m.period = :period
                     ORDER BY FIELD(ed.ed, 'BASIC') DESC, ed.edType, ed.ed_id";

    $payrollStmt = $db->prepare($payrollQuery);
    $payrollStmt->bindParam(':staff_id', $user_id);
    $payrollStmt->bindParam(':period', $periodId);
    $payrollStmt->execute();

    $allowances = [];
    $deductions = [];
    $totalAllowances = 0;
    $totalDeductions = 0;

    while ($row = $payrollStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['type'] == 1) {
            $allowances[] = [
                'description' => $row['description'],
                'amount' => floatval($row['allow'])
            ];
            $totalAllowances += floatval($row['allow']);
        } else {
            $deductions[] = [
                'description' => $row['description'],
                'amount' => floatval($row['deduc'])
            ];
            $totalDeductions += floatval($row['deduc']);
        }
    }

    // Generate PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('SACEST');
    $pdf->SetAuthor('SACEST');
    $pdf->SetTitle('Payslip - ' . $employee['NAME'] . ' - ' . $periodData['period']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->setFooterData(array(0,0,0), array(0,0,0));
    $pdf->setFooterFont(Array('helvetica', '', 8));
    $pdf->SetFooterMargin(5);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Add page
    $pdf->AddPage();

    // Set margins
    $pdf->SetMargins(15, 15, 15);

    // Add background watermark
    $pageWidth = $pdf->getPageWidth();
    $pageHeight = $pdf->getPageHeight();
    $watermarkWidth = 120;
    $watermarkX = ($pageWidth - $watermarkWidth) / 2;
    $watermarkY = ($pageHeight - $watermarkWidth) / 2;

    // Start transparency group
    $pdf->startLayer();
    $pdf->setAlpha(0.1);
    $pdf->Image('../../../assets/images/tasce_r_logo.png', $watermarkX, $watermarkY, $watermarkWidth, $watermarkWidth);
    $pdf->setAlpha(1);
    $pdf->endLayer();

    // Add header logos
    $pdf->Image('../../../assets/images/ogun_logo.png', 15, 15, 25);
    $pdf->Image('../../../assets/images/tasce_r_logo.png', 170, 15, 25);

    // Header text
    $pdf->SetY(15);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Sikiru Adetona College of', 0, 1, 'C');
    $pdf->Cell(0, 8, 'Education, Science and', 0, 1, 'C');
    $pdf->Cell(0, 8, 'Technology, Omu-Ajose', 0, 1, 'C');

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 12, 'PAYSLIP FOR THE MONTH OF ' . $periodData['period'], 0, 1, 'C');

    // Employee Details Section
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Employee Details', 0, 1);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(95, 7, 'Name: ' . $employee['NAME'], 0, 0);
    $pdf->Cell(95, 7, 'Staff No.: ' . $employee['OGNO'], 0, 1);
    $pdf->Cell(95, 7, 'Dept: ' . $employee['dept'], 0, 0);
    $pdf->Cell(95, 7, 'Bank: ' . $employee['BNAME'], 0, 1);
    $pdf->Cell(95, 7, 'Acct No.: ' . $employee['ACCTNO'], 0, 0);
    $pdf->Cell(95, 7, 'Grade/Step: ' . $employee['GRADE'] . '/' . $employee['STEP'], 0, 1);
    $pdf->Cell(190, 7, 'Salary Structure: ' . $employee['SalaryType'], 0, 1);

    // Allowances Section
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Allowances', 0, 1);

    // Table header for allowances
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(120, 7, 'Description', 1, 0, 'L');
    $pdf->Cell(70, 7, 'Amount', 1, 1, 'R');

    $pdf->SetFont('helvetica', '', 10);
    foreach ($allowances as $allowance) {
        $pdf->Cell(120, 6, $allowance['description'], 1, 0, 'L');
        $pdf->Cell(70, 6, number_format($allowance['amount'], 2), 1, 1, 'R');
    }

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(120, 7, 'Gross Salary:', 1, 0, 'L');
    $pdf->Cell(70, 7, number_format($totalAllowances, 2), 1, 1, 'R');

    // Deductions Section
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Deductions', 0, 1);

    // Table header for deductions
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(120, 7, 'Description', 1, 0, 'L');
    $pdf->Cell(70, 7, 'Amount', 1, 1, 'R');

    $pdf->SetFont('helvetica', '', 10);
    foreach ($deductions as $deduction) {
        $pdf->Cell(120, 6, $deduction['description'], 1, 0, 'L');
        $pdf->Cell(70, 6, number_format($deduction['amount'], 2), 1, 1, 'R');
    }

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(120, 7, 'Total Deductions:', 1, 0, 'L');
    $pdf->Cell(70, 7, number_format($totalDeductions, 2), 1, 1, 'R');

    // Net Pay Section
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Net Pay', 0, 1);

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(120, 7, 'NET PAY:', 1, 0, 'L');
    $pdf->Cell(70, 7, number_format($totalAllowances - $totalDeductions, 2), 1, 1, 'R');

    // Footer
    $pdf->SetY(-20);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Powered by emmaggi (www.emmaggi.com)', 0, 0, 'C');

    // Generate PDF output
    $pdfContent = $pdf->Output('', 'S');
    $fileSize = strlen($pdfContent);

    // Clear any previous output
    if (ob_get_length()) ob_clean();

    // Send headers
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . $employee['NAME'] . '_' . $periodData['period'] . '.pdf"');
    header('Cache-Control: private, must-revalidate');
    header('Pragma: public');

    // Output PDF
    echo $pdfContent;
    exit();

} catch (Exception $e) {
    error_log("Error in PDF generation: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    if (ob_get_length()) ob_clean();

    header('Content-Type: application/json');
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}