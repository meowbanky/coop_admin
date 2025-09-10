<?php
@mysql_connect('localhost','root','oluwaseyi');
mysql_select_db("career");

$txtemail = $_POST['txtemail'];
$txtpasswd = $_POST['txtpasswd'];
$txtFname = $_POST['txtFname']; $txtMname = $_POST['txtMname']; $txtLname = $_POST['txtLname']; $gender = $_POST['gender']; $txtMStatus = $_POST['txtMStatus']; $txtDOB = $_POST['txtDOB']; $txtAddress =  $_POST['txtAddress']; $txtAddress2  =  $_POST['txtAddress2']; $txtCity = $_POST['txtCity']; $txtState = $_POST['txtState']; $txtcountry = $_POST['txtcountry']; $txtStateOfOrigin = $_POST['txtStateOfOrigin']; $txtLGA = $_POST['txtLGA']; $txtMobPhone = $_POST['txtMobPhone']; $txtNYSCCompleted = $_POST['txtNYSCCompleted']; $txtDayPhone = $_POST[txtDayPhone];


$date_array = explode('/', $txtDOB );
$day                      = $date_array[0];
$month                    = $date_array[1];
$year                     = $date_array[2];

$txtDOB = $year.'-'.$day.'-'.$month;

$query1 = "SELECT * FROM loginInfo WHERE emailAddress = '$txtemail'";
$result1 = mysql_query($query1);
$row1=mysql_fetch_array($result1);
$username_exist = $row1['emailAddress'];
if ($username_exist==$txtemail){
	header("Location:Careers at oouth_registration.php?username_exist=true");
		}else
		{
		$query = "INSERT INTO tbl_user SET emailAddress = '$txtemail', password = '$txtpasswd'";
mysql_query($query) or die(mysql_error());
		
		$query2 = "INSERT INTO tbl_personalInfo SET firstName = '$txtFname' , middleName = '$txtMname', lastName = '$txtLname', gender = '$gender',
					martialStatus = '$txtMStatus',dob = '$txtDOB',address1 = '$txtAddress',address2='$txtAddress2',city='$txtCity',
					state='$txtState',countryOfOrigin='$txtcountry',stateOfOrigin='$txtStateOfOrigin',LGA='$txtLGA',mobilePhoneNo='$txtMobPhone',
					NYSC='$txtNYSCCompleted', DayPhone='$txtDayPhone'";
mysql_query($query2) or die(mysql_error());
header("Location:Careers at oouth_registration.php");
}

?>