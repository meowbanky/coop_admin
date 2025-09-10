<?php
session_start();
require_once '../config/Database.php';
require_once '../utils/JWTHandler.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle filters
$filters = [
    'staff_id' => $_GET['staff_id'] ?? '',
    'change_type' => $_GET['change_type'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'page' => max(1, intval($_GET['page'] ?? 1)),
];

$per_page = 20;
$offset = ($filters['page'] - 1) * $per_page;

// Build query
$query = "
    SELECT 
        l.*,
        e.NAME as staff_name,
        e.PPNO as staff_ppno,
        c.NAME as changed_by_name,
        d.dept as department
    FROM 
        profile_change_log l
        JOIN employee e ON l.staff_id = e.staff_id
        JOIN employee c ON l.changed_by = c.staff_id
        LEFT JOIN tbl_dept d ON e.DEPTCD = d.dept_id
    WHERE 1=1
";

$params = [];

if ($filters['staff_id']) {
    $query .= " AND (e.staff_id = ? OR e.PPNO LIKE ?)";
    $params[] = $filters['staff_id'];
    $params[] = "%{$filters['staff_id']}%";
}

if ($filters['change_type']) {
    $query .= " AND l.change_type = ?";
    $params[] = $filters['change_type'];
}

if ($filters['date_from']) {
    $query .= " AND l.changed_at >= ?";
    $params[] = $filters['date_from'] . ' 00:00:00';
}

if ($filters['date_to']) {
    $query .= " AND l.changed_at <= ?";
    $params[] = $filters['date_to'] . ' 23:59:59';
}

// Get total count for pagination
$count_query = str_replace('SELECT l.*,', 'SELECT COUNT(*) as total', $query);
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// Add pagination to main query
$query .= " ORDER BY l.changed_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;

$stmt = $db->prepare($query);
$stmt->execute($params);
$changes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Audit Trail</h2>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="staff_id" class="form-control"
                           placeholder="Staff ID or PP Number"
                           value="<?= htmlspecialchars($filters['staff_id']) ?>">
                </div>
                <div class="col-md-2">
                    <select name="change_type" class="form-select">
                        <option value="">All Changes</option>
                        <option value="update" <?= $filters['change_type'] == 'update' ? 'selected' : '' ?>>Updates</option>
                        <option value="add" <?= $filters['change_type'] == 'add' ? 'selected' : '' ?>>Additions</option>
                        <option value="delete" <?= $filters['change_type'] == 'delete' ? 'selected' : '' ?>>Deletions</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control"
                           value="<?= $filters['date_from'] ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control"
                           value="<?= $filters['date_to'] ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="audit_trail.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Trail Table -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Date/Time</th>
                <th>Staff</th>
                <th>Department</th>
                <th>Field</th>
                <th>Change Type</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Changed By</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($changes as $change): ?>
                <tr>
                    <td><?= date('Y-m-d H:i:s', strtotime($change['changed_at'])) ?></td>
                    <td>
                        <?= htmlspecialchars($change['staff_name']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($change['staff_ppno']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($change['department']) ?></td>
                    <td><?= htmlspecialchars($change['field_name']) ?></td>
                    <td><?= htmlspecialchars($change['change_type']) ?></td>
                    <td><?= htmlspecialchars($change['old_value'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($change['new_value'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($change['changed_by_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $filters['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>