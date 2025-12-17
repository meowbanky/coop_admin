<?php
// Database connection
require_once('Connections/coop.php');
require_once('classes/MemberAccountManager.php');
require_once('classes/ResponseHandler.php');

// Start session
session_start();

// Initialize classes
$memberAccountManager = new MemberAccountManager($coop, $database_coop);
$responseHandler = new ResponseHandler();

// Fetch banks for the dropdown
$banks = $memberAccountManager->getBanks();

// Include the modern view
include('views/member-account-update.php');
?>