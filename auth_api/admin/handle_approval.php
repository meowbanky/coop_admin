<?php
// admin/handle_approval.php

header('Content-Type: application/json');
session_start();

require_once '../config/Database.php';
require_once '../utils/JWTHandler.php';

require_once '../utils/EmailService.php';
$emailService = new EmailService();


// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['staff_id']) || !isset($data['action'])) {
        throw new Exception('Invalid request data');
    }

    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    $staff_id = $data['staff_id'];
    $action = $data['action'];
    $reason = $data['reason'] ?? null;
    $admin_id = $_SESSION['admin_id'];
    $admin_staff_id = $_SESSION['staff_id'];

    if ($action === 'approve') {
        // Get profile changes
        $profile_stmt = $db->prepare("
            SELECT field_name, new_value 
            FROM pending_profile_changes 
            WHERE staff_id = ? AND status = 'pending'
        ");
        $profile_stmt->execute([$staff_id]);
        $changes = $profile_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Apply profile changes
        foreach ($changes as $change) {
            $field = $change['field_name'];
            $value = $change['new_value'];

            $update_stmt = $db->prepare("
                UPDATE employee 
                SET $field = ?, 
                    last_approved_at = CURRENT_TIMESTAMP,
                    approved_by = ?
                WHERE staff_id = ?
            ");
            $update_stmt->execute([$value, $admin_staff_id, $staff_id]);
        }

        // Handle qualification changes
        $qual_stmt = $db->prepare("
            SELECT * FROM pending_qualification_changes 
            WHERE staff_id = ? AND status = 'pending'
        ");
        $qual_stmt->execute([$staff_id]);
        $qual_changes = $qual_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($qual_changes as $change) {
            switch ($change['change_type']) {
                case 'add':
                    $insert_stmt = $db->prepare("
                        INSERT INTO staff_qualification 
                        (staff_id, qua_id, field, institution, year_obtained)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $insert_stmt->execute([
                        $staff_id,
                        $change['qua_id'],
                        $change['field'],
                        $change['institution'],
                        $change['year_obtained']
                    ]);
                    break;

                case 'edit':
                    $update_stmt = $db->prepare("
                        UPDATE staff_qualification
                        SET qua_id = ?, field = ?, institution = ?, year_obtained = ?
                        WHERE id = ?
                    ");
                    $update_stmt->execute([
                        $change['qua_id'],
                        $change['field'],
                        $change['institution'],
                        $change['year_obtained'],
                        $change['original_qualification_id']
                    ]);
                    break;

                case 'delete':
                    $delete_stmt = $db->prepare("
                        DELETE FROM staff_qualification 
                        WHERE id = ?
                    ");
                    $delete_stmt->execute([$change['original_qualification_id']]);
                    break;
            }
        }

        // Update status of pending changes
        $update_status = $db->prepare("
            UPDATE pending_profile_changes 
            SET status = 'approved', 
                approved_by = ?,
                approved_at = CURRENT_TIMESTAMP
            WHERE staff_id = ? AND status = 'pending'
        ");
        $update_status->execute([$admin_staff_id, $staff_id]);

        $update_qual_status = $db->prepare("
            UPDATE pending_qualification_changes 
            SET status = 'approved', 
                approved_by = ?,
                approved_at = CURRENT_TIMESTAMP
            WHERE staff_id = ? AND status = 'pending'
        ");
        $update_qual_status->execute([$admin_staff_id, $staff_id]);

    } elseif ($action === 'reject') {
        if (empty($reason)) {
            throw new Exception('Rejection reason is required');
        }

        // Update status of pending changes
        $update_status = $db->prepare("
            UPDATE pending_profile_changes 
            SET status = 'rejected', 
                approved_by = ?,
                approved_at = CURRENT_TIMESTAMP,
                rejection_reason = ?
            WHERE staff_id = ? AND status = 'pending'
        ");
        $update_status->execute([$admin_staff_id, $reason, $staff_id]);

        $update_qual_status = $db->prepare("
            UPDATE pending_qualification_changes 
            SET status = 'rejected', 
                approved_by = ?,
                approved_at = CURRENT_TIMESTAMP,
                rejection_reason = ?
            WHERE staff_id = ? AND status = 'pending'
        ");
        $update_qual_status->execute([$admin_staff_id, $reason, $staff_id]);
    }

    // Reset pending changes flag
    $reset_pending = $db->prepare("
        UPDATE employee 
        SET has_pending_changes = false 
        WHERE staff_id = ?
    ");
    $reset_pending->execute([$staff_id]);

    $db->commit();


    // Get staff email
    $staff_stmt = $db->prepare("SELECT EMAIL, NAME FROM employee WHERE staff_id = ?");
    $staff_stmt->execute([$staff_id]);
    $staff_info = $staff_stmt->fetch(PDO::FETCH_ASSOC);

    if ($action === 'approve') {
        $emailService->sendApprovalNotification(
            $staff_info['EMAIL'],
            $staff_info['NAME'],
            $changes
        );
    } else {
        $emailService->sendRejectionNotification(
            $staff_info['EMAIL'],
            $staff_info['NAME'],
            $reason
        );
    }


    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' ? 'Changes approved successfully' : 'Changes rejected successfully'
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}