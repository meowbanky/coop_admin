<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;




?>

<?php

function sendMail($coop_no){
    
$hostname_coop = "localhost";
$database_coop = "emmaggic_coop";
$username_coop = "emmaggic_root";
$password_coop = "Oluwaseyi";
$conn = mysqli_connect($hostname_coop, $username_coop, $password_coop) or trigger_error(mysql_error(),E_USER_ERROR); 


$sql = "SELECT
tblemployees.LastName,
tblemployees.FirstName,
tblemployees.MiddleName,
tblusers_online.PlainPassword,
tblusers_online.Username,
tblemployees.EmailAddress
FROM
tblusers_online
INNER JOIN tblemployees ON tblemployees.CoopID = tblusers_online.Username WHERE Username = '".$coop_no."'";



    mysqli_select_db($conn,$database_coop);
    $Result1 = mysqli_query($conn,$sql) or die(mysqli_error($conn));
  
    $row_period = mysqli_fetch_assoc($Result1);
    $totalRows_period = mysqli_num_rows($Result1);
  

	$email_register = $row_period["EmailAddress"];
	$username = $row_period["Username"];
	$password = $row_period["PlainPassword"];
	$first_name = $row_period["FirstName"];
    $last_name = $row_period["LastName"];
    
	$sendmessage = "Dear {$first_name} {$last_name}, Password change process has occured on your coop account. Your login details can be found below: <br>username = {$username}<br> Password = {$password} <br />If you did not carry out this change, Please login and change your password immediately.";

	require "mail/vendor/autoload.php";

	//Create a new PHPMailer instance
	$mail = new PHPMailer();

	//Tell PHPMailer to use SMTP
	$mail->isSMTP();

	//Enable SMTP debugging
	//SMTP::DEBUG_OFF = off (for production use)
	//SMTP::DEBUG_CLIENT = client messages
	//SMTP::DEBUG_SERVER = client and server messages
	$mail->SMTPDebug = SMTP::DEBUG_OFF;

	//Set the hostname of the mail server
	$mail->Host = "mail.emmaggi.com";
	//Use `$mail->Host = gethostbyname('smtp.gmail.com');`
	//if your network does not support SMTP over IPv6,
	//though this may cause issues with TLS

	//Set the SMTP port number:
	// - 465 for SMTP with implicit TLS, a.k.a. RFC8314 SMTPS or
	// - 587 for SMTP+STARTTLS
	$mail->Port = 465;

	//Set the encryption mechanism to use:
	// - SMTPS (implicit TLS on port 465) or
	// - STARTTLS (explicit TLS on port 587)
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

	//Whether to use SMTP authentication
	$mail->SMTPAuth = true;

	//Username to use for SMTP authentication - use full email address for gmail
	$mail->Username = "vcms@emmaggi.com";

	//Password to use for SMTP authentication
	$mail->Password = "Banzoo@7980";

	//Set who the message is to be sent from
	//Note that with gmail you can only use your account address (same as `Username`)
	//or predefined aliases that you have configured within your account.
	//Do not use user-submitted addresses in here
	$mail->setFrom("no-reply@emmaggi.com", "OOUTHCOOP");

	//Set an alternative reply-to address
	//This is a good place to put user-submitted addresses
	$mail->addReplyTo("no-reply@emmaggi.com", "OOUTHCOOP");

	//Set who the message is to be sent to
	$mail->addAddress($email_register, $first_name);
	$mail ->addBCC('bankole.adesoji@gmail.com');
	
	//Set the subject line
	$mail->Subject = "OOUTHCOOP MOBILE APP";

	//Read an HTML message body from an external file, convert referenced images to embedded,
	//convert HTML into a basic plain-text alternative body
	//$mail->msgHTML(file_get_contents('contents.html'), __DIR__);

	//Replace the plain text body with one created manually
	$mail->AltBody = "This is a plain-text message body";

	$mail->Body = $sendmessage;

	//Attach an image file
	//$mail->addAttachment('images/phpmailer_mini.png');

	//send the message, check for errors
	if (!$mail->send()) {
		echo "Mailer Error: " . $mail->ErrorInfo;
	} else {
	//	echo "2";
	}


}

