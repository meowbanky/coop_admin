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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    $input = file_get_contents('php://input');
    error_log("Received input: " . $input);
    $data = json_decode($input);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    if (!isset($data->username) || !isset($data->password)) {
        throw new Exception('Username and password are required');
    }

    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../models/User.php';
    require_once __DIR__ . '/../../utils/JWTHandler.php';

    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $result = $user->login($data->username, $data->password);

    if ($result['success']) {
        $jwt = new JWTHandler();
        $token = $jwt->generateToken($result['user']['CoopID']);


    $query = "SELECT 
    e.CoopID,
    e.Picture as profile_image,
    e.FirstName,
    e.LastName,
    e.EmailAddress,
    e.MobileNumber,
    e.StreetAddress,
    e.Town,
    e.State,
    COALESCE(SUM(t.sharesAmount), 0) as shares_balance,
    COALESCE(SUM(t.savingsAmount), 0) as savings_balance,
    COALESCE(SUM(t.loan), 0) - COALESCE(SUM(t.loanRepayment), 0) as unpaid_loan,
    (COALESCE(SUM(t.sharesAmount), 0) + COALESCE(SUM(t.savingsAmount), 0)) as total_balance
FROM tblemployees e
LEFT JOIN tbl_mastertransact t ON e.CoopID = t.COOPID
WHERE e.CoopID = :coop_id
GROUP BY e.CoopID, e.Picture, e.FirstName, e.LastName, e.EmailAddress, e.MobileNumber, e.StreetAddress, e.Town, e.State";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':coop_id', $result['user']['CoopID']);
        $stmt->execute();

        $walletInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $response = [
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => $result['user'],
            'wallet' => [
                'total_balance' => floatval($walletInfo['total_balance'] ?? 0),
                'shares_balance' => floatval($walletInfo['shares_balance'] ?? 0),
                'savings_balance' => floatval($walletInfo['savings_balance'] ?? 0),
                'unpaid_loan' => floatval($walletInfo['unpaid_loan'] ?? 0)
            ]
        ];
        http_response_code(200);
    } else {
        $response = [
            'success' => false,
            'message' => 'Invalid credentials'
        ];
        http_response_code(401);
    }

    echo json_encode($response);
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}