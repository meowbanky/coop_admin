<?php
require_once('Connections/coop.php');
session_start();

// Clear any existing session data
unset($_SESSION['SESS_MEMBER_ID']);
unset($_SESSION['SESS_PRICE_AJUSTMENT']);
unset($_SESSION['ERRMSG_ARR']);

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header("Location: home.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for success message
    session_start();
    $_SESSION['logout_success'] = true;
    
    // Redirect to login page to prevent resubmission
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OOUTH COOP - Login</title>

    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Logo and Header -->
        <div class="text-center fade-in">
            <div class="mx-auto h-20 w-20 bg-white rounded-full flex items-center justify-center shadow-lg mb-6">
                <img src="img/header_logo.png" alt="OOUTH COOP" class="h-12 w-12 object-contain">
            </div>
            <h2 class="text-3xl font-bold text-white mb-2">OOUTH COOP</h2>
            <p class="text-white/80 text-lg">Salary Manager System</p>
            <p class="text-white/60 text-sm mt-2">Version 15.5</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 fade-in">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back</h3>
                <p class="text-gray-600">Please sign in to your account to continue</p>
            </div>

            <form id="loginForm" class="space-y-6">
                <!-- Username Field -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                        placeholder="Enter your username" autocomplete="username">
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pr-12"
                            placeholder="Enter your password" autocomplete="current-password">
                        <button type="button" id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span id="errorText"></span>
                    </div>
                </div>

                <!-- Success Message -->
                <div id="successMessage"
                    class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span id="successText"></span>
                    </div>
                </div>

                <!-- Login Button -->
                <button type="submit" id="loginBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <span id="loginBtnText">Sign In</span>
                    <i id="loginSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                </button>

                <!-- Forgot Password Link -->
                <div class="text-center">
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-key mr-1"></i>Forgot your password?
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center text-white/60 text-sm fade-in">
            <p>&copy; 2024 OOUTH COOP. All rights reserved.</p>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin text-blue-600"></i>
            <span class="text-gray-700">Signing you in...</span>
        </div>
    </div>

    <script>
    class LoginManager {
        constructor() {
            this.init();
        }

        init() {
            this.setupEventListeners();
            this.checkForMessages();
            this.autoFocus();
        }

        setupEventListeners() {
            // Form submission
            $('#loginForm').on('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });

            // Password toggle
            $('#togglePassword').on('click', () => {
                this.togglePasswordVisibility();
            });

            // Clear messages on input
            $('#username, #password').on('input', () => {
                this.hideMessages();
            });
        }

        checkForMessages() {
            // Check for logout success message
            <?php if (isset($_SESSION['logout_success'])): ?>
            this.showSuccess('You have been successfully logged out.');
            <?php unset($_SESSION['logout_success']); ?>
            <?php endif; ?>

            // Check for error messages
            <?php if (isset($_SESSION['ERRMSG_ARR']) && !empty($_SESSION['ERRMSG_ARR'])): ?>
            const errors = <?php echo json_encode($_SESSION['ERRMSG_ARR']); ?>;
            this.showError(errors.join('<br>'));
            <?php unset($_SESSION['ERRMSG_ARR']); ?>
            <?php endif; ?>
        }

        autoFocus() {
            if ($('#username').val() === '') {
                $('#username').focus();
            } else if ($('#password').val() === '') {
                $('#password').focus();
            }
        }

        togglePasswordVisibility() {
            const passwordField = $('#password');
            const passwordIcon = $('#passwordIcon');

            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                passwordIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                passwordIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        }

        async handleLogin() {
            const username = $('#username').val().trim();
            const password = $('#password').val();

            // Basic validation
            if (!username || !password) {
                this.showError('Please enter both username and password.');
                return;
            }

            this.showLoading();

            try {
                const response = await $.ajax({
                    url: 'login.php',
                    type: 'POST',
                    data: {
                        username: username,
                        password: password
                    },
                    dataType: 'json'
                });

                if (response.success === 'true') {
                    this.showSuccess('Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'home.php';
                    }, 1500);
                } else {
                    this.showError(response.message || 'Login failed. Please check your credentials.');
                }
            } catch (error) {
                console.error('Login error:', error);
                this.showError('An error occurred during login. Please try again.');
            } finally {
                this.hideLoading();
            }
        }

        showLoading() {
            $('#loginBtn').prop('disabled', true);
            $('#loginBtnText').text('Signing In...');
            $('#loginSpinner').removeClass('hidden');
            $('#loadingOverlay').removeClass('hidden');
        }

        hideLoading() {
            $('#loginBtn').prop('disabled', false);
            $('#loginBtnText').text('Sign In');
            $('#loginSpinner').addClass('hidden');
            $('#loadingOverlay').addClass('hidden');
        }

        showError(message) {
            this.hideMessages();
            $('#errorText').html(message);
            $('#errorMessage').removeClass('hidden').addClass('fade-in');

            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.hideMessages();
            }, 5000);
        }

        showSuccess(message) {
            this.hideMessages();
            $('#successText').html(message);
            $('#successMessage').removeClass('hidden').addClass('fade-in');

            // Auto-hide after 3 seconds
            setTimeout(() => {
                this.hideMessages();
            }, 3000);
        }

        hideMessages() {
            $('#errorMessage').addClass('hidden').removeClass('fade-in');
            $('#successMessage').addClass('hidden').removeClass('fade-in');
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new LoginManager();
    });
    </script>
</body>

</html>