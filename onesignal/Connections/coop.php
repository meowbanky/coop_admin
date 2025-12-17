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

// MySQLi connections
$conn = mysqli_connect($hostname_coop, $username_coop, $password_coop);
if (!$conn) {
    trigger_error("Connection failed: " . mysqli_connect_error(), E_USER_ERROR);
}

$coop = mysqli_connect($hostname_coop, $username_coop, $password_coop);
if (!$coop) {
    trigger_error("Connection failed: " . mysqli_connect_error(), E_USER_ERROR);
}

mysqli_select_db($coop, $database_coop) or trigger_error("Database selection failed: " . mysqli_error($coop), E_USER_ERROR);
?>
