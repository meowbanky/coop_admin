<?php
class AuthMiddleware {
    public static function authenticate() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if already on login page to prevent redirect loop
        $currentScript = str_replace('/auth_api/admin_duty/', '', $_SERVER['SCRIPT_NAME']);
        if ($currentScript === 'login.php') {
            return null;
        }

        // First check session
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        // Then check for JWT in Authorization header
        $headers = apache_request_headers();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if ($auth_header && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            try {
                $token = $matches[1];
                $jwt = new JWTHandler();

                if ($jwt->validateToken($token)) {
                    // Token is valid, get user data and create session
                    $payload = $jwt->getPayload($token);
                    $database = new Database();
                    $db = $database->getConnection();

                    $query = "SELECT ms.*, td.dept as department 
                             FROM master_staff ms
                             LEFT JOIN tbl_dept td ON ms.DEPTCD = td.dept_id
                             WHERE ms.staff_id = :staff_id";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':staff_id', $payload['user_id']);
                    $stmt->execute();

                    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $_SESSION['user'] = [
                            'id' => $user['staff_id'],
                            'name' => $user['NAME'],
                            'email' => $user['email'],
                            'department' => $user['department'],
                            'token' => $token
                        ];
                        return $_SESSION['user'];
                    }
                }
            } catch (Exception $e) {
                // Log error but continue to redirect
                error_log("Auth error: " . $e->getMessage());
            }
        }

        // No valid session or token, redirect to login
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /auth_api/admin_duty/login.php');
        exit();
    }
}