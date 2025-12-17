<?php
// admin/get_change_details.php

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/Database.php';

try {
    if (!isset($_GET['staff_id'])) {
        throw new Exception('Staff ID is required');
    }

    $database = new Database();
    $db = $database->getConnection();

    $staff_id = $_GET['staff_id'];

    // Get profile changes
    $profile_stmt = $db->prepare("
        SELECT pc.*, e.NAME as staff_name, d.dept as department
        FROM pending_profile_changes pc
        JOIN employee e ON pc.staff_id = e.staff_id
        LEFT JOIN tbl_dept d ON e.DEPTCD = d.dept_id
        WHERE pc.staff_id = ? AND pc.status = 'pending'
    ");
    $profile_stmt->execute([$staff_id]);
    $profile_changes = $profile_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get qualification changes
    $qual_stmt = $db->prepare("
        SELECT pq.*, q.quaification as qualification
        FROM pending_qualification_changes pq
        LEFT JOIN qualification q ON pq.qua_id = q.id
        WHERE pq.staff_id = ? AND pq.status = 'pending'
    ");
    $qual_stmt->execute([$staff_id]);
    $qual_changes = $qual_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build HTML for the modal
    $html = '<div class="container">';

    // Staff info header
    if (!empty($profile_changes)) {
        $html .= sprintf(
            '<h5>Changes for: %s</h5><p>Department: %s</p>',
            htmlspecialchars($profile_changes[0]['staff_name']),
            htmlspecialchars($profile_changes[0]['department'])
        );
    }

    // Profile changes section
    if (!empty($profile_changes)) {
        $html .= '<h6 class="mt-4">Profile Changes</h6>';
        $html .= '<table class="table table-bordered">';
        $html .= '<thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead><tbody>';

        foreach ($profile_changes as $change) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($change['field_name']),
                htmlspecialchars($change['old_value'] ?? 'N/A'),
                htmlspecialchars($change['new_value'] ?? 'N/A')
            );
        }

        $html .= '</tbody></table>';
    }

    // Qualification changes section
    if (!empty($qual_changes)) {
        $html .= '<h6 class="mt-4">Qualification Changes</h6>';
        $html .= '<table class="table table-bordered">';
        $html .= '<thead><tr><th>Change Type</th><th>Qualification</th><th>Field</th><th>Institution</th><th>Year</th></tr></thead><tbody>';

        foreach ($qual_changes as $change) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($change['change_type']),
                htmlspecialchars($change['qualification'] ?? 'N/A'),
                htmlspecialchars($change['field'] ?? 'N/A'),
                htmlspecialchars($change['institution'] ?? 'N/A'),
                htmlspecialchars($change['year_obtained'] ?? 'N/A')
            );
        }

        $html .= '</tbody></table>';
    }

    if (empty($profile_changes) && empty($qual_changes)) {
        $html .= '<div class="alert alert-info">No pending changes found.</div>';
    }

    $html .= '</div>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}