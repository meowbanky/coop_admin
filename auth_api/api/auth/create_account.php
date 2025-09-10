<?php
// api/auth/create_account.php
require_once '../../config/Database.php';
require_once '../../utils/EmailService.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'));
    if (!isset($data->coopId) || !isset($data->email) ||
        !isset($data->otp) || !isset($data->password)) {
        throw new Exception('All fields are required');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Verify OTP
    $sql = "SELECT * FROM tbl_signup_otp 
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

    // Start transaction
    $db->beginTransaction();

    try {
        // Update employee email
        $sql = "UPDATE tblemployees 
                SET EmailAddress = :email 
                WHERE CoopID = :coopId";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':coopId', $data->coopId);
        $stmt->execute();

        error_log($data->email);
        error_log($data->coopId);
        
        // Create user account
        $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO tblusers_online 
                (Username, UPassword, PlainPassword, first_login, roleid, dateofRegistration) 
                VALUES (:username, :password, :plain_password, 1, 2, CURDATE())";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':username', $data->coopId);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':plain_password', $data->password);
        $stmt->execute();

        // Mark OTP as used
        $sql = "UPDATE tbl_signup_otp 
                SET used = 1 
                WHERE email = :email AND otp = :otp";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':otp', $data->otp);
        $stmt->execute();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}