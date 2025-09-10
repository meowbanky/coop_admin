<?php

	$db_server = 	"localhost";
	$db_user = 		"emmaggic_root";
	$db_passwd = 	"Oluwaseyi";

	try {
			$conn = new PDO("mysql:host=$db_server;dbname=emmaggic_coop", $db_user, $db_passwd, array(PDO::ATTR_PERSISTENT=>true));
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
	catch(PDOException $e)
		{
			echo "Failed Connection: " . $e->getMessage();
		}

?>
