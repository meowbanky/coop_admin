<?php
// admin/login.php

session_start();

// If already logged in, redirect to admin dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: approve_changes.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/Database.php';

    $database = new Database();
    $db = $database->getConnection();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'All fields are required';
    } else {
        // Check if user exists and is an admin
        $stmt = $db->prepare("
            SELECT u.id, u.staff_id, u.password_hash, e.NAME 
            FROM tbl_users u
            JOIN employee e ON u.staff_id = e.staff_id
            WHERE e.EMAIL = ? AND e.is_admin = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['NAME'];
            $_SESSION['staff_id'] = $user['staff_id'];

            // Update last login
            $update = $db->prepare("
                UPDATE tbl_users 
                SET last_login = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $update->execute([$user['id']]);

            // Redirect to admin dashboard
            header('Location: approve_changes.php');
            exit();
        } else {
            $error = 'Invalid credentials or insufficient privileges';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - OOUTH Staff Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 150px;
            height: auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #1a237e;
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        .form-control {
            padding: 12px;
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #1a237e;
            border: none;
            padding: 12px;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #0d1757;
        }
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="login-container">
        <div class="logo">
            <img src="../assets/images/oouth_logo.png" alt="OOUTH Logo">
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Admin Login</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                               required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password"
                               name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Login
                    </button>
                </form>
            </div>
        </div>
        <div class="text-center mt-3">
            <a href="../" class="text-muted">Back to Staff Portal</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
