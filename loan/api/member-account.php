<?php
// Suppress warnings and errors for clean JSON output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once('../Connections/coop.php');
require_once('../classes/MemberAccountManager.php');
require_once('../classes/ResponseHandler.php');

header('Content-Type: application/json');

// Initialize classes
$memberAccountManager = new MemberAccountManager($coop, $database_coop);
$responseHandler = new ResponseHandler();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            if (!isset($input['action'])) {
                $response = ['success' => false, 'message' => 'No action specified'];
                break;
            }
            
            switch ($input['action']) {
                case 'update_personal':
                    if (empty($input['coop_id'])) {
                        $response = ['success' => false, 'message' => 'Member ID is required'];
                        break;
                    }
                    
                    $response = $memberAccountManager->updateMemberPersonal($input);
                    break;
                    
                case 'update_account':
                    if (empty($input['coop_id'])) {
                        $response = ['success' => false, 'message' => 'Member ID is required'];
                        break;
                    }
                    
                    $response = $memberAccountManager->updateMemberAccount($input);
                    break;
                    
                default:
                    $response = ['success' => false, 'message' => 'Invalid action'];
            }
            break;
            
        case 'GET':
            if (!isset($_GET['action'])) {
                $response = ['success' => false, 'message' => 'No action specified'];
                break;
            }
            
            switch ($_GET['action']) {
                case 'search_members':
                    if (empty($_GET['q'])) {
                        $response = ['success' => true, 'data' => []];
                        break;
                    }
                    
                    $members = $memberAccountManager->searchMembers($_GET['q']);
                    $response = [
                        'success' => true,
                        'data' => $members
                    ];
                    break;
                    
                case 'get_member_details':
                    $coopId = $_GET['coop_id'] ?? '';
                    if (empty($coopId)) {
                        $response = ['success' => false, 'message' => 'Member ID is required'];
                        break;
                    }
                    
                    $member = $memberAccountManager->getMemberDetails($coopId);
                    if ($member) {
                        $response = [
                            'success' => true,
                            'data' => $member
                        ];
                    } else {
                        $response = ['success' => false, 'message' => 'Member not found'];
                    }
                    break;
                    
                case 'get_account_history':
                    $coopId = $_GET['coop_id'] ?? '';
                    if (empty($coopId)) {
                        $response = ['success' => false, 'message' => 'Member ID is required'];
                        break;
                    }
                    
                    $history = $memberAccountManager->getMemberAccountHistory($coopId);
                    $response = [
                        'success' => true,
                        'data' => $history
                    ];
                    break;
                    
                case 'get_banks':
                    $banks = $memberAccountManager->getBanks();
                    $response = [
                        'success' => true,
                        'data' => $banks
                    ];
                    break;
                    
                default:
                    $response = ['success' => false, 'message' => 'Invalid action'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Method not allowed'];
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

// Send response
echo json_encode($response);
exit;
?>