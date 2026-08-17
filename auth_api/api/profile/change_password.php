<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../utils/JWTHandler.php';
require_once __DIR__ . '/../../utils/Validator.php';

header('Content-Type: application/json');

try {
    // Validate JWT token
    $headers = getallheaders();
    $jwt = new JWTHandler();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    $decoded = $jwt->validateToken($token);

    if (!$decoded || empty($decoded['user_id'])) {
        throw new Exception('Invalid token', 401);
    }

    // Identity comes from the token, never from the request body, so a caller
    // cannot target another member's account.
    $username = $decoded['user_id'];

    $input = json_decode(file_get_contents('php://input'));

    if (!isset($input->current_password) || !isset($input->new_password)) {
        throw new Exception('Current and new password are required');
    }

    $passwordError = Validator::passwordError($input->new_password);
    if ($passwordError !== null) {
        throw new Exception($passwordError);
    }

    $database = new Database();
    $db = $database->getConnection();

    // Verify current password
    $query = "SELECT UPassword FROM tblusers_online WHERE Username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($input->current_password, $row['UPassword'])) {
        throw new Exception('Current password is incorrect');
    }

    // Update password
    $hashedPassword = password_hash($input->new_password, PASSWORD_DEFAULT);
    $updateQuery = "UPDATE tblusers_online SET
        UPassword = :password,
        CPassword = :password
        WHERE Username = :username";

    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':password', $hashedPassword);
    $updateStmt->bindParam(':username', $username);

    if($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        throw new Exception('Failed to change password');
    }
} catch(Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}