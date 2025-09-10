<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname = "localhost";
$database = "emmaggic_coop";
$username = "emmaggic_root";
$password = "Oluwaseyi";
$coop = mysqli_connect($hostname, $username, $password) or trigger_error(mysqli_error($coop),E_USER_ERROR);
mysqli_select_db($coop, $database) or trigger_error(mysqli_error($coop),E_USER_ERROR);

// Set charset to utf8
mysqli_set_charset($coop, "utf8"); 


	$db_server = "localhost";
	$db_user = 	"emmaggic_root";
	$db_passwd = "Oluwaseyi";

	try {
			$conn = new PDO("mysql:host=$db_server;dbname=emmaggic_coop", $db_user, $db_passwd, array(PDO::ATTR_PERSISTENT=>true));
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
	catch(PDOException $e)
		{
			echo "Failed Connection: " . $e->getMessage();
		}
?>