<?php
/**
 * Member identity rules for tblemployees.
 *
 * A member who withdraws keeps their record, marked In-Active. If they rejoin
 * they are registered afresh under a new CoopID, so the same StaffID or email
 * can legitimately appear on several records. What must stay unique is the
 * ACTIVE holder: payroll uploads and the mobile login resolve a StaffID or email
 * to the active record.
 */

require_once __DIR__ . '/validation_helper.php';

/**
 * Returns the CoopID of the active member holding $value in $column, or null.
 * $column is never user input — callers pass a literal column name.
 */
function findActiveMemberCoopId($conn, $column, $value, $excludeCoopId = null)
{
    $sql = "SELECT CoopID FROM tblemployees WHERE {$column} = ? AND Status = ?";
    $params = [$value, EMPLOYEE_STATUS_ACTIVE];

    if ($excludeCoopId !== null) {
        $sql .= ' AND CoopID != ?';
        $params[] = $excludeCoopId;
    }

    $stmt = $conn->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $coopId = $stmt->fetchColumn();

    return $coopId === false ? null : $coopId;
}

/**
 * Returns an error message when another ACTIVE member already holds the StaffID
 * or email, or null when the record may be saved as active.
 * $excludeCoopId skips the member being edited or reactivated.
 *
 * StaffID 0 is the legacy "unassigned" placeholder shared by several members,
 * and a blank email means "not yet registered", so neither can conflict.
 */
function findActiveIdentityConflict($conn, $staffId, $email, $excludeCoopId = null)
{
    if ((int) $staffId > 0) {
        $holder = findActiveMemberCoopId($conn, 'StaffID', $staffId, $excludeCoopId);

        if ($holder !== null) {
            return "Staff ID already belongs to active member {$holder}. "
                . 'Set that record to In-Active first if the member is rejoining.';
        }
    }

    if ($email !== '') {
        $holder = findActiveMemberCoopId($conn, 'EmailAddress', $email, $excludeCoopId);

        if ($holder !== null) {
            return "Email address already belongs to active member {$holder}. "
                . 'Set that record to In-Active first if the member is rejoining.';
        }
    }

    return null;
}
