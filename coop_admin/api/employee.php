<?php
header('Content-Type: application/json');
require_once('../Connections/coop.php');
include_once('../classes/model.php');

// Start session
session_start();

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createEmployee();
            break;
        case 'get':
            getEmployee();
            break;
        case 'update':
            updateEmployee();
            break;
        case 'change_status':
            changeEmployeeStatus();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function createEmployee() {
    global $conn;
    
    $coop_id = $_POST['coop_id'] ?? '';
    $staff_id = $_POST['staff_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Validate required fields
    if (empty($coop_id) || empty($staff_id) || empty($first_name) || empty($last_name) || empty($department)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        return;
    }
    
    // Validate StaffID is numeric
    if (!is_numeric($staff_id)) {
        echo json_encode(['success' => false, 'message' => 'Staff ID must be a number']);
        return;
    }
    
    // Check if CoopID already exists
    $check_sql = "SELECT COUNT(*) as count FROM tblemployees WHERE CoopID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$coop_id]);
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cooperative ID already exists']);
        return;
    }
    
    // Check if StaffID already exists
    $check_staff_sql = "SELECT COUNT(*) as count FROM tblemployees WHERE StaffID = ?";
    $check_staff_stmt = $conn->prepare($check_staff_sql);
    $check_staff_stmt->execute([$staff_id]);
    $staff_result = $check_staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff_result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Staff ID already exists']);
        return;
    }
    
    // Insert new employee
    $sql = "INSERT INTO tblemployees (CoopID, StaffID, FirstName, LastName, Department, JobPosition, Status, EmailAddress, MobileNumber, StreetAddress, DateInserted, InsertedBy) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $coop_id,
        $staff_id,
        $first_name,
        $last_name,
        $department,
        $position,
        $status,
        $email,
        $phone,
        $address,
        $_SESSION['SESS_MEMBER_ID']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Employee created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create employee']);
    }
}

function getEmployee() {
    global $conn;
    
    $coop_id = $_POST['coop_id'] ?? '';
    $staff_id = $_POST['staff_id'] ?? '';
    
    if (empty($coop_id) || empty($staff_id)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID and Staff ID are required']);
        return;
    }
    
    $sql = "SELECT * FROM tblemployees WHERE CoopID = ? AND StaffID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$coop_id, $staff_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($employee) {
        echo json_encode(['success' => true, 'employee' => $employee]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
}

function updateEmployee() {
    global $conn;
    
    $coop_id = $_POST['coop_id'] ?? '';
    $staff_id = $_POST['staff_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Validate required fields
    if (empty($coop_id) || empty($staff_id) || empty($first_name) || empty($last_name) || empty($department)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        return;
    }
    
    // Validate StaffID is numeric
    if (!is_numeric($staff_id)) {
        echo json_encode(['success' => false, 'message' => 'Staff ID must be a number']);
        return;
    }
    
    // Check if StaffID already exists (excluding current employee)
    $check_staff_sql = "SELECT COUNT(*) as count FROM tblemployees WHERE StaffID = ? AND CoopID != ?";
    $check_staff_stmt = $conn->prepare($check_staff_sql);
    $check_staff_stmt->execute([$staff_id, $coop_id]);
    $staff_result = $check_staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff_result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Staff ID already exists']);
        return;
    }
    
    // Get the original StaffID for the WHERE clause
    $original_staff_sql = "SELECT StaffID FROM tblemployees WHERE CoopID = ?";
    $original_staff_stmt = $conn->prepare($original_staff_sql);
    $original_staff_stmt->execute([$coop_id]);
    $original_staff = $original_staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$original_staff) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        return;
    }
    
    // Update employee
    $sql = "UPDATE tblemployees SET 
            StaffID = ?,
            FirstName = ?, 
            LastName = ?, 
            Department = ?, 
            JobPosition = ?, 
            Status = ?, 
            EmailAddress = ?, 
            MobileNumber = ?, 
            StreetAddress = ?, 
            DateUpdated = CURDATE(), 
            UpdatedBy = ?
            WHERE CoopID = ? AND StaffID = ?";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $staff_id,
        $first_name,
        $last_name,
        $department,
        $position,
        $status,
        $email,
        $phone,
        $address,
        $_SESSION['SESS_MEMBER_ID'],
        $coop_id,
        $original_staff['StaffID']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee']);
    }
}

function changeEmployeeStatus() {
    global $conn;
    
    $coop_id = $_POST['coop_id'] ?? '';
    
    if (empty($coop_id)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        return;
    }
    
    // Get current status
    $sql = "SELECT Status FROM tblemployees WHERE CoopID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$coop_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        return;
    }
    
    // Toggle status
    $new_status = ($employee['Status'] === 'Active') ? 'In-Active' : 'Active';
    
    // Update status
    $sql = "UPDATE tblemployees SET 
            Status = ?, 
            DateUpdated = CURDATE(), 
            UpdatedBy = ?
            WHERE CoopID = ?";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $new_status,
        $_SESSION['SESS_MEMBER_ID'],
        $coop_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => "Employee status changed to {$new_status}",
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee status']);
    }
}
?>