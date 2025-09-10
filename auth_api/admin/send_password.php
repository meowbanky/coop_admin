<?php
//require __DIR__.'/../../vendor/autoload.php';
require_once '../config/Database.php';

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/EmailConfig.php';



require_once __DIR__ .'/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$database = new Database();
try {
    $conn = $database->getConnection();
} catch (\Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Generate random password
function generatePassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

// Search for employees
function searchEmployees($conn, $searchTerm) {
    try {
        $sql = "SELECT employee.staff_id, employee.`NAME`, employee.EMAIL, employee.TIN, employee.PPNO, tbl_users.plain_password FROM employee
                INNER JOIN tbl_users ON employee.staff_id = tbl_users.staff_id 
                WHERE (NAME LIKE :search 
                OR EMAIL LIKE :search 
                OR TIN LIKE :search 
                OR PPNO LIKE :search) and STATUSCD = 'A'";

        $stmt = $conn->prepare($sql);
        $searchTerm = "%{$searchTerm}%";
        $stmt->execute([':search' => $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        return [];
    }
}

// Update user password in database
function updateUserPassword($conn, $staffId, $password) {
    try {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO tbl_users (staff_id, password_hash, plain_password) 
                VALUES (:staff_id, :password_hash, :plain_password)
                ON DUPLICATE KEY UPDATE 
                password_hash = :password_hash,
                plain_password = :plain_password";

        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':staff_id' => $staffId,
            ':password_hash' => $passwordHash,
            ':plain_password' => $password
        ]);
    } catch (\PDOException $e) {
        error_log("Password update error: " . $e->getMessage());
        return false;
    }
}

// Get employee details by ID
function getEmployeeById($conn, $staffId) {
    try {
        $sql = "SELECT employee.staff_id, employee.EMAIL, employee.`NAME`, tbl_users.plain_password FROM employee INNER JOIN tbl_users ON employee.staff_id = tbl_users.staff_id WHERE employee.staff_id = :staff_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':staff_id' => $staffId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Get employee error: " . $e->getMessage());
        return false;
    }
}

// Send email with credentials
function sendCredentialEmail($email, $password,$name) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = EmailConfig::SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = EmailConfig::SMTP_USERNAME;
        $mail->Password = EmailConfig::SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = EmailConfig::SMTP_PORT;

        $mail->setFrom(EmailConfig::SMTP_FROM, 'OOUTH Password Reset');
        $mail->addAddress($email);
        $mail->addCC('Bankole.adesoji@gmail.com');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'OOUTH Mobile App Login Credentials';

        $emailBody = "
        <p>Dear {$name},</p>

<p>We are excited to introduce the new OOUTH Mobile App that allows you to:</p>
<ul>
    <li>View your payslips</li>
    <li>Access your employee profile</li>
    <li>Update your information</li>
    <li>And much more!</li>
</ul>

<p>Your login credentials for the mobile app are:</p>
<p><strong>Username:</strong> {$email}</p>
<p><strong>Password:</strong> {$password}</p>

<p>To get started:</p>
<ol>
    <li>Download the OOUTH Mobile App from: <a href='https://oouthsalary.com.ng/download.html'>Download App</a></li>
    <li>Install the app on your Android device</li>
    <li>Log in using your email and password above</li>
</ol>

Don't have an adroid phone, dont worrry about it, use the <a href='https://oouth-e2a0e.web.app/'>Web version</a> 
<br>Once it open's you may want to click the install button

<p>For security reasons, please change your password after your first login.</p>

<p>If you have any questions or need assistance, please contact the HR department.</p>

<p>Best regards,<br>OOUTH HR Team</p>";

        $mail->Body = $emailBody;
        $mail->send();
        error_log("Email sent successfully to: " . $email);
        return true;
    } catch (Exception $e) {
        error_log("Email send error: " . $e->getMessage());
        return false;
    }
}

// Process bulk password generation
function processEmployeeCredentials($conn) {
    try {
//        $sql = "SELECT staff_id, EMAIL, NAME FROM employee WHERE EMAIL IS NOT NULL AND EMAIL != ''";
        $sql = "SELECT employee.staff_id, employee.EMAIL, employee.`NAME`, tbl_users.plain_password FROM employee INNER JOIN tbl_users ON employee.staff_id = tbl_users.staff_id WHERE EMAIL IS NOT NULL AND EMAIL != '' AND staff_id = 1751";
        $stmt = $conn->query($sql);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $password = generatePassword();
            if (updateUserPassword($conn, $row['staff_id'], $row['plain_password'])) {
                if (sendCredentialEmail($row['EMAIL'], $password,$row['NAME'])) {
                    $results[] = [
                        'staff_id' => $row['staff_id'],
                        'email' => $row['EMAIL'],
                        'status' => 'success',
                        'message' => 'Credentials sent successfully to '.$row['NAME']
                    ];
                } else {
                    $results[] = [
                        'staff_id' => $row['staff_id'],
                        'email' => $row['EMAIL'],
                        'status' => 'error',
                        'message' => 'Failed to send email'
                    ];
                }
            }
        }
        return $results;
    } catch (\PDOException $e) {
        error_log("Bulk process error: " . $e->getMessage());
        return [['status' => 'error', 'message' => 'Database error occurred']];
    }
}

