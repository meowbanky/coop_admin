<?php
// logout.php - Loan module logout handler
session_start();

// Debug: Log before logout
error_log("Loan Logout: Before logout - Session data: " . print_r($_SESSION, true));

// Clear all session variables
$_SESSION = array();

// Also explicitly unset common session variables
unset($_SESSION['SESS_FIRST_NAME']);
unset($_SESSION['SESS_LAST_NAME']);
unset($_SESSION['role']);
unset($_SESSION['emptrack']);
unset($_SESSION['empDataTrack']);
unset($_SESSION['Batch']);
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['complete_name']);
unset($_SESSION['admin_type']);

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session for success message
session_start();
$_SESSION['logout_success'] = true;

// Debug: Log after logout
error_log("Loan Logout: Session destroyed successfully");

// Redirect to login page
header("Location: index.php");
exit();
?>