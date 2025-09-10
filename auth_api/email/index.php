<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
$config = require_once 'config.php';
require_once 'email_manager.php';

// Initialize email manager
$email_manager = new EmailAccountManager(
$config['cpanel']['username'],
$config['cpanel']['password'],
$config['cpanel']['domain']
);

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
try {
switch ($_POST['action']) {
case 'create':
$result = $email_manager->createEmailAccount(
$_POST['email_user'],
$_POST['email_password'],
$_POST['quota']
);
if ($result['success']) {
$message = "Email account created successfully";
$messageType = "success";
} else {
throw new Exception("Failed to create email account");
}
break;

case 'delete':
$result = $email_manager->deleteEmailAccount($_POST['email']);
if ($result['success']) {
$message = "Email account deleted successfully";
$messageType = "success";
} else {
throw new Exception("Failed to delete email account");
}
break;

case 'change_password':
$result = $email_manager->changePassword(
$_POST['email'],
$_POST['new_password']
);
if ($result['success']) {
$message = "Password changed successfully";
$messageType = "success";
} else {
throw new Exception("Failed to change password");
}
break;
}
} catch (Exception $e) {
$message = $e->getMessage();
$messageType = "danger";
}
}

// Get list of email accounts
try {
$accounts = $email_manager->listEmailAccounts();
} catch (Exception $e) {
$message = $e->getMessage();
$messageType = "danger";
$accounts = ['data' => []];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OOUTH Email Account Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">OOUTH Email Account Manager</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Create Email Account Form -->
    <div class="card mb-4">
        <div class="card-header">Create New Email Account</div>
        <div class="card-body">
            <form method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">Email Username</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="email_user" required
                               pattern="[a-zA-Z0-9._-]+" title="Only letters, numbers, and common email characters allowed">
                        <span class="input-group-text">@oouth.com</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="email_password"
                           required minlength="8" id="password">
                    <div class="form-text">Minimum 8 characters</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Quota (MB)</label>
                    <input type="number" class="form-control" name="quota" value="250"
                           required min="25" max="1000">
                </div>

                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
        </div>
    </div>

    <!-- Email Accounts List -->
    <div class="card">
        <div class="card-header">Existing Email Accounts</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Email Address</th>
                        <th>Quota</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (isset($accounts['data'])): ?>
                        <?php foreach ($accounts['data'] as $account): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($account['email'] . '@' . $account['domain']); ?></td>
                                <td><?php echo htmlspecialchars($account['disk_quota'] . ' MB'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-2"
                                            onclick="changePassword('<?php echo htmlspecialchars($account['email']); ?>')">
                                        Change Password
                                    </button>
                                    <button class="btn btn-sm btn-danger"
                                            onclick="deleteAccount('<?php echo htmlspecialchars($account['email']); ?>')">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function validateForm() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return false;
        }

        if (password.length < 8) {
            alert('Password must be at least 8 characters long!');
            return false;
        }

        return true;
    }

    function changePassword(email) {
        const newPassword = prompt('Enter new password for ' + email + '@oouth.com');
        if (newPassword) {
            if (newPassword.length < 8) {
                alert('Password must be at least 8 characters long!');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="email" value="${email}">
                <input type="hidden" name="new_password" value="${newPassword}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteAccount(email) {
        if (confirm('Are you sure you want to delete ' + email + '@oouth.com?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="email" value="${email}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
</body>
</html>