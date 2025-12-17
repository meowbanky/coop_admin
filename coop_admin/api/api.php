<?php
/**
 * OOUTH COOP API
 * External API for member information and balance queries
 * 
 * Actions:
 * - check_user: Verify member by phone number
 * - get_balances: Get member account balances
 */

header('Content-Type: application/json');

// Load environment configuration
require_once __DIR__ . '/../config/EnvConfig.php';

// ==========================================
// 1. CONFIGURATION
// ==========================================

// Get API Secret from environment configuration
$API_SECRET = EnvConfig::getAPISecret();

// Validate API secret is configured
if (empty($API_SECRET)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "API_SECRET not configured in .env"]);
    exit;
}

// DATABASE CREDENTIALS from environment configuration
$dbConfig = EnvConfig::getDatabaseConfig();
$host = $dbConfig['host'];
$db   = $dbConfig['name'];
$user = $dbConfig['user'];
$pass = $dbConfig['password'];
$charset = 'utf8mb4';

// ==========================================
// 2. AUTHENTICATION & CONNECTION
// ==========================================

// Check for API Key
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Allow passing key via GET parameter for easy testing (optional)
if(!$authHeader && isset($_GET['apikey'])) {
    $authHeader = "Bearer " . $_GET['apikey'];
}

if (strpos($authHeader, $API_SECRET) === false) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Connect to Database
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Connection Failed"]);
    exit;
}

// ==========================================
// 3. ROUTING LOGIC
// ==========================================

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_user':
        checkUser($pdo);
        break;

    case 'get_balances':
        getBalances($pdo);
        break;
    
    case 'get_monthly_stats':
        getMonthlyStats($pdo);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        break;
}

// ==========================================
// 4. FUNCTIONS
// ==========================================

/**
 * Check if a user exists by phone number
 * 
 * @param PDO $pdo Database connection
 */
