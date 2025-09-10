<?php

session_start();

if (isset($_GET['logout'])) {
    // Debug: Log before logout
    error_log("Logout: Before logout - Session data: " . print_r($_SESSION, true));
    
    // Unset all session variables (including legacy ones)
    $_SESSION = array();
    
    // Also explicitly unset common session variables from your system
    unset($_SESSION['SESS_FIRST_NAME']);
    unset($_SESSION['SESS_LAST_NAME']);
    unset($_SESSION['role']);
    unset($_SESSION['emptrack']);
    unset($_SESSION['empDataTrack']);
    unset($_SESSION['Batch']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['complete_name']);
    unset($_SESSION['admin_type']);
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Start a new session to show the success message
    session_start();
    
    // Debug: Log after logout
    error_log("Logout: After logout - Session destroyed and restarted");
    
    $success_message = 'You have been successfully logged out.';
}

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header('Location: home.php');
    exit();
}

// Include database connection
require_once('Connections/coop.php');

// Handle login form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Sanitize input
        $username = mysqli_real_escape_string($coop, $username);
        
        // Query user from database
        $sql = "SELECT user_id, Username, UPassword, CompleteName, AdminType, Status 
                FROM tblusers 
                WHERE Username = '$username' AND Status = 'Active'";
        
        $result = mysqli_query($coop, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Debug: Log the password comparison
            error_log("Login attempt - Username: $username, DB Password: " . $user['UPassword'] . ", Input Password: $password");
            
            // Verify password using standard logic
            $passwordValid = false;
            
            // Check if password is legacy bcrypt hash (starts with *)
            if (strpos($user['UPassword'], '*') === 0) {
                // For legacy bcrypt hashes starting with *, use crypt() function
                $passwordValid = (crypt($password, $user['UPassword']) === $user['UPassword']);
            } elseif (strpos($user['UPassword'], '$2y$') === 0 || 
                      strpos($user['UPassword'], '$2a$') === 0 || 
                      strpos($user['UPassword'], '$2b$') === 0) {
                // Standard bcrypt with $2y$, $2a$, $2b$
                $passwordValid = password_verify($password, $user['UPassword']);
            } else {
                // Plain text password comparison
                $passwordValid = ($password === $user['UPassword']);
            }
            
            if ($passwordValid) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['complete_name'] = $user['CompleteName'];
                $_SESSION['admin_type'] = $user['AdminType'];
                
                error_log("Login successful for user: " . $user['Username']);
                
                // Set success message in session for display on home page
                $_SESSION['login_success'] = true;
                
                // Redirect to home page
                header('Location: home.php');
                exit();
            } else {
                error_log("Login failed - Invalid password for user: $username");
                $error_message = 'Invalid password. Please check your password and try again.';
            }
            } else {
            error_log("Login failed - User not found or inactive: $username");
            $error_message = 'Invalid username or account is inactive.';
        }
    }
}

// Handle logout

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cooperative Admin System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .login-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    </style>
</head>

<body class="login-bg min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div
                    class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-building text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Cooperative Admin</h1>
                <p class="text-gray-600 mt-2">Sign in to your account</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if (!empty($error_message)): ?>
            <div id="error-message"
                class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center animate-pulse">
                <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
                <span class="font-medium"><?= htmlspecialchars($error_message) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
            <div id="success-message"
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                <span class="font-medium"><?= htmlspecialchars($success_message) ?></span>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input type="text" id="username" name="username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                        placeholder="Enter your username" required>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 pr-12"
                            placeholder="Enter your password" required>
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login"
                    class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-4 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Secure login system
                </p>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="text-center mt-6">
            <p class="text-white text-sm opacity-80">
                © <?= date('Y') ?> Cooperative Admin System. All rights reserved.
            </p>
        </div>
    </div>

    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('password-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordIcon.classList.remove('fa-eye');
            passwordIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            passwordIcon.classList.remove('fa-eye-slash');
            passwordIcon.classList.add('fa-eye');
        }
    }

    // Auto-focus on username field
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('username').focus();
    });

    // Clear error messages when user starts typing
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const errorDiv = document.getElementById('error-message');
                if (errorDiv) {
                    errorDiv.style.transition = 'opacity 0.3s ease';
                    errorDiv.style.opacity = '0';
                    setTimeout(function() {
                        errorDiv.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
    </script>
</body>

</html>