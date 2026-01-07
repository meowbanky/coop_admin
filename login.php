<?php
require_once('Connections/coop.php'); 

// provides $conn (PDO)
session_start();

/* Response arrays */
$errmsg_arr = [];
$data = [];

/* Validate input */
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '') {
    $errmsg_arr[] = 'Username missing';
}
if ($password === '') {
    $errmsg_arr[] = 'Password missing';
}

if (!empty($errmsg_arr)) {
    $_SESSION['ERRMSG_ARR'] = $errmsg_arr;
    session_write_close();
    header("location: index.php");
    exit();
}

try {

    /* Prepare query */
    $sql = "
        SELECT 
            user_id,
            AdminType,
            CompleteName,
            Username,
            UPassword
        FROM tblusers
        WHERE Username = :username
          AND Status = 'Active'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

	$stmt->execute([':username' => $username]);


    $user = $stmt->fetch();

    if ($user) {

        $stored_password = $user['UPassword'];
        $password_valid = false;

        /* Password compatibility logic */
        if (strpos($stored_password, '*') === 0) {
            // Legacy crypt hash
            $password_valid = (crypt($password, $stored_password) === $stored_password);
        } elseif (
            str_starts_with($stored_password, '$2y$') ||
            str_starts_with($stored_password, '$2a$') ||
            str_starts_with($stored_password, '$2b$')
        ) {
            // Modern bcrypt
            $password_valid = password_verify($password, $stored_password);
        } else {
            // Plain text fallback (legacy)
            $password_valid = ($password === $stored_password);
        }

        if ($password_valid) {

            session_regenerate_id(true);

            /* Legacy session variables */
            $_SESSION['SESS_MEMBER_ID']  = $user['user_id'];
            $_SESSION['SESS_FIRST_NAME'] = $user['CompleteName'];
            $_SESSION['SESS_LAST_NAME']  = $user['CompleteName'];
            $_SESSION['role']            = $user['AdminType'];
            $_SESSION['emptrack']        = 0;
            $_SESSION['empDataTrack']    = 'next';

            /* Loan system variables */
            $_SESSION['user_id']        = $user['user_id'];
            $_SESSION['username']       = $user['Username'];
            $_SESSION['complete_name']  = $user['CompleteName'];
            $_SESSION['admin_type']     = $user['AdminType'];

            $data['success'] = true;
            $data['message'] = 'Successfully Login';

        } else {
            $data['success'] = false;
            $data['message'] = 'Invalid Password';
        }

    } else {
        $data['success'] = false;
        $data['message'] = 'Invalid Username';
    }

} catch (PDOException $e) {
    error_log('Login Error: ' . $e->getMessage());
    $data['success'] = false;
    $data['message'] = 'System error, please try again';
}

/* Return JSON */
echo json_encode($data);
exit;