function checkUser($pdo) {
    // Input: Phone number
    $phone = $_GET['phone'] ?? '';
    
    if (empty($phone)) {
        echo json_encode([
            "status" => "error", 
            "message" => "Phone number is required"
        ]);
        return;
    }
    
    // Basic sanitization - remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone); 
    
    if (empty($phone)) {
        echo json_encode([
            "status" => "error", 
            "message" => "Invalid phone number format"
        ]);
        return;
    }

    // Query tblemployees table
    // Note: n8n usually sends WhatsApp numbers with country code (e.g. 23480...)
    // We try to match the last 10 digits to handle 080 vs 23480 issues
    $stmt = $pdo->prepare("
        SELECT 
            CoopID, 
            FirstName, 
            MiddleName,
            LastName, 
            MobileNumber,
            EmailAddress,
            Status
        FROM tblemployees 
        WHERE MobileNumber LIKE ? AND Status = 'Active' 
        LIMIT 1
    ");
    
    // Match last 10 digits (handles 080 vs 23480 issues)
    $searchPhone = "%" . substr($phone, -10); 
    
    $stmt->execute([$searchPhone]);
    $user = $stmt->fetch();

    if ($user) {
        // Build full name
        $fullName = trim($user['FirstName'] . ' ' . ($user['MiddleName'] ?? '') . ' ' . $user['LastName']);
        $fullName = preg_replace('/\s+/', ' ', $fullName); // Clean up extra spaces
        
        echo json_encode([
            "status" => "success",
            "member_id" => $user['CoopID'],
            "name" => $fullName,
            "first_name" => $user['FirstName'],
            "last_name" => $user['LastName'],
            "phone_matched" => $user['MobileNumber'],
            "email" => $user['EmailAddress'] ?? null
            // "status" => $user['Status'] ?? 'Active'
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Member not found"
        ]);
    }
}

/**
 * Get member account balances
 * 
 * @param PDO $pdo Database connection
 */
function getBalances($pdo) {
    $coopId = $_GET['member_id'] ?? $_GET['coop_id'] ?? '';
    
    if (empty($coopId)) {
        echo json_encode([
            "status" => "error", 
            "message" => "Member ID (CoopID) is required"
        ]);
        return;
    }

    // Query tbl_mastertransact for transaction totals
    // Note: In this system, savingsAmount and sharesAmount are deposits (positive values)
    // Withdrawals might be stored as negative values or in separate tables
    $sql = "SELECT 
                COALESCE(SUM(savingsAmount), 0) as total_savings,
                COALESCE(SUM(sharesAmount), 0) as total_shares,
                COALESCE(SUM(loan), 0) as total_loan_taken,
                COALESCE(SUM(loanRepayment), 0) as total_loan_repaid,
                COALESCE(SUM(InterestPaid), 0) as total_interest_paid,
                COALESCE(SUM(DevLevy), 0) as total_dev_levy,
                COALESCE(SUM(EntryFee), 0) as total_entry_fee,
                COALESCE(SUM(Stationery), 0) as total_stationery,
                COALESCE(SUM(Commodity), 0) as total_commodity,
                COALESCE(SUM(CommodityRepayment), 0) as total_commodity_repaid
            FROM tbl_mastertransact 
            WHERE COOPID = ? 
            AND completed = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$coopId]);
    $result = $stmt->fetch();

    if (!$result) {
        echo json_encode([
            "status" => "error",
            "message" => "No transaction records found for this member"
        ]);
        return;
    }

    // Calculate balances
    // Savings balance (deposits only - no withdrawal column in schema)
    $savings_bal = round(floatval($result['total_savings'] ?? 0), 2);
    
    // Shares balance (deposits only - no withdrawal column in schema)
    $shares_bal = round(floatval($result['total_shares'] ?? 0), 2);
    
    // Loan Principal Balance (loan taken - loan repaid)
    $loan_bal = round(floatval($result['total_loan_taken'] ?? 0) - floatval($result['total_loan_repaid'] ?? 0), 2);
    
    // Interest Balance
    // Note: InterestPaid is interest paid by member, not interest charged
    // If you need outstanding interest, you may need to query tbl_loans or tbl_interest tables
    $interest_paid = round(floatval($result['total_interest_paid'] ?? 0), 2);
    
    // Other balances
    $dev_levy = round(floatval($result['total_dev_levy'] ?? 0), 2);
    $entry_fee = round(floatval($result['total_entry_fee'] ?? 0), 2);
    $stationery = round(floatval($result['total_stationery'] ?? 0), 2);
    
    // Commodity balance (commodity taken - commodity repaid)
    $commodity_bal = round(floatval($result['total_commodity'] ?? 0) - floatval($result['total_commodity_repaid'] ?? 0), 2);
    
    // Raw totals (for reference)
    $total_loan_taken = round(floatval($result['total_loan_taken'] ?? 0), 2);
    $total_loan_repaid = round(floatval($result['total_loan_repaid'] ?? 0), 2);
    $total_commodity = round(floatval($result['total_commodity'] ?? 0), 2);
    $total_commodity_repaid = round(floatval($result['total_commodity_repaid'] ?? 0), 2);

    // Safety checks - ensure balances are not negative (unless withdrawals are negative)
    if($loan_bal < 0) $loan_bal = 0;
    if($commodity_bal < 0) $commodity_bal = 0;
    if($savings_bal < 0) $savings_bal = 0;
    if($shares_bal < 0) $shares_bal = 0;

    // Get member info for additional context
    $memberStmt = $pdo->prepare("
        SELECT 
            CoopID,
            FirstName,
            LastName,
            Status
        FROM tblemployees 
        WHERE CoopID = ?
        LIMIT 1
    ");
    $memberStmt->execute([$coopId]);
    $member = $memberStmt->fetch();

    echo json_encode([
        "status" => "success",
        "member_id" => $coopId,
        "member_name" => $member ? trim($member['FirstName'] . ' ' . $member['LastName']) : null,
        "member_status" => $member['Status'] ?? null,
        "data" => [
            "savings_balance" => number_format($savings_bal, 2, '.', ''),
            "shares_balance" => number_format($shares_bal, 2, '.', ''),
            "loan_balance" => number_format($loan_bal, 2, '.', ''),
            "interest_paid" => number_format($interest_paid, 2, '.', ''),
            "commodity_balance" => number_format($commodity_bal, 2, '.', ''),
            "dev_levy_total" => number_format($dev_levy, 2, '.', ''),
            "entry_fee_total" => number_format($entry_fee, 2, '.', ''),
            "stationery_total" => number_format($stationery, 2, '.', ''),
            "currency" => "NGN"
        ],
        "raw_totals" => [
            "total_savings" => number_format($savings_bal, 2, '.', ''),
            "total_shares" => number_format($shares_bal, 2, '.', ''),
            "total_loan_taken" => number_format($total_loan_taken, 2, '.', ''),
            "total_loan_repaid" => number_format($total_loan_repaid, 2, '.', ''),
            "total_interest_paid" => number_format($interest_paid, 2, '.', ''),
            "total_commodity" => number_format($total_commodity, 2, '.', ''),
            "total_commodity_repaid" => number_format($total_commodity_repaid, 2, '.', '')
        ]
    ]);
}

