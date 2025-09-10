<?php 
ini_set('max_execution_time', '300');
require_once('Connections/coop.php');
include_once('classes/model.php');

// Set page title
$pageTitle = 'OOUTH COOP - Employee Management';

// Include header
include 'includes/header.php';

// Get pagination parameters
$results_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.FirstName LIKE ? OR e.LastName LIKE ? OR e.CoopID LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($department)) {
    $where_conditions[] = "e.Department = ?";
    $params[] = $department;
}

if (!empty($status)) {
    $where_conditions[] = "e.Status = ?";
    $params[] = $status;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM tblemployees e $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->execute($params);
} else {
    $count_stmt->execute();
}
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $results_per_page);

// Get employees data
$sql = "SELECT e.*, e.Department as DepartmentName
        FROM tblemployees e 
        $where_clause 
        ORDER BY e.FirstName, e.LastName 
        LIMIT $results_per_page OFFSET $offset";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments for filter (from the Department field in tblemployees)
$dept_sql = "SELECT DISTINCT Department as DepartmentName FROM tblemployees WHERE Department IS NOT NULL AND Department != '' ORDER BY Department";
$dept_stmt = $conn->prepare($dept_sql);
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate next CoopID
function generateNextCoopID($conn) {
    $sql = "SELECT CoopID FROM tblemployees WHERE CoopID LIKE 'COOP-%' ORDER BY CoopID DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $lastCoopID = $result['CoopID'];
        $number = intval(substr($lastCoopID, 5)); // Extract number part after "COOP-"
        $nextNumber = $number + 1;
        return 'COOP-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    } else {
        return 'COOP-00001';
    }
}

