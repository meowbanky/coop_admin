<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Database connection
require_once('Connections/coop.php');
require_once('classes/BeneficiaryManager.php');
require_once('classes/BeneficiaryValidator.php');
require_once('classes/ResponseHandler.php');

// Initialize classes
$beneficiaryManager = new BeneficiaryManager($coop, $database_coop);
$validator = new BeneficiaryValidator();
$responseHandler = new ResponseHandler();

// Check if session batch is set
if (isset($_GET['Session_batch'])) {
    $_SESSION['Batch'] = $_GET['Session_batch'];
} else {
    // Redirect to home if no batch is set
    // header("Location: home.php");
    // exit();
}

// Handle legacy GET requests for deletion
if (isset($_GET['beneficiaryCode']) && isset($_SESSION['Batch'])) {
    $response = $beneficiaryManager->deleteBeneficiary($_GET['beneficiaryCode'], $_SESSION['Batch']);
    if ($response['success']) {
        header("Location: beneficiary.php?Session_batch=" . urlencode($_SESSION['Batch']));
        exit();
    }
}

// Handle legacy form submissions
if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'eduEntry') {
    $response = $beneficiaryManager->updateBeneficiary($_POST);
    // Redirect to prevent form resubmission
    header("Location: beneficiary.php?Session_batch=" . urlencode($_SESSION['Batch']));
    exit();
}

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'eduEntry') {
    $response = $beneficiaryManager->addBeneficiary($_POST);
    // Redirect to prevent form resubmission
    header("Location: beneficiary.php?Session_batch=" . urlencode($_SESSION['Batch']));
    exit();
}

// Fetch data for the view
$batch = $_SESSION['Batch'] ?? '';
$beneficiaries = [];
$batchTotal = 0;

if (!empty($batch)) {
    $beneficiaries = $beneficiaryManager->getBeneficiaries($batch);
    $batchTotal = $beneficiaryManager->getBatchTotal($batch);
}


// Include the modern view
include('views/beneficiary-management.php');
?>