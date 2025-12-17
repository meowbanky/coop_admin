<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../utils/JWTHandler.php';

header('Content-Type: application/json');

try {
    // Validate JWT token
    $headers = getallheaders();
    $jwt = new JWTHandler();
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = $jwt->validateToken($token);

    if (!$decoded) {
        throw new Exception('Invalid token', 401);
    }

    $input = json_decode(file_get_contents('php://input'));

    $database = new Database();
    $db = $database->getConnection();

    // Verify current password
    $query = "SELECT UPassword FROM tblusers_online WHERE Username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $input->coop_id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!password_verify($input->current_password, $row['UPassword'])) {
        throw new Exception('Current password is incorrect');
    }

    // Update password
    $hashedPassword = password_hash($input->new_password, PASSWORD_DEFAULT);
    $updateQuery = "UPDATE tblusers_online SET 
        UPassword = :password,
        PlainPassword = :plain_password
        WHERE Username = :username";

    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':password', $hashedPassword);
    $updateStmt->bindParam(':plain_password', $input->new_password);
    $updateStmt->bindParam(':username', $input->coop_id);

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