// Handle single employee password reset
function resetEmployeePassword($conn, $staffId) {
    try {
        error_log("Starting password reset for staff ID: " . $staffId);

        $employee = getEmployeeById($conn, $staffId);
        if (!$employee || empty($employee['EMAIL'])) {
            error_log("Employee not found or no email for staff ID: " . $staffId);
            return [
                'status' => 'error',
                'message' => 'Employee not found or no email address available'
            ];
        }

//        $password =  $password ; //generatePassword();
        error_log("Generated new password for staff ID: " . $staffId);

        if (updateUserPassword($conn, $staffId, $employee['plain_password'])) {
            error_log("Password updated in database for staff ID: " . $staffId);

            if (sendCredentialEmail($employee['EMAIL'], $employee['plain_password'],$employee['NAME'])) {
                error_log("Reset successful - email sent to: " . $employee['EMAIL']);
                return [
                    'status' => 'success',
                    'message' => 'Password reset and sent successfully to ' . $employee['EMAIL']
                ];
            } else {
                error_log("Failed to send email to: " . $employee['EMAIL']);
                return [
                    'status' => 'error',
                    'message' => 'Password reset but email failed to send'
                ];
            }
        }
        error_log("Failed to update password in database for staff ID: " . $staffId);
        return [
            'status' => 'error',
            'message' => 'Failed to reset password'
        ];
    } catch (\Exception $e) {
        error_log("Reset password error for staff ID {$staffId}: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'An error occurred during password reset: ' . $e->getMessage()
        ];
    }
}

// Sanitize input
// Sanitize input
function sanitizeInput($input) {
    // Handle null, empty values or non-string inputs
    if ($input === null || !is_string($input)) {
        return '';
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Credentials Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .results {
            margin-top: 20px;
        }
        .success {
            color: green;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
            margin: 5px 0;
        }
        .error {
            color: red;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
            margin: 5px 0;
        }
        .search-section {
            margin: 20px 0;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .search-results {
            margin-top: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .reset-button {
            padding: 5px 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .search-box {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
    <script>
        function confirmReset(name) {
            return confirm("Are you sure you want to reset the password for " + name + "?");
        }

        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
    </script>
</head>
<body>
<div id="loading" class="loading">Processing... Please wait...</div>

<h1>Employee Credentials Management</h1>

<!-- Search Section -->
<div class="search-section">
    <h2>Search Employee</h2>
    <form method="GET" class="container">
        <input type="text"
               name="search"
               placeholder="Search by name, email, TIN, or PPNO"
               class="search-box"
               value="<?php echo sanitizeInput((string)($_GET['search'] ?? '')); ?>">
        <button type="submit" class="button">Search</button>
    </form>

    <?php
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchResults = searchEmployees($conn, sanitizeInput($_GET['search']));
        if (!empty($searchResults)) {
            echo "<div class='search-results'>";
            echo "<table>";
            echo "<tr><th>Staff ID</th><th>Name</th><th>Email</th><th>TIN</th><th>PPNO</th><th>Action</th></tr>";
            foreach ($searchResults as $employee) {
                echo "<tr>";
                echo "<td>" . $employee['staff_id'] . "</td>";
                echo "<td>" . sanitizeInput($employee['NAME']) . "</td>";
                echo "<td>" . sanitizeInput($employee['EMAIL']) . "</td>";
                echo "<td>" . sanitizeInput($employee['TIN']) . "</td>";
                echo "<td>" . sanitizeInput($employee['PPNO']) . "</td>";
                echo "<td>
                            <form method='POST' style='display:inline;' onsubmit='return confirmReset(\"" . htmlspecialchars($employee['NAME'], ENT_QUOTES) . "\"); showLoading();'>
                                <input type='hidden' name='reset_staff_id' value='" . ($employee['staff_id']) . "'>
                                <input type='hidden' name='reset_password' value='" . ($employee['plain_password']) . "'>
                                <button type='submit' class='reset-button'>Reset Password</button>
                            </form>
                          </td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p>No employees found matching your search.</p>";
        }
    }
    ?>
</div>

<!-- Bulk Generation Section -->
<div class="bulk-section">
    <h2>Bulk Generate Credentials</h2>
    <form method="POST" onsubmit="showLoading();">
        <button type="submit" name="bulk_generate" class="button">Generate and Send All Credentials</button>
    </form>
</div>

<!-- Results Section -->
<div class="results">
    <?php
    // Handle individual password reset
    if (isset($_POST['reset_staff_id'])) {
        $staffId = $_POST['reset_staff_id'];
        $password = $_POST['reset_password'];
        error_log("Processing password reset request for staff ID: " . $staffId);
//        $employee = getEmployeeById($conn,$staffId);
        $result = resetEmployeePassword($conn, $staffId);
        echo "<p class='{$result['status']}'>{$result['message']}</p>";
    }

    // Handle bulk generation
    if (isset($_POST['bulk_generate'])) {
        $results = processEmployeeCredentials($conn);
        foreach ($results as $result) {
            $class = $result['status'] === 'success' ? 'success' : 'error';
            echo "<p class='{$class}'>" . sanitizeInput($result['message']) . "</p>";
        }
    }
    ?>
</div>

<script>
    // Hide loading indicator after page load
    window.onload = function() {
        document.getElementById('loading').style.display = 'none';
    }
</script>