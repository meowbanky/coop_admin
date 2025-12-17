<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
try {
    $db = $database->getConnection();
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['staff_id']) || !isset($data['password'])) {
        throw new Exception('Required fields are missing');
    }

    // Begin transaction
    $db->beginTransaction();

    // Update employee table with alternate email
    $updateEmployee = "UPDATE employee 
                      SET alternate_email = ?, 
                          MOBILE_NO = ?,EMAIL = ?
                      WHERE staff_id = ?";

    $stmt = $db->prepare($updateEmployee);
    $stmt->execute([
        $data['alternate_email'],
        $data['mobile_no'],
        $data['email'],
        $data['staff_id']
    ]);

    // Check if user already exists
    $checkUser = "SELECT id FROM tbl_users WHERE staff_id = ?";
    $stmt = $db->prepare($checkUser);
    $stmt->execute([$data['staff_id']]);

    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

    if ($stmt->rowCount() > 0) {
        // User exists - update password only
        $updateUser = "UPDATE tbl_users 
                      SET password_hash = ?, 
                          plain_password = ?,
                          last_login = NULL
                      WHERE staff_id = ?";

        $stmt = $db->prepare($updateUser);
        $stmt->execute([
            $passwordHash,
            $data['password'],
            $data['staff_id']
        ]);
    } else {
        // New user - insert full details
        $createUser = "INSERT INTO tbl_users 
                      (staff_id, password_hash, plain_password) 
                      VALUES (?, ?, ?)";

        $stmt = $db->prepare($createUser);
        $stmt->execute([
            $data['staff_id'],
            $passwordHash,
            $data['password']
        ]);
    }

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Registration completed successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>