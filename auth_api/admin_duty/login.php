<?php
session_start();

// If already logged in, redirect to intended URL or dashboard
if (isset($_SESSION['user'])) {
    $redirect = isset($_SESSION['intended_url']) ? $_SESSION['intended_url'] : '/auth_api/admin_duty/dashboard.php';
    unset($_SESSION['intended_url']);
    header('Location: ' . $redirect);
    exit();
}

// Handle API login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/Database.php';
    require_once '../utils/JWTHandler.php';

    try {
        $database = new Database();
        $db = $database->getConnection();

        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $query = "SELECT 
                    ms.*, 
                    td.dept as department
                 FROM master_staff ms
                 LEFT JOIN tbl_dept td ON ms.DEPTCD = td.dept_id
                 WHERE ms.email = :email
                 LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {
                $jwt = new JWTHandler();
                $token = $jwt->generateToken($user['staff_id']);

                $_SESSION['user'] = [
                    'id' => $user['staff_id'],
                    'name' => $user['NAME'],
                    'email' => $user['email'],
                    'department' => $user['department'],
                    'token' => $token
                ];

                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user['staff_id'],
                        'name' => $user['NAME'],
                        'email' => $user['email'],
                        'department' => $user['department']
                    ]
                ]);
                exit;
            }
        }

        throw new Exception('Invalid credentials');

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OOUTH Staff Portal - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
<div class="max-w-md w-full space-y-8">
    <div class="text-center">
        <img src="assets/images/oouth_logo.png" alt="OOUTH Logo" class="mx-auto h-16 w-auto">
        <h2 class="mt-6 text-3xl font-bold text-gray-900">Staff Portal</h2>
        <p class="mt-2 text-sm text-gray-600">Sign in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="mt-8 bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form id="loginForm" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" required
                           class="appearance-none block w-full px-3 py-2 border border-gray-300
                                      rounded-md shadow-sm placeholder-gray-400
                                      focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="mt-1">
                    <input id="password" name="password" type="password" required
                           class="appearance-none block w-full px-3 py-2 border border-gray-300
                                      rounded-md shadow-sm placeholder-gray-400
                                      focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember_me" name="remember_me" type="checkbox"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">Remember me</label>
                </div>
            </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent
                                   rounded-md shadow-sm text-sm font-medium text-white bg-blue-600
                                   hover:bg-blue-700 focus:outline-none focus:ring-2
                                   focus:ring-offset-2 focus:ring-blue-500">
                    Sign in
                </button>
            </div>
        </form>

        <div id="errorAlert" class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3
                                      rounded relative hidden" role="alert">
            <span id="errorMessage" class="block sm:inline"></span>
        </div>
    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const errorAlert = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');
        errorAlert.classList.add('hidden');

        try {
            const response = await fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value
                })
            });

            const data = await response.json();

            if (data.success) {
                // Store token based on remember me
                if (document.getElementById('remember_me').checked) {
                    localStorage.setItem('auth_token', data.token);
                } else {
                    sessionStorage.setItem('auth_token', data.token);
                }

                // Store user data
                sessionStorage.setItem('user', JSON.stringify(data.user));

                // Redirect
                window.location.href = '<?php echo isset($_SESSION['intended_url']) ?
                    $_SESSION['intended_url'] : '/auth_api/admin_duty/dashboard.php'; ?>';
            } else {
                errorMessage.textContent = data.message;
                errorAlert.classList.remove('hidden');
            }
        } catch (error) {
            errorMessage.textContent = 'An error occurred. Please try again.';
            errorAlert.classList.remove('hidden');
        }
    });
</script>
</body>
</html>