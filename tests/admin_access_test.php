<?php
/**
 * Tests for includes/admin_access_helper.php.
 *
 * Run from the coop_admin directory:  php tests/admin_access_test.php
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../includes/admin_access_helper.php';

test('rejects a request with no session', function () {
    // Arrange
    $session = [];

    // Act
    $error = findAdminAccessError($session);

    // Assert
    assertContains('Please login', $error);
});

test('rejects a session whose member id is blank', function () {
    $error = findAdminAccessError(['SESS_MEMBER_ID' => '   ', 'role' => 'Admin']);

    assertContains('Please login', $error);
});

test('rejects a logged-in user who is not an admin', function () {
    $error = findAdminAccessError(['SESS_MEMBER_ID' => 'jane', 'role' => 'User']);

    assertContains('Admin privileges required', $error);
});

test('rejects a logged-in user with no role at all', function () {
    $error = findAdminAccessError(['SESS_MEMBER_ID' => 'jane']);

    assertContains('Admin privileges required', $error);
});

test('allows a logged-in admin', function () {
    $error = findAdminAccessError(['SESS_MEMBER_ID' => 'jane', 'role' => 'Admin']);

    assertSameValue(null, $error);
});

test('allows an admin whose role is only in admin_type', function () {
    $error = findAdminAccessError(['SESS_MEMBER_ID' => 'jane', 'admin_type' => 'Admin']);

    assertSameValue(null, $error);
});

test('matches admin roles regardless of letter case', function () {
    $error = findAdminAccessError(['SESS_MEMBER_ID' => 'jane', 'role' => 'Super ADMINISTRATOR']);

    assertSameValue(null, $error);
});

finishTests();
