<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'coop_admin/vendor/autoload.php';
include_once('db.php');
session_start();
header('Content-Type: application/json');


// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);


if(!isset($data['givenName'])){
    die(json_encode(['success' => false, 'message' => 'Cant get Name from Google']));
}
if(!isset($data['familyName'])){
    die(json_encode(['success' => false, 'message' => 'Cant get Surname from Google']));
}

$givenName = $data['givenName'];
$familyName = $data['familyName'];
$email = $data['email'];


// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$sql = "SELECT max(CoopID) FROM tblemployees WHERE FirstName = ? AND LastName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $givenName, $familyName);
$stmt->execute();
$stmt->store_result();

// Compare the name
if ($stmt->num_rows == 1) {
    // Bind the result to variables
    $stmt->bind_result($coopID);
    $stmt->fetch();
    $sql = "UPDATE tblemployees SET  EmailAddress = ? WHERE CoopID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email,$coopID);
    $stmt->execute();


    // Generate a unique token
    $token = bin2hex(random_bytes(50));
    $expires_at = date("Y-m-d H:i:s", strtotime('+2 hour'));

    // Store the token in the database
    $stmt = $conn->prepare("INSERT INTO password_resets (coop_id,email, token, expires_at) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $coopID,$email,$token,$expires_at);
    $stmt->execute();

    // Send the token to the user's email
    $resetLink = "http://emmaggi.com/coop_admin/update/change_password.php?token=$token";
    $subject = "Password Reset Request";
    $message = "Click on the link to reset your password: <a href='" . $resetLink . "'>link</a><br>Your username:$coopID";

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'mail.emmaggi.com'; // Specify main and backup SMTP servers
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'no-reply@emmaggi.com'; // SMTP username
        $mail->Password = 'Banzoo@7980'; // SMTP password
        $mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587; // TCP port to connect to

        // Recipients
        $mail->setFrom('no-reply@emmaggi.com', 'Password Rest');
        $mail->addAddress($email, $familyName); // Add a recipient
        $mail->addBCC('bankole.adesoji@gmail.com', 'Abiodun');

        // Add more recipients or CC/BCC as needed

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject =$subject;
        $mail->Body    = $message;
        $mail->AltBody = $message;

        // Send the email
        $mail->send();
        // If a matching user is found, return the CoopID
        echo json_encode(['success' => true,"message" => 'A password reset link has been sent to your email.']);
    } catch (Exception $e) {
        // If a matching user is found, return the CoopID
        echo json_encode(['success' => true,"message" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    // If no matching user is found
    echo json_encode(['success' => false, 'message' => 'Your details from google does not match the details from our end. \nPlease contact the secretary to update your details']);
}

?>
