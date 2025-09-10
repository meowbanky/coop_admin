<?php
header('Content-Type: application/json');
require_once('../Connections/coop.php');
require_once('../classes/ResponseHandler.php');

$responseHandler = new ResponseHandler();

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_commodities':
            getCommodities();
            break;
        case 'edit_commodity':
            editCommodity();
            break;
        case 'delete_commodity':
            deleteCommodity();
            break;
        default:
            $responseHandler->error('Invalid action');
    }
} catch (Exception $e) {
    error_log("Commodity API Error: " . $e->getMessage());
    $responseHandler->error('An error occurred: ' . $e->getMessage());
}

function getCommodities() {
    global $responseHandler, $conn;
    
    $period = $_POST['period'] ?? '';
    
    // Debug logging
    error_log("Commodity API - Period received: " . $period);
    error_log("Commodity API - POST data: " . print_r($_POST, true));
    
    if (empty($period)) {
        echo json_encode(['success' => false, 'message' => 'Period is required']);
        exit();
    }
    
    try {
        // Check if connection exists
        if (!$conn) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit();
        }
        
        // First, let's test a simple query
        $testQuery = $conn->prepare("SELECT COUNT(*) as count FROM tbl_commodity");
        $testQuery->execute();
        $testResult = $testQuery->fetch(PDO::FETCH_ASSOC);
        error_log("Commodity table count: " . $testResult['count']);
        
        $query = $conn->prepare("
            SELECT 
                c.*,
                CONCAT(e.FirstName, ' ', e.LastName) as member_name,
                e.CoopID,
                p.PayrollPeriod
            FROM tbl_commodity c
            LEFT JOIN tblemployees e ON c.coopID = e.CoopID
            LEFT JOIN tbpayrollperiods p ON c.Period = p.id
            WHERE c.Period = ?
            ORDER BY c.dateOfInsertion DESC
        ");
        
        $query->execute([$period]);
        $commodities = $query->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Commodities found: " . count($commodities));
        error_log("Commodities data: " . print_r($commodities, true));
        
        $response = [
            'success' => true,
            'message' => 'Commodities loaded successfully',
            'data' => $commodities
        ];
        echo json_encode($response);
        exit();
        
    } catch (PDOException $e) {
        error_log("Get Commodities Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to load commodities: ' . $e->getMessage()]);
        exit();
    }
}

function editCommodity() {
    global $responseHandler, $conn;
    
    $commodity_id = $_POST['commodity_id'] ?? '';
    $commodity = $_POST['commodity'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $commodity_type = $_POST['commodity_type'] ?? '';
    $period = $_POST['period'] ?? '';
    
    if (empty($commodity_id) || empty($commodity) || empty($amount) || empty($period)) {
        $responseHandler->error('All fields are required');
        return;
    }
    
    try {
        $query = $conn->prepare("
            UPDATE tbl_commodity 
            SET Commodity = ?, amount = ?, CommodityType = ?, Period = ?
            WHERE commodity_id = ?
        ");
        
        $result = $query->execute([$commodity, $amount, $commodity_type, $period, $commodity_id]);
        
        if ($result) {
            $responseHandler->success('Commodity updated successfully');
        } else {
            $responseHandler->error('Failed to update commodity');
        }
        
    } catch (PDOException $e) {
        error_log("Edit Commodity Error: " . $e->getMessage());
        $responseHandler->error('Failed to update commodity');
    }
}

function deleteCommodity() {
    global $responseHandler, $conn;
    
    $commodity_id = $_POST['commodity_id'] ?? '';
    
    if (empty($commodity_id)) {
        $responseHandler->error('Commodity ID is required');
        return;
    }
    
    try {
        $query = $conn->prepare("DELETE FROM tbl_commodity WHERE commodity_id = ?");
        $result = $query->execute([$commodity_id]);
        
        if ($result) {
            $responseHandler->success('Commodity deleted successfully');
        } else {
            $responseHandler->error('Failed to delete commodity');
        }
        
    } catch (PDOException $e) {
        error_log("Delete Commodity Error: " . $e->getMessage());
        $responseHandler->error('Failed to delete commodity');
    }
}
?>