function resetMail($coop_no){
    
$hostname_coop = "localhost";
$database_coop = "emmaggic_coop";
$username_coop = "emmaggic_root";
$password_coop = "Oluwaseyi";
$conn = mysqli_connect($hostname_coop, $username_coop, $password_coop) or trigger_error(mysql_error(),E_USER_ERROR); 


$sql = "SELECT
tblemployees.LastName,
tblemployees.FirstName,
tblemployees.MiddleName,
tblusers_online.PlainPassword,
tblusers_online.Username,
tblemployees.EmailAddress
FROM
tblusers_online
INNER JOIN tblemployees ON tblemployees.CoopID = tblusers_online.Username WHERE Username = '".$coop_no."'";



    mysqli_select_db($conn,$database_coop);
    $Result1 = mysqli_query($conn,$sql) or die(mysqli_error($conn));
  
    $row_period = mysqli_fetch_assoc($Result1);
    $totalRows_period = mysqli_num_rows($Result1);
  

	$email_register = $row_period["EmailAddress"];
	$username = $row_period["Username"];
	$password = $row_period["PlainPassword"];
	$first_name = $row_period["FirstName"];
    $last_name = $row_period["LastName"];
    
	$sendmessage = "Dear {$first_name} {$last_name}, your login details can be found below: <br>username = {$username}<br> Password = {$password}";
	require "mail/vendor/autoload.php";

	//Create a new PHPMailer instance
	$mail = new PHPMailer();

	//Tell PHPMailer to use SMTP
	$mail->isSMTP();

	//Enable SMTP debugging
	//SMTP::DEBUG_OFF = off (for production use)
	//SMTP::DEBUG_CLIENT = client messages
	//SMTP::DEBUG_SERVER = client and server messages
	$mail->SMTPDebug = SMTP::DEBUG_OFF;

	//Set the hostname of the mail server
	$mail->Host = "mail.emmaggi.com";
	//Use `$mail->Host = gethostbyname('smtp.gmail.com');`
	//if your network does not support SMTP over IPv6,
	//though this may cause issues with TLS

	//Set the SMTP port number:
	// - 465 for SMTP with implicit TLS, a.k.a. RFC8314 SMTPS or
	// - 587 for SMTP+STARTTLS
	$mail->Port = 465;

	//Set the encryption mechanism to use:
	// - SMTPS (implicit TLS on port 465) or
	// - STARTTLS (explicit TLS on port 587)
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

	//Whether to use SMTP authentication
	$mail->SMTPAuth = true;

	//Username to use for SMTP authentication - use full email address for gmail
	$mail->Username = "vcms@emmaggi.com";

	//Password to use for SMTP authentication
	$mail->Password = "Banzoo@7980";

	//Set who the message is to be sent from
	//Note that with gmail you can only use your account address (same as `Username`)
	//or predefined aliases that you have configured within your account.
	//Do not use user-submitted addresses in here
	$mail->setFrom("no-reply@emmaggi.com", "OOUTHCOOP");

	//Set an alternative reply-to address
	//This is a good place to put user-submitted addresses
	$mail->addReplyTo("no-reply@emmaggi.com", "OOUTHCOOP");

	//Set who the message is to be sent to
	$mail->addAddress($email_register, $first_name);
	$mail ->addBCC('bankole.adesoji@gmail.com');
	
	//Set the subject line
	$mail->Subject = "OOUTHCOOP MOBILE APP";

	//Read an HTML message body from an external file, convert referenced images to embedded,
	//convert HTML into a basic plain-text alternative body
	//$mail->msgHTML(file_get_contents('contents.html'), __DIR__);

	//Replace the plain text body with one created manually
	$mail->AltBody = "This is a plain-text message body";

	$mail->Body = $sendmessage;

	//Attach an image file
	//$mail->addAttachment('images/phpmailer_mini.png');

	//send the message, check for errors
	if (!$mail->send()) {
		echo "Mailer Error: " . $mail->ErrorInfo;
	} else {
	//	echo "2";
	}


}

?>