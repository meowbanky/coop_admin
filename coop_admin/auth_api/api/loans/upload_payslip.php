<?php
// api/loans/upload_payslip.php
// Handle payslip file upload

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../../config/Database.php';
    require_once '../../utils/JWTHandler.php';

    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $decodedToken = $jwt->validateToken($token);

    if (!$decodedToken) {
        throw new Exception('Invalid token', 401);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    if (!isset($_FILES['payslip']) || $_FILES['payslip']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed', 400);
    }

    $file = $_FILES['payslip'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    // Check file extension first (most reliable)
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        throw new Exception('Invalid file type. Only PDF files are allowed. Please upload a file with .pdf extension', 400);
    }
    
    // Validate MIME type - PDF can have different MIME types depending on system
    $allowedMimeTypes = [
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'applications/vnd.pdf',
        'text/pdf',
        'text/x-pdf'
    ];
    
    // Also check file content signature (magic bytes) for PDF: %PDF
    $fileContent = file_get_contents($file['tmp_name'], false, null, 0, 4);
    $isPdfByContent = ($fileContent === '%PDF');
    
    // Accept if extension is PDF AND (MIME type matches OR content signature matches)
    if (!in_array($file['type'], $allowedMimeTypes) && !$isPdfByContent) {
        // Log for debugging
        error_log("PDF validation failed - MIME type: {$file['type']}, Extension: $extension, Content signature: " . ($isPdfByContent ? 'PDF' : 'Not PDF'));
        throw new Exception('Invalid file type. Only PDF files are allowed. The uploaded file does not appear to be a valid PDF', 400);
    }

    // Validate file size
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 5MB limit', 400);
    }

    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../../uploads/loan_payslips/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'payslip_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save file', 500);
    }

    // Return relative path for storage in database
    $relativePath = 'uploads/loan_payslips/' . $filename;

    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'data' => [
            'file_path' => $relativePath,
            'file_name' => $filename,
            'file_size' => $file['size'],
            'file_type' => $file['type']
        ]
    ]);

} catch (Exception $e) {
    error_log("Payslip upload error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $status_code = $e->getCode();
    if (!is_int($status_code) || $status_code < 100 || $status_code > 599) {
        $status_code = 400;
    }

    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}