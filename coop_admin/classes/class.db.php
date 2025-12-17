<?php

require_once __DIR__ . '/../config/EnvConfig.php';

// Load database configuration from .env
$dbConfig = EnvConfig::getDatabaseConfig();
$db_server = $dbConfig['host'];
$db_user = $dbConfig['user'];
$db_passwd = $dbConfig['password'];
$db_name = $dbConfig['name'];

try {
	$conn = new PDO("mysql:host=$db_server;dbname=$db_name", $db_user, $db_passwd, array(PDO::ATTR_PERSISTENT=>true));
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
	error_log("Database Connection Error: " . $e->getMessage());
	echo "Failed Connection: " . $e->getMessage();
}

?>