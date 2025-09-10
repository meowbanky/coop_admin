<?php
if (ob_get_level()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set all required CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 1728000');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ .'/../../utils/EmailService.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'));
    if (!isset($data->email)) {
        throw new Exception('Email is required');
    }

    $database = new Database();
    $db = $database->getConnection();

    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Calculate expiry time in UTC
    $expiryTime = (new DateTime('now', new DateTimeZone('UTC'))) // Current time in UTC
    ->add(new DateInterval('PT15M')) // Add 15 minutes
    ->format('Y-m-d H:i:s');
    // Store OTP in database
    $sql = "INSERT INTO tbl_password_resets (email, otp, expiry_time) 
            VALUES (:email, :otp, :expiry_time)";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':otp', $otp);
    $stmt->bindParam(':expiry_time', $expiryTime);
    $stmt->execute();

    // Send email
    $emailSender = new EmailService();
    $emailSender->sendOTP($data->email, $otp);

    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}