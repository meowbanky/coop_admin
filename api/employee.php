<?php
header('Content-Type: application/json');
require_once('../Connections/coop.php');
include_once('../classes/model.php');
require_once('../includes/session_helper.php');
require_once('../includes/admin_access_helper.php');
require_once('../includes/coop_id_helper.php');
require_once('../includes/validation_helper.php');
require_once('../includes/member_identity_helper.php');

// Bounded retry for the rare case where two admins create a member at the same
// instant and land on the same generated CoopID.
const MAX_COOP_ID_ATTEMPTS = 5;
const MYSQL_DUPLICATE_KEY = '23000';

// Initialize session properly
initSession();

// Check authentication and admin role
$accessError = findAdminAccessError($_SESSION);
if ($accessError !== null) {
    echo json_encode(['success' => false, 'message' => $accessError]);
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
    // Log the detail; never return driver/SQL internals to the browser.
    error_log('Employee API error (' . $action . '): ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again or contact support.'
    ]);
}

/**
 * Reads and trims the employee fields shared by create and update.
 */
function collectEmployeeInput() {
    $fields = [
        'staff_id', 'first_name', 'last_name', 'department', 'position',
        'email', 'phone', 'address',
        'nok_first_name', 'nok_middle_name', 'nok_last_name', 'nok_tel'
    ];

    $input = [];
    foreach ($fields as $field) {
        $input[$field] = trim($_POST[$field] ?? '');
    }
    $input['status'] = trim($_POST['status'] ?? 'Active');

    return $input;
}

/**
 * Returns an error message for the first failed rule, or null when valid.
 * Uniqueness is checked separately by findInputIdentityConflict().
 *
 * $requireEmail is true when creating: new members must have an address, since
 * it is what lets them register in the mobile app. It is false when editing, so
 * that legacy members with no address on file can still be corrected — an admin
 * fixing a StaffID should not be forced to invent an email first.
 */
function validateEmployeeInput($input, $requireEmail = true) {
    if (empty($input['staff_id']) || empty($input['first_name']) ||
        empty($input['last_name']) || empty($input['department'])) {
        return 'Required fields are missing';
    }

    if ($requireEmail && $input['email'] === '') {
        return 'Required fields are missing';
    }

    if (!is_numeric($input['staff_id'])) {
        return 'Staff ID must be a number';
    }

    // 0 was historically used as "unknown", which collided across members.
    if ((int) $input['staff_id'] <= 0) {
        return 'Staff ID must be greater than zero';
    }

    // Format is checked only when an address was supplied.
    if ($input['email'] !== '' && !isValidEmailAddress($input['email'])) {
        return 'Please enter a valid email address';
    }

    if (!isValidEmployeeStatus($input['status'])) {
        return 'Invalid status selected';
    }

    return null;
}

/**
 * Returns an error message when saving $input would give two ACTIVE members the
 * same StaffID or email, or null when it is safe to save.
 *
 * Records of withdrawn members are kept as In-Active and may share a StaffID or
 * email with the record a returning member is given, so only an active record
 * has to hold them exclusively. $excludeCoopId skips the member being edited.
 */
function findInputIdentityConflict($conn, $input, $excludeCoopId = null) {
    if ($input['status'] !== EMPLOYEE_STATUS_ACTIVE) {
        return null;
    }

    return findActiveIdentityConflict($conn, $input['staff_id'], $input['email'], $excludeCoopId);
}

function createEmployee() {
    global $conn;

    $input = collectEmployeeInput();

    $error = validateEmployeeInput($input) ?? findInputIdentityConflict($conn, $input);
    if ($error !== null) {
        echo json_encode(['success' => false, 'message' => $error]);
        return;
    }

    $coopId = insertEmployeeWithGeneratedCoopId($conn, $input);

    if ($coopId === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Could not assign a Cooperative ID. Please try again.'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Employee created successfully',
        'coop_id' => $coopId
    ]);
}

/**
 * Generates the CoopID server-side and inserts the member in one transaction so
 * two admins submitting at the same time cannot claim the same ID. Retries on a
 * duplicate-key collision; returns the assigned CoopID, or null if it never won.
 */
