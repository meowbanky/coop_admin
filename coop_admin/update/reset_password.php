<?php
include('db.php'); // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "Passwords do not match.";
        exit;
    }

    // Validate the token
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();

    if ($reset) {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Check if the user exists
        $stmt = $conn->prepare("SELECT * FROM tblusers_online WHERE Username = ?");
        $stmt->bind_param("s", $reset['coop_id']);
        $stmt->execute();
        $user_result = $stmt->get_result();

        if ($user_result->num_rows > 0) {
            // User exists, update the password
            $stmt = $conn->prepare("UPDATE tblusers_online SET UPassword = ?, CPassword = ?, PlainPassword = ?,first_login = 1 WHERE Username = ?");
            $stmt->bind_param("ssss", $hashed_password, $hashed_password, $password, $reset['coop_id']);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo "Password updated successfully.";
            } else {
                echo "No changes made or error updating password.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO tblusers_online (Username, UPassword, CPassword, PlainPassword,first_login,dateofRegistration) VALUES (?, ?, ?, ?,1,now())");
            $stmt->bind_param("ssss", $reset['coop_id'], $hashed_password, $hashed_password, $password);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo "User created and password set successfully.";
            } else {
                echo "Error creating user or setting password.";
            }
        }

        // Delete the token so it can't be used again
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        echo "Token has been deleted.";
    } else {
        echo "Invalid or expired token.";
    }

    // Close the statement
    $stmt->close();
}
?>
