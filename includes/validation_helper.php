<?php
/**
 * Shared input validation helpers for admin-facing endpoints.
 */

const MIN_EMAIL_LENGTH = 6;
const EMPLOYEE_STATUSES = ['Active', 'In-Active'];

/**
 * Validates an email address beyond PHP's default filter: rejects consecutive
 * dots and single-character TLDs, which filter_var accepts.
 */
function isValidEmailAddress($email)
{
    $email = trim((string) $email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (strlen($email) < MIN_EMAIL_LENGTH) {
        return false;
    }

    if (strpos($email, '..') !== false) {
        return false;
    }

    $parts = explode('.', $email);

    return strlen(end($parts)) >= 2;
}

/**
 * True when the status is one the application recognises.
 */
function isValidEmployeeStatus($status)
{
    return in_array($status, EMPLOYEE_STATUSES, true);
}
