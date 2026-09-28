<?php
/**
 * Access rule for endpoints that change member or contribution data:
 * the caller must be logged in and hold an admin role.
 */

const ADMIN_ROLE_KEYWORD = 'admin';

/**
 * Returns an error message when the session may not use an admin endpoint, or
 * null when access is allowed. Pass $_SESSION.
 *
 * The role is matched loosely (any role containing "admin", in any letter case)
 * so that types such as "Administrator" or "Super Admin" are accepted.
 */
function findAdminAccessError(array $session)
{
    $memberId = trim((string) ($session['SESS_MEMBER_ID'] ?? ''));

    if ($memberId === '') {
        return 'Unauthorized access: Session not found. Please login.';
    }

    $role = (string) ($session['role'] ?? $session['admin_type'] ?? '');

    if (stripos($role, ADMIN_ROLE_KEYWORD) === false) {
        return 'Unauthorized access: Admin privileges required.';
    }

    return null;
}