/**
 * Get monthly statistics for a member
 * 
 * @param PDO $pdo Database connection
 * @return void Outputs JSON response
 */

function getMonthlyStats($pdo) {
    $memberId = $_GET['member_id'] ?? '';
    $month = $_GET['month'] ?? ''; // Expecting "April", "January", etc.
    $year = $_GET['year'] ?? '';   // Expecting "2024", "2023"

    if (!$memberId || !$month || !$year) {
        echo json_encode(["status" => "error", "message" => "Missing parameters"]);
        return;
    }

    // 1. We need to match the Month/Year string to your database format.
    // Assuming 'PhysicalMonth' in DB is stored like "January", "February" etc.
    // If your DB uses numbers (1, 2), we might need to convert. 
    // Based on your schema (varchar 20), I assume it stores text.

    $sql = "SELECT 
                COALESCE(SUM(t.savingsAmount), 0) as total_savings,
                COALESCE(SUM(t.sharesAmount), 0) as total_shares,
                COALESCE(SUM(t.loanRepayment), 0) as total_loan_repayment,
                COALESCE(SUM(t.InterestPaid), 0) as total_interest_paid,
                COALESCE(SUM(t.CommodityRepayment), 0) as total_commodity_repayment,
                COALESCE(SUM(t.loan), 0) as total_loan_taken,
                MAX(p.PayrollPeriod) as PayrollPeriod
								FROM tbl_mastertransact t
            JOIN tbpayrollperiods p ON t.TransactionPeriod = p.id
            WHERE t.COOPID = ?
            AND p.PhysicalMonth = ? 
            AND p.PhysicalYear = ?
            AND t.completed = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$memberId, $month, $year]);
    $result = $stmt->fetch();

    if ($result && ($result['total_savings'] > 0 || $result['total_shares'] > 0 || $result['total_loan_repayment'] > 0 || $result['total_interest_paid'] > 0 || $result['total_commodity_repayment'] > 0 || $result['total_loan_taken'] > 0)) {
        // Round all values to 2 decimal places
        $total_savings = round(floatval($result['total_savings']), 2);
        $total_shares = round(floatval($result['total_shares']), 2);
        $total_loan_repayment = round(floatval($result['total_loan_repayment']), 2);
        $total_interest_paid = round(floatval($result['total_interest_paid']), 2);
        $total_commodity_repayment = round(floatval($result['total_commodity_repayment']), 2);
        $total_loan_taken = round(floatval($result['total_loan_taken']), 2);
        
        echo json_encode([
            "status" => "success",
            "period" => $result['PayrollPeriod'],
            "month" => $month,
            "year" => $year,
            "data" => [
                "savings_contribution" => number_format($total_savings, 2, '.', ''),
                "shares_contribution" => number_format($total_shares, 2, '.', ''),
                "loan_repayment" => number_format($total_loan_repayment, 2, '.', ''),
                "interest_paid" => number_format($total_interest_paid, 2, '.', ''),
                "commodity_repayment" => number_format($total_commodity_repayment, 2, '.', ''),
                "loan_taken" => number_format($total_loan_taken, 2, '.', ''),
                "currency" => "NGN"
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "not_found", 
            "message" => "No records found for $month $year"
        ]);
    }
}
?>