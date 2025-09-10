<?php
// admin/approve_changes.php

session_start();
require_once '../config/Database.php';
require_once '../utils/JWTHandler.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$filters = [
    'department' => $_GET['department'] ?? '',
    'status' => $_GET['status'] ?? 'pending',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];


$dept_query = "SELECT dept_id, dept FROM tbl_dept ORDER BY dept";
$dept_stmt = $db->prepare($dept_query);
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending changes
    $query = "
    SELECT 
        e.staff_id,
        e.NAME as staff_name,
        e.PPNO as staff_ppno,
        d.dept as department,
        pc.submitted_at,
        GROUP_CONCAT(DISTINCT pc.field_name) as changed_fields,
        (SELECT COUNT(*) FROM pending_qualification_changes pq 
         WHERE pq.staff_id = e.staff_id AND pq.status = ?) as qualification_changes
    FROM 
        employee e
        INNER JOIN pending_profile_changes pc ON e.staff_id = pc.staff_id
        LEFT JOIN tbl_dept d ON e.DEPTCD = d.dept_id
    WHERE 
        pc.status = ?
";
$params = [$filters['status'], $filters['status']];

if ($filters['department']) {
    $query .= " AND e.DEPTCD = ?";
    $params[] = $filters['department'];
}

if ($filters['search']) {
    $query .= " AND (e.NAME LIKE ? OR e.PPNO LIKE ?)";
    $params[] = "%{$filters['search']}%";
    $params[] = "%{$filters['search']}%";
}

if ($filters['date_from']) {
    $query .= " AND pc.submitted_at >= ?";
    $params[] = $filters['date_from'] . ' 00:00:00';
}

if ($filters['date_to']) {
    $query .= " AND pc.submitted_at <= ?";
    $params[] = $filters['date_to'] . ' 23:59:59';
}

$query .= " GROUP BY e.staff_id";

$stmt = $db->prepare($query);
$stmt->execute($params);
$pending_changes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Changes Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Pending Profile Changes</h2>
    <div class="table-responsive">
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control"
                               placeholder="Search name or PP number"
                               value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['dept_id'] ?>"
                                    <?= $filters['department'] == $dept['dept_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['dept']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control"
                               value="<?= $filters['date_from'] ?>" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control"
                               value="<?= $filters['date_to'] ?>" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="approve_changes.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Staff Name</th>
                <th>PP Number</th>
                <th>Department</th>
                <th>Changed Fields</th>
                <th>Qualification Changes</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pending_changes as $change): ?>
                <tr>
                    <td><?= htmlspecialchars($change['staff_name']) ?></td>
                    <td><?= htmlspecialchars($change['staff_ppno']) ?></td>
                    <td><?= htmlspecialchars($change['department']) ?></td>
                    <td><?= htmlspecialchars($change['changed_fields']) ?></td>
                    <td><?= $change['qualification_changes'] ?> changes</td>
                    <td><?= date('Y-m-d H:i:s', strtotime($change['submitted_at'])) ?></td>
                    <td>
                        <button
                            class="btn btn-sm btn-primary view-changes"
                            data-staff-id="<?= $change['staff_id'] ?>"
                        >
                            View Details
                        </button>
                        <button
                            class="btn btn-sm btn-success approve-changes"
                            data-staff-id="<?= $change['staff_id'] ?>"
                        >
                            Approve
                        </button>
                        <button
                            class="btn btn-sm btn-danger reject-changes"
                            data-staff-id="<?= $change['staff_id'] ?>"
                        >
                            Reject
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // View Changes
        document.querySelectorAll('.view-changes').forEach(button => {
            button.addEventListener('click', function() {
                const staffId = this.dataset.staffId;
                fetch(`get_change_details.php?staff_id=${staffId}`)
                    .then(response => response.json())
                    .then(data => {
                        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                        document.querySelector('#detailsModal .modal-body').innerHTML = data.html;
                        modal.show();
                    });
            });
        });

        // Approve Changes
        document.querySelectorAll('.approve-changes').forEach(button => {
            button.addEventListener('click', function() {
                const staffId = this.dataset.staffId;
                Swal.fire({
                    title: 'Confirm Approval',
                    text: 'Are you sure you want to approve these changes?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleAction(staffId, 'approve');
                    }
                });
            });
        });

        // Reject Changes
        document.querySelectorAll('.reject-changes').forEach(button => {
            button.addEventListener('click', function() {
                const staffId = this.dataset.staffId;
                Swal.fire({
                    title: 'Reject Changes',
                    input: 'textarea',
                    inputLabel: 'Reason for rejection',
                    inputPlaceholder: 'Enter the reason for rejection...',
                    inputAttributes: {
                        'required': 'true'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Reject',
                    confirmButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleAction(staffId, 'reject', result.value);
                    }
                });
            });
        });

        function handleAction(staffId, action, reason = null) {
            const data = {
                staff_id: staffId,
                action: action,
                reason: reason
            };

            fetch('handle_approval.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', data.message, 'success')
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'An error occurred', 'error');
                });
        }
    });
</script>
</body>
</html>


