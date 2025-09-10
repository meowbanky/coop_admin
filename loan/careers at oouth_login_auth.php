<?php
@mysql_connect('localhost','root','oluwaseyi');
mysql_select_db("career");

$loginUsername=$_POST['uname'];
$password=$_POST['passwd'];

$query1 = "SELECT * FROM loginInfo WHERE emailAddress = '$loginUsername' and password = '$password' ";
$result1 = mysql_query($query1);
$row1=mysql_fetch_array($result1);
$username_exist = $row1['emailAddress'];
$UserID = $row1['userid'];

if ($username_exist==$loginUsername){

$query2 = "SELECT FirstName ,UserID FROM tbl_personalInfo WHERE UserID = '$UserID'" ;
$result2 = mysql_query($query2);
$row2=mysql_fetch_array($result2);
$firstName = $row2['FirstName'];
$UserID = $row2['UserID'];
session_start();
session_register('FirstName');
session_register('UserID');
$_SESSION['FirstName'] = $firstName;
$_SESSION['UserID'] = $UserID;
	header("Location:welcome.php");
		}else
		{
	header("Location:loginerror.php"); 
}

?>