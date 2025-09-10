<?php
/**
 * Loan Batch Management System - Home Dashboard
 * Modern MVC-like architecture with separated concerns
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Debug: Log the redirect
    error_log("Home.php: User not logged in, redirecting to index.php");
    error_log("Home.php: Session data: " . print_r($_SESSION, true));
    
    header('Location: index.php');
    exit();
}

// Check for login success message
$login_success = false;
if (isset($_SESSION['login_success']) && $_SESSION['login_success']) {
    $login_success = true;
    unset($_SESSION['login_success']); // Clear the flag
}

// Include database connection
require_once('Connections/coop.php');

// Include business logic classes
require_once('classes/BatchManager.php');
require_once('classes/ValidationHelper.php');
require_once('classes/ResponseHandler.php');

// Initialize classes
$batchManager = new BatchManager($coop, $database_coop);
$validator = new ValidationHelper();
$responseHandler = new ResponseHandler();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = $batchManager->handleRequest($_POST);
    $responseHandler->handleResponse($response);
}

// Get batches data
$batches = $batchManager->getAllBatches();
$totalBatches = count($batches);

// Include the view
include 'views/batch-management.php';
?>