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


require_once '../../config/Database.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'));
    if (!isset($data->email) || !isset($data->otp) || !isset($data->new_password)) {
        throw new Exception('All fields are required');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Verify OTP
    $sql = "SELECT * FROM tbl_password_resets 
            WHERE email = :email 
            AND otp = :otp 
            AND expiry_time > UTC_TIMESTAMP() 
            AND used = 0 
            ORDER BY created_at DESC 
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':otp', $data->otp);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('Invalid or expired OTP');
    }

    // Get CoopID from employees table
    $sql = "SELECT CoopID FROM tblemployees 
            WHERE EmailAddress = :email 
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$employee) {
        throw new Exception('Employee not found');
    }

    // Update password in users_online table
    $hashedPassword = password_hash($data->new_password, PASSWORD_DEFAULT);
    $plainPassword = $data->new_password; // Store plain password as well

    $sql = "UPDATE tblusers_online 
            SET UPassword = :hashed_password,
                PlainPassword = :plain_password,
                CPassword = :hashed_password
            WHERE Username = :coop_id";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':hashed_password', $hashedPassword);
    $stmt->bindParam(':plain_password', $plainPassword);
    $stmt->bindParam(':coop_id', $employee['CoopID']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to update password');
    }

    // Mark OTP as used
    $sql = "UPDATE tbl_password_resets 
            SET used = 1 
            WHERE email = :email AND otp = :otp";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':otp', $data->otp);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully'
    ]);

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}