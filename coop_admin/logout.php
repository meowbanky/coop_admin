<?php  
//logout.php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Start a new session for success message
session_start();
$_SESSION['logout_success'] = true;

// Redirect to login page
header("Location: index.php");
exit();
?>