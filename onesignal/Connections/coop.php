<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"

require_once __DIR__ . '/../../config/EnvConfig.php';

// Load database configuration from .env
$dbConfig = EnvConfig::getDatabaseConfig();
$hostname_coop = $dbConfig['host'];
$database_coop = $dbConfig['name'];
$username_coop = $dbConfig['user'];
$password_coop = $dbConfig['password'];

// PDO Connection (Replacing MySQLi)
try {
    $conn = new PDO("mysql:host=$hostname_coop;dbname=$database_coop", $username_coop, $password_coop, array(PDO::ATTR_PERSISTENT => true));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    trigger_error("Connection failed: " . $e->getMessage(), E_USER_ERROR);
}

// $coop is used as a secondary handle in some places, alias it to $conn or remove if not needed.
// For backwards compatibility during refactor, we can't fully alias because mysqli functions need mysqli object.
// BUT since we are getting rid of mysqli, we assume the files using this will be updated to use $conn (PDO).
$coop = $conn;
?>
