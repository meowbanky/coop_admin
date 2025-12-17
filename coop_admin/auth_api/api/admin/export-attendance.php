<?php
// api/admin/export-attendance.php
// Export event attendance to Excel

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../../config/Database.php';
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    
    $eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    if (!$eventId) {
        throw new Exception('Event ID is required', 400);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get event details
    $eventQuery = "SELECT title, start_time, end_time FROM events WHERE id = :id";
    $eventStmt = $db->prepare($eventQuery);
    $eventStmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $eventStmt->execute();
    
    if ($eventStmt->rowCount() === 0) {
        throw new Exception('Event not found', 404);
    }
    
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get attendance records
    $attendanceQuery = "SELECT 
        ea.id,
        ea.user_coop_id,
        CONCAT(e.FirstName, ' ', e.LastName) as user_name,
        ea.check_in_time,
        ea.distance_from_event,
        ea.status
    FROM event_attendance ea
    LEFT JOIN tblemployees e ON e.CoopID = ea.user_coop_id
    WHERE ea.event_id = :event_id
    ORDER BY ea.check_in_time DESC";
    
    $attendanceStmt = $db->prepare($attendanceQuery);
    $attendanceStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $attendanceStmt->execute();
    
    $attendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('OOUTH Cooperative')
        ->setTitle('Event Attendance - ' . $event['title'])
        ->setSubject('Event Attendance Export')
        ->setDescription('Attendance list for event: ' . $event['title']);
    
    // Set sheet title
    $sheet->setTitle('Attendance');
    
    // Header row
    $headers = [
        'S/N',
        'Coop ID',
        'Name',
        'Check-in Time',
        'Distance (meters)',
        'Status'
    ];
    
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // Style header row
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4285F4']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ]
    ];
    
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
    $sheet->getRowDimension('1')->setRowHeight(25);
    
    // Event info row
    $sheet->setCellValue('A2', 'Event:');
    $sheet->setCellValue('B2', $event['title']);
    $sheet->mergeCells('B2:F2');
    
    $sheet->setCellValue('A3', 'Date:');
    $startDate = date('F j, Y g:i A', strtotime($event['start_time']));
    $endDate = date('g:i A', strtotime($event['end_time']));
    $sheet->setCellValue('B3', $startDate . ' - ' . $endDate);
    $sheet->mergeCells('B3:F3');
    
    $sheet->setCellValue('A4', 'Total Attendance:');
    $sheet->setCellValue('B4', count($attendance));
    $sheet->mergeCells('B4:F4');
    
    // Data rows start at row 6
    $row = 6;
    $sn = 1;
    
    foreach ($attendance as $record) {
        $sheet->setCellValue('A' . $row, $sn);
        $sheet->setCellValue('B' . $row, $record['user_coop_id']);
        $sheet->setCellValue('C' . $row, $record['user_name'] ?: 'Unknown');
        $sheet->setCellValue('D' . $row, date('Y-m-d H:i:s', strtotime($record['check_in_time'])));
        $sheet->setCellValue('E' . $row, number_format($record['distance_from_event'], 2));
        $sheet->setCellValue('F' . $row, ucfirst($record['status']));
        
        // Style data rows
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        
        // Alternate row colors
        if ($row % 2 == 0) {
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F5F5F5']
                ]
            ]);
        }
        
        $row++;
        $sn++;
    }
    
    // Auto-size columns
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(12);
    
    // Center align S/N and Distance columns
    $sheet->getStyle('A6:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E6:E' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F6:F' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Generate filename
    $filename = 'Event_Attendance_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $event['title']) . '_' . date('Y-m-d_His') . '.xlsx';
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Write file to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
    
} catch (Exception $e) {
    error_log("Export attendance error: " . $e->getMessage());
    $code = $e->getCode();
    // Ensure code is an integer (PDO exceptions may return string codes)
    $httpCode = is_numeric($code) ? intval($code) : 500;
    // Ensure valid HTTP status code range
    if ($httpCode < 100 || $httpCode > 599) {
        $httpCode = 500;
    }
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}