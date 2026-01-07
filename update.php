<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

?>

<?php

function sendMail($coop_no){
    require_once __DIR__ . '/Connections/coop.php';
    global $conn;

    if (!$conn) {
        trigger_error("Connection failed", E_USER_ERROR);
    }

    try {
        $sql = "SELECT
            tblemployees.LastName,
            tblemployees.FirstName,
            tblemployees.MiddleName,
            tblusers_online.PlainPassword,
            tblusers_online.Username,
            tblemployees.EmailAddress
            FROM
            tblusers_online
            INNER JOIN tblemployees ON tblemployees.CoopID = tblusers_online.Username WHERE Username = :username";

        $stmt = $conn->prepare($sql);
        $stmt->execute(['username' => $coop_no]);
        $row_period = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row_period) {
             error_log("No user found for email sending: " . $coop_no);
             return;
        }

        $email_register = $row_period["EmailAddress"];
        $username = $row_period["Username"];
        $password = $row_period["PlainPassword"];
        $first_name = $row_period["FirstName"];
        $last_name = $row_period["LastName"];
        
        $sendmessage = "Dear {$first_name} {$last_name}, Password change process has occured on your coop account. Your login details can be found below: <br>username = {$username}<br> Password = {$password} <br />If you did not carry out this change, Please login and change your password immediately.";

        require "mail/vendor/autoload.php";
        
        // ... PHPMailer setup ...
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = SMTP::DEBUG_OFF;

        require_once __DIR__ . '/config/EnvConfig.php';
        $smtpHost = EnvConfig::get('SMTP_HOST', 'mail.emmaggi.com');
        $smtpPort = EnvConfig::get('SMTP_PORT', 465);
        $smtpUsername = EnvConfig::get('SMTP_USERNAME', 'vcms@emmaggi.com');
        $smtpPassword = EnvConfig::get('SMTP_PASSWORD', '');
        
        $mail->Host = $smtpHost;
        $mail->Port = (int)$smtpPort;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->setFrom("no-reply@emmaggi.com", "OOUTHCOOP");
        $mail->addReplyTo("no-reply@emmaggi.com", "OOUTHCOOP");
        $mail->addAddress($email_register, $first_name);
        $mail ->addBCC('bankole.adesoji@gmail.com');
        $mail->Subject = "OOUTHCOOP MOBILE APP";
        $mail->AltBody = "This is a plain-text message body";
        $mail->Body = $sendmessage;

        if (!$mail->send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        }

    } catch (PDOException $e) {
        error_log("Database Error in sendMail: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Mail Error in sendMail: " . $e->getMessage());
    }
}

function resetMail($coop_no){
    require_once __DIR__ . '/Connections/coop.php';
    global $conn;

    if (!$conn) {
        trigger_error("Connection failed", E_USER_ERROR);
    }

    try {
        $sql = "SELECT
            tblemployees.LastName,
            tblemployees.FirstName,
            tblemployees.MiddleName,
            tblusers_online.PlainPassword,
            tblusers_online.Username,
            tblemployees.EmailAddress
            FROM
            tblusers_online
            INNER JOIN tblemployees ON tblemployees.CoopID = tblusers_online.Username WHERE Username = :username";

        $stmt = $conn->prepare($sql);
        $stmt->execute(['username' => $coop_no]);
        $row_period = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row_period) {
             error_log("No user found for password reset email: " . $coop_no);
             return;
        }

        $email_register = $row_period["EmailAddress"];
        $username = $row_period["Username"];
        $password = $row_period["PlainPassword"];
        $first_name = $row_period["FirstName"];
        $last_name = $row_period["LastName"];
        
        $sendmessage = "Dear {$first_name} {$last_name}, your login details can be found below: <br>username = {$username}<br> Password = {$password}";
        
        require "mail/vendor/autoload.php";
        
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = SMTP::DEBUG_OFF;

        require_once __DIR__ . '/config/EnvConfig.php';
        $smtpHost = EnvConfig::get('SMTP_HOST', 'mail.emmaggi.com');
        $smtpPort = EnvConfig::get('SMTP_PORT', 465);
        $smtpUsername = EnvConfig::get('SMTP_USERNAME', 'vcms@emmaggi.com');
        $smtpPassword = EnvConfig::get('SMTP_PASSWORD', '');
        
        $mail->Host = $smtpHost;
        $mail->Port = (int)$smtpPort;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->setFrom("no-reply@emmaggi.com", "OOUTHCOOP");
        $mail->addReplyTo("no-reply@emmaggi.com", "OOUTHCOOP");
        $mail->addAddress($email_register, $first_name);
        $mail ->addBCC('bankole.adesoji@gmail.com');
        $mail->Subject = "OOUTHCOOP MOBILE APP";
        $mail->AltBody = "This is a plain-text message body";
        $mail->Body = $sendmessage;

        if (!$mail->send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        }

    } catch (PDOException $e) {
        error_log("Database Error in resetMail: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Mail Error in resetMail: " . $e->getMessage());
    }
}
?>