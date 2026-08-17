<?php
// api/auth/create_account.php
require_once '../../config/Database.php';
require_once '../../utils/EmailService.php';
require_once '../../utils/Validator.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'));
    if (!isset($data->coopId) || !isset($data->email) ||
        !isset($data->otp) || !isset($data->password)) {
        throw new Exception('All fields are required');
    }

    $coopId = trim($data->coopId);
    $email = trim($data->email);
    $otp = trim($data->otp);

    if ($coopId === '') {
        throw new Exception('All fields are required');
    }

    if (!Validator::isValidEmail($email)) {
        throw new Exception('Please enter a valid email address');
    }

    $passwordError = Validator::passwordError($data->password);
    if ($passwordError !== null) {
        throw new Exception($passwordError);
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
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':otp', $otp);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('Invalid or expired OTP');
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // The CoopID must belong to a real member, and that member must not
        // already have a login. Checked inside the transaction so a concurrent
        // signup cannot slip a second account in between check and insert.
        $sql = "SELECT CoopID FROM tblemployees WHERE CoopID = :coopId FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':coopId', $coopId);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception('Member record not found. Please contact the cooperative office.');
        }

        $sql = "SELECT Username FROM tblusers_online WHERE Username = :coopId";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':coopId', $coopId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            throw new Exception('An account already exists for this member. Please log in or reset your password.');
        }

        // Update employee email
        $sql = "UPDATE tblemployees
                SET EmailAddress = :email
                WHERE CoopID = :coopId";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':coopId', $coopId);
        $stmt->execute();

        // Create user account
        $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO tblusers_online
                (Username, UPassword, first_login, roleid, dateofRegistration)
                VALUES (:username, :password, 1, 2, CURDATE())";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':username', $coopId);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->execute();

        // Mark OTP as used
        $sql = "UPDATE tbl_signup_otp
                SET used = 1
                WHERE email = :email AND otp = :otp";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':otp', $otp);
        $stmt->execute();

        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    // The account exists from here on. A failed confirmation email must not be
    // reported as a failed signup, so it is sent outside the transaction.
    try {
        $emailService = new EmailService();
        $emailService->sendAccountCreationNotification($email, $coopId);
    } catch (Exception $e) {
        error_log('Account creation email failed for ' . $coopId . ': ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}