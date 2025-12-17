<?php
header('Content-Type: application/json');
require_once('../Connections/coop.php');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetUsers();
            break;
        case 'POST':
            handleCreateUser();
            break;
        case 'PUT':
            handleUpdateUser();
            break;
        case 'DELETE':
            handleDeleteUser();
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    error_log("Error in users.php: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ];
    
    echo json_encode($response);
}

function handleGetUsers() {
    global $conn;
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $recordsPerPage = isset($_GET['records_per_page']) ? (int)$_GET['records_per_page'] : 100;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $userType = isset($_GET['user_type']) ? trim($_GET['user_type']) : '';
    
    $offset = ($page - 1) * $recordsPerPage;
    
    // Build the WHERE clause
    $whereConditions = ['1=1'];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(Username LIKE :search OR CompleteName LIKE :search OR user_id LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($status)) {
        $whereConditions[] = "Status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($userType)) {
        $whereConditions[] = "AdminType = :user_type";
        $params[':user_type'] = $userType;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Count total records
    $countQuery = "SELECT COUNT(*) as total FROM tblusers WHERE $whereClause";
    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    // Get users with pagination
    $query = "SELECT * FROM tblusers WHERE $whereClause ORDER BY user_id ASC LIMIT $recordsPerPage OFFSET $offset";
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'users' => $users,
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'records_per_page' => $recordsPerPage,
        'has_previous' => $page > 1,
        'has_next' => $page < $totalPages
    ];
    
    echo json_encode($response);
}

function handleCreateUser() {
    global $conn;
    
    $staffId = $_POST['staff_id'] ?? '';
    $staffName = $_POST['staff_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? '';
    
    if (empty($staffId) || empty($email) || empty($password) || empty($userType)) {
        throw new Exception('All fields are required');
    }
    
    // Check if user already exists
    $checkQuery = "SELECT COUNT(*) FROM tblusers WHERE user_id = :staff_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':staff_id', $staffId);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception('User already exists for this staff member');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $insertQuery = "INSERT INTO tblusers (user_id, Username, CompleteName, Email, Password, AdminType, Status, CreatedDate) 
                    VALUES (:user_id, :username, :complete_name, :email, :password, :admin_type, 'Active', NOW())";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bindParam(':user_id', $staffId);
    $stmt->bindParam(':username', $staffId); // Using staff ID as username
    $stmt->bindParam(':complete_name', $staffName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':admin_type', $userType);
    
    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'User created successfully'
        ];
    } else {
        throw new Exception('Failed to create user');
    }
    
    echo json_encode($response);
}

function handleUpdateUser() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'toggle_status') {
        // Toggle user status
        $userId = $input['user_id'];
        $newStatus = $input['status'];
        
        $updateQuery = "UPDATE tblusers SET Status = :status WHERE user_id = :user_id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'User status updated successfully'
            ];
        } else {
            throw new Exception('Failed to update user status');
        }
    } else {
        // Regular update
        $userId = $_POST['userId'] ?? '';
        $email = $_POST['email'] ?? '';
        $userType = $_POST['userType'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($userId)) {
            throw new Exception('User ID is required');
        }
        
        $updateQuery = "UPDATE tblusers SET Email = :email, AdminType = :admin_type, Status = :status WHERE user_id = :user_id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':admin_type', $userType);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'User updated successfully'
            ];
        } else {
            throw new Exception('Failed to update user');
        }
    }
    
    echo json_encode($response);
}

function handleDeleteUser() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? '';
    
    if (empty($userId)) {
        throw new Exception('User ID is required');
    }
    
    $deleteQuery = "DELETE FROM tblusers WHERE user_id = :user_id";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bindParam(':user_id', $userId);
    
    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'User deleted successfully'
        ];
    } else {
        throw new Exception('Failed to delete user');
    }
    
    echo json_encode($response);
}
?>
