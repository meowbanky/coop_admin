<?php
/**
 * Shared input validation for the auth and profile endpoints.
 */
class Validator
{
    const MIN_EMAIL_LENGTH = 6;
    const MIN_PASSWORD_LENGTH = 8;

    // password_hash() with PASSWORD_DEFAULT (bcrypt) silently truncates beyond
    // 72 bytes, which would make the extra characters meaningless.
    const MAX_PASSWORD_BYTES = 72;

    /**
     * Validates an email beyond filter_var, which accepts consecutive dots and
     * single-character TLDs.
     */
    public static function isValidEmail($email)
    {
        $email = trim((string) $email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (strlen($email) < self::MIN_EMAIL_LENGTH) {
            return false;
        }

        if (strpos($email, '..') !== false) {
            return false;
        }

        $parts = explode('.', $email);

        return strlen(end($parts)) >= 2;
    }

    /**
     * Returns a message describing the first unmet password rule, or null when
     * the password is acceptable.
     */
    public static function passwordError($password)
    {
        if (!is_string($password) || $password === '') {
            return 'Password is required';
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters';
        }

        if (strlen($password) > self::MAX_PASSWORD_BYTES) {
            return 'Password must be ' . self::MAX_PASSWORD_BYTES . ' characters or fewer';
        }

        if (!preg_match('/[A-Za-z]/', $password)) {
            return 'Password must contain at least one letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number';
        }

        return null;
    }
}
