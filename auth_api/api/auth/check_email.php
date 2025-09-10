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

function isValidEmail($email) {
    // Remove leading/trailing whitespace
    $email = trim($email);

    // Basic format validation using PHP's filter_var
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Check for minimum length (user@x.xx)
    if (strlen($email) < 6) {
        return false;
    }

    // Check for consecutive dots
    if (strpos($email, '..') !== false) {
        return false;
    }

    // Check for valid domain extension (at least 2 characters after last dot)
    $parts = explode('.', $email);
    if (strlen(end($parts)) < 2) {
        return false;
    }

    return true;
}

try {
    $coopId = $_GET['coopId'] ?? '';
    if (empty($coopId)) {
        throw new Exception('CoopID is required');
    }

    require_once __DIR__ . '/../../config/Database.php';
    $database = new Database();
    $db = $database->getConnection();

    // Get all email addresses for the given CoopID
    $sql = "SELECT EmailAddress FROM tblemployees WHERE CoopID = :coopId AND EmailAddress <> ''";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':coopId', $coopId);
    $stmt->execute();

    // Count only valid email addresses
    $validEmailCount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isValidEmail($row['EmailAddress'])) {
            $validEmailCount++;
        }
    }

    $hasRegisteredAccount = $validEmailCount > 0;

    echo json_encode([
        'success' => true,
        'hasEmail' => $hasRegisteredAccount,
        'message' => $hasRegisteredAccount ? 'User already registered' : 'User not registered'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}