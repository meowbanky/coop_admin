<?php
// api/auth/send_signup_otp.php
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


// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../utils/EmailService.php';
require_once __DIR__ . '/../../utils/Validator.php';
require_once __DIR__ . '/../../utils/OtpThrottle.php';
header('Content-Type: application/json');

const OTP_VALIDITY_MINUTES = 15;

try {
    $data = json_decode(file_get_contents('php://input'));
    if (!isset($data->email)) {
        throw new Exception('Email is required');
    }

    $email = trim($data->email);

    if (!Validator::isValidEmail($email)) {
        throw new Exception('Please enter a valid email address');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check if email already exists
    $sql = "SELECT EmailAddress FROM tblemployees
            WHERE EmailAddress = :email AND EmailAddress IN
            (SELECT Username FROM tblusers_online)";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        throw new Exception('Email already registered');
    }

    $throttle = new OtpThrottle($db, 'tbl_signup_otp');
    $throttle->assertCanSend($email);

    // Generate OTP from a cryptographically secure source
    $otp = OtpThrottle::generateCode();
    $expiryTime = (new DateTime('now', new DateTimeZone('UTC'))) // Current time in UTC
    ->add(new DateInterval('PT' . OTP_VALIDITY_MINUTES . 'M'))
    ->format('Y-m-d H:i:s');

    // Store and send together: if the mail fails, roll back so we do not leave
    // an OTP the member never received (which would still count against them).
    $db->beginTransaction();

    try {
        $sql = "INSERT INTO tbl_signup_otp (email, otp, expiry_time)
                VALUES (:email, :otp, :expiry_time)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':otp', $otp);
        $stmt->bindParam(':expiry_time', $expiryTime);
        $stmt->execute();

        $emailService = new EmailService();
        $emailService->sendOTP($email, $otp);

        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Signup OTP delivery failed for ' . $email . ': ' . $e->getMessage());
        throw new Exception('Could not send the verification code. Please try again.');
    }

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