$nextCoopID = generateNextCoopID($conn);
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Employee Management</h1>
            <p class="text-gray-600 mt-2">Manage employee records and information</p>
        </div>
        <div class="flex items-center space-x-4">
            <button onclick="openAddModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Employee
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Employees</p>
                <p class="text-2xl font-bold text-gray-900"><?= $total_records ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Active</p>
                <p class="text-2xl font-bold text-gray-900">
                    <?php
                    $active_sql = "SELECT COUNT(*) as count FROM tblemployees WHERE Status = 'Active'";
                    $active_stmt = $conn->prepare($active_sql);
                    $active_stmt->execute();
                    echo $active_stmt->fetch()['count'];
                    ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-times-circle text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">In-Active</p>
                <p class="text-2xl font-bold text-gray-900">
                    <?php
                    $inactive_sql = "SELECT COUNT(*) as count FROM tblemployees WHERE Status = 'In-Active'";
                    $inactive_stmt = $conn->prepare($inactive_sql);
                    $inactive_stmt->execute();
                    echo $inactive_stmt->fetch()['count'];
                    ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-building text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Departments</p>
                <p class="text-2xl font-bold text-gray-900"><?= count($departments) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="bg-white rounded-lg shadow-lg p-6 mb-8">
    <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by name or CoopID..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept['DepartmentName']) ?>"
                        <?= $department === $dept['DepartmentName'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['DepartmentName']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="In-Active" <?= $status === 'In-Active' ? 'selected' : '' ?>>In-Active</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <a href="employee.php"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Employees Table -->
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Employees List</h3>
        <p class="text-sm text-gray-600">Showing <?= count($employees) ?> of <?= $total_records ?> employees</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S/N</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CoopID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($employees)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p class="text-lg">No employees found</p>
                        <p class="text-sm">Try adjusting your search criteria</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($employees as $index => $employee): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= $offset + $index + 1 ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?= htmlspecialchars($employee['CoopID']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            <?= htmlspecialchars($employee['EmailAddress'] ?? '') ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= htmlspecialchars($employee['Department'] ?? 'N/A') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $employee['Status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= htmlspecialchars($employee['Status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <button onclick="editEmployee(<?= htmlspecialchars(json_encode($employee)) ?>)"
                            class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button
                            onclick="deleteEmployee('<?= $employee['CoopID'] ?>', '<?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>')"
                            class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing page <?= $page ?> of <?= $total_pages ?> (<?= $total_records ?> total employees)
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&department=<?= urlencode($department) ?>&status=<?= urlencode($status) ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&department=<?= urlencode($department) ?>&status=<?= urlencode($status) ?>"
                    class="px-3 py-2 text-sm font-medium <?= $i === $page ? 'text-blue-600 bg-blue-50 border-blue-300' : 'text-gray-500 bg-white border-gray-300' ?> border rounded-md hover:bg-gray-50">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&department=<?= urlencode($department) ?>&status=<?= urlencode($status) ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>

<!-- Add Employee Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Add New Employee</h3>
            </div>
            <form id="addEmployeeForm" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Staff ID *</label>
                        <input type="text" id="add_staff_id" name="staff_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CoopID *</label>
                        <input type="text" id="add_coop_id" name="coop_id" value="<?= $nextCoopID ?>" readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" id="add_first_name" name="first_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" id="add_last_name" name="last_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" id="add_email" name="email" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="text" id="add_phone" name="phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                        <input type="text" id="add_department" name="department" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select id="add_status" name="status" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Active">Active</option>
                            <option value="In-Active">In-Active</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeAddModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Add Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Edit Employee</h3>
            </div>
            <form id="editEmployeeForm" class="p-6 space-y-4">
                <input type="hidden" id="edit_id" name="id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Staff ID *</label>
                        <input type="text" id="edit_staff_id" name="staff_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CoopID *</label>
                        <input type="text" id="edit_coop_id" name="coop_id" readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" id="edit_first_name" name="first_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" id="edit_last_name" name="last_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" id="edit_email" name="email" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="text" id="edit_phone" name="phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                        <input type="text" id="edit_department" name="department" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select id="edit_status" name="status" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="Active">Active</option>
                            <option value="In-Active">In-Active</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeEditModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
class EmployeeManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Add employee form
        document.getElementById('addEmployeeForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addEmployee();
        });

        // Edit employee form
        document.getElementById('editEmployeeForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateEmployee();
        });
    }

    openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
        document.getElementById('addEmployeeForm').reset();
    }

    openEditModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }

    closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editEmployeeForm').reset();
    }

    editEmployee(employee) {
        document.getElementById('edit_id').value = employee.id;
        document.getElementById('edit_staff_id').value = employee.StaffID || '';
        document.getElementById('edit_coop_id').value = employee.CoopID || '';
        document.getElementById('edit_first_name').value = employee.FirstName || '';
        document.getElementById('edit_last_name').value = employee.LastName || '';
        document.getElementById('edit_email').value = employee.EmailAddress || '';
        document.getElementById('edit_phone').value = employee.PhoneNumber || '';
        document.getElementById('edit_department').value = employee.Department || '';
        document.getElementById('edit_status').value = employee.Status || 'Active';

        this.openEditModal();
    }

    async addEmployee() {
        const formData = new FormData(document.getElementById('addEmployeeForm'));

        try {
            const response = await fetch('api/employee.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                CoopUtils.showSuccess('Employee added successfully!');
                this.closeAddModal();
                location.reload();
            } else {
                CoopUtils.showError(result.message || 'Failed to add employee');
            }
        } catch (error) {
            console.error('Error:', error);
            CoopUtils.showError('An error occurred while adding employee');
        }
    }

    async updateEmployee() {
        const formData = new FormData(document.getElementById('editEmployeeForm'));

        try {
            const response = await fetch('api/employee.php', {
                method: 'PUT',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                CoopUtils.showSuccess('Employee updated successfully!');
                this.closeEditModal();
                location.reload();
            } else {
                CoopUtils.showError(result.message || 'Failed to update employee');
            }
        } catch (error) {
            console.error('Error:', error);
            CoopUtils.showError('An error occurred while updating employee');
        }
    }

    deleteEmployee(id, name) {
        CoopUtils.showConfirm(
            `Are you sure you want to change the status of ${name}?`,
            'Change Employee Status',
            () => this.confirmDelete(id)
        );
    }

    async confirmDelete(id) {
        try {
            const response = await fetch('api/employee.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=change_status&coop_id=${encodeURIComponent(id)}`
            });

            const result = await response.json();

            if (result.success) {
                CoopUtils.showSuccess('Employee status updated successfully!');
                location.reload();
            } else {
                CoopUtils.showError(result.message || 'Failed to update employee status');
            }
        } catch (error) {
            console.error('Error:', error);
            CoopUtils.showError('An error occurred while updating employee status');
        }
    }
}

// Global functions for backward compatibility
const employeeManager = new EmployeeManager();

function openAddModal() {
    employeeManager.openAddModal();
}

function closeAddModal() {
    employeeManager.closeAddModal();
}

function openEditModal() {
    employeeManager.openEditModal();
}

function closeEditModal() {
    employeeManager.closeEditModal();
}

function editEmployee(employee) {
    employeeManager.editEmployee(employee);
}

function deleteEmployee(id, name) {
    employeeManager.deleteEmployee(id, name);
}
</script>