<?php
//Start session
require_once('Connections/coop.php');
session_start();

//Array to store validation errors
$errmsg_arr = array();

//Validation error flag
$errflag = false;

$errors         = array();      // array to hold validation errors
$data           = array();      // array to pass back data

function clean($str)
{
	global $coop;
	$str = @trim($str);

	return mysqli_real_escape_string($coop, $str);
}

//Sanitize the POST values
$login = clean($_POST['username']);
$password = clean($_POST['password']);
//$location = clean($_POST['location']);

//Input Validations
if ($login == '') {
	$errmsg_arr[] = 'Username missing';
	$errflag = true;
}
if ($password == '') {
	$errmsg_arr[] = 'Password missing';
	$errflag = true;
}

//If there are input validations, redirect back to the login form
if ($errflag) {
	$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
	session_write_close();
	header("location: index.php");
	exit();
}



//Create query - Get user data first, then verify password
mysqli_select_db($coop, $database);
$qry = "SELECT tblusers.user_id,AdminType,tblusers.CompleteName,tblusers.Username,tblusers.UPassword FROM tblusers WHERE Username = '$login' AND Status = 'Active'";
$result = mysqli_query($coop, $qry) or die(mysqli_error($coop));
$row_qry = mysqli_fetch_assoc($result);
$totalRows_result = mysqli_num_rows($result);



// $row1=mysql_fetch_array($result);


//Check whether the query was successful or not
if ($result) {

	if (mysqli_num_rows($result) > 0) {
		// User found, now verify password
		$stored_password = $row_qry['UPassword'];
		$password_valid = false;
		
		// Check if password is legacy bcrypt hash (starts with *)
		if (strpos($stored_password, '*') === 0) {
			// For legacy bcrypt hashes starting with *, use crypt() function
			$password_valid = (crypt($password, $stored_password) === $stored_password);
		} elseif (strpos($stored_password, '$2y$') === 0 || 
		          strpos($stored_password, '$2a$') === 0 || 
		          strpos($stored_password, '$2b$') === 0) {
			// Standard bcrypt with $2y$, $2a$, $2b$
			$password_valid = password_verify($password, $stored_password);
		} else {
			// Plain text password comparison (fallback)
			$password_valid = ($password === $stored_password);
		}
		
		if ($password_valid) {
			//Login Successful
			//echo "completed";

			session_regenerate_id();

			$_SESSION['SESS_MEMBER_ID'] = $row_qry['user_id'];
			$_SESSION['SESS_FIRST_NAME'] = $row_qry['CompleteName'];
			$_SESSION['SESS_LAST_NAME'] = $row_qry['CompleteName'];
			$_SESSION['role'] = $row_qry['AdminType'];
			$_SESSION['emptrack'] = 0;
			$_SESSION['empDataTrack'] = 'next';
			
			// Also set the new session variables for the loan system
			$_SESSION['user_id'] = $row_qry['user_id'];
			$_SESSION['username'] = $row_qry['Username'];
			$_SESSION['complete_name'] = $row_qry['CompleteName'];
			$_SESSION['admin_type'] = $row_qry['AdminType'];

			$data['success'] = 'true';
			$data['message'] = 'Successfully Login';
		} else {
			// Password verification failed
			$data['success'] = 'false';
			$data['message'] = 'Invalid Password';
		}
	} else {
		// User not found
		$data['success'] = 'false';
		$data['message'] = 'Invalid Username';
	}
} else {
	die("Query failed");
}
echo json_encode($data);
//echo "completed";