function insertEmployeeWithGeneratedCoopId($conn, $input) {
    $sql = "INSERT INTO tblemployees (CoopID, StaffID, FirstName, LastName, Department, JobPosition, Status, EmailAddress, MobileNumber, StreetAddress, NOKFirstName, NOKMiddleName, NOKLastName, NOKTel, DateInserted, InsertedBy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";

    for ($attempt = 1; $attempt <= MAX_COOP_ID_ATTEMPTS; $attempt++) {
        $conn->beginTransaction();

        try {
            $coopId = generateNextCoopID($conn, true);

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $coopId,
                $input['staff_id'],
                $input['first_name'],
                $input['last_name'],
                $input['department'],
                $input['position'],
                $input['status'],
                $input['email'],
                $input['phone'],
                $input['address'],
                $input['nok_first_name'],
                $input['nok_middle_name'],
                $input['nok_last_name'],
                $input['nok_tel'],
                $_SESSION['SESS_MEMBER_ID']
            ]);

            $conn->commit();

            return $coopId;
        } catch (PDOException $e) {
            $conn->rollBack();

            // Another admin took this CoopID first — regenerate and try again.
            if ($e->getCode() === MYSQL_DUPLICATE_KEY && $attempt < MAX_COOP_ID_ATTEMPTS) {
                error_log("CoopID collision on attempt {$attempt}, retrying: " . $e->getMessage());
                continue;
            }

            throw $e;
        }
    }

    return null;
}

function getEmployee() {
    global $conn;
    
    $coop_id = $_POST['coop_id'] ?? '';

    if (empty($coop_id)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        return;
    }

    // Keyed on CoopID only — StaffID may be NULL and would match nothing.
    $sql = "SELECT * FROM tblemployees WHERE CoopID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$coop_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($employee) {
        echo json_encode(['success' => true, 'employee' => $employee]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
}

function updateEmployee() {
    global $conn;

    $coop_id = trim($_POST['coop_id'] ?? '');
    $input = collectEmployeeInput();

    if (empty($coop_id)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        return;
    }

    $error = validateEmployeeInput($input, false) ?? findInputIdentityConflict($conn, $input, $coop_id);
    if ($error !== null) {
        echo json_encode(['success' => false, 'message' => $error]);
        return;
    }

    $staff_id = $input['staff_id'];
    $first_name = $input['first_name'];
    $last_name = $input['last_name'];
    $department = $input['department'];
    $position = $input['position'];
    $status = $input['status'];
    $email = $input['email'];
    $phone = $input['phone'];
    $address = $input['address'];
    $nok_first_name = $input['nok_first_name'];
    $nok_middle_name = $input['nok_middle_name'];
    $nok_last_name = $input['nok_last_name'];
    $nok_tel = $input['nok_tel'];

    // Confirm the member exists. The update keys on CoopID alone: StaffID may be
    // NULL for members whose payroll ID is not yet assigned, and "StaffID = NULL"
    // matches nothing, which would make the update a silent no-op.
    $exists_stmt = $conn->prepare("SELECT 1 FROM tblemployees WHERE CoopID = ?");
    $exists_stmt->execute([$coop_id]);

    if ($exists_stmt->fetchColumn() === false) {
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
            NOKFirstName = ?,
            NOKMiddleName = ?,
            NOKLastName = ?,
            NOKTel = ?,
            DateUpdated = CURDATE(),
            UpdatedBy = ?
            WHERE CoopID = ?";

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
        $nok_first_name,
        $nok_middle_name,
        $nok_last_name,
        $nok_tel,
        $_SESSION['SESS_MEMBER_ID'],
        $coop_id
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
    $sql = "SELECT Status, StaffID, EmailAddress FROM tblemployees WHERE CoopID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$coop_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        return;
    }

    // Toggle status
    $new_status = ($employee['Status'] === EMPLOYEE_STATUS_ACTIVE)
        ? EMPLOYEE_STATUS_INACTIVE
        : EMPLOYEE_STATUS_ACTIVE;

    // Reactivating an old record must not leave a returning member with two
    // active records sharing one StaffID or email.
    if ($new_status === EMPLOYEE_STATUS_ACTIVE) {
        $conflict = findActiveIdentityConflict(
            $conn,
            $employee['StaffID'],
            trim((string) $employee['EmailAddress']),
            $coop_id
        );

        if ($conflict !== null) {
            echo json_encode(['success' => false, 'message' => $conflict]);
            return;
        }
    }

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