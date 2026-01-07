<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"

require_once __DIR__ . '/../config/EnvConfig.php';

// Load info database configuration from .env
// Use DB_INFO_* variables if available, otherwise fall back to main DB config
$hostname_info = EnvConfig::get('DB_INFO_HOST', EnvConfig::get('DB_HOST', 'localhost'));
$database_info = EnvConfig::get('DB_INFO_NAME', '');
$username_info = EnvConfig::get('DB_INFO_USER', EnvConfig::get('DB_USER', ''));
$password_info = EnvConfig::get('DB_INFO_PASSWORD', EnvConfig::get('DB_PASSWORD', ''));

if (empty($database_info) || empty($username_info)) {
    die("Info database configuration not found in .env file. Please set DB_INFO_NAME, DB_INFO_USER, and DB_INFO_PASSWORD.");
}

// MySQLi connection - REMOVED during PDO migration
// $info = mysqli_connect($hostname_info, $username_info, $password_info);
// if (!$info) {
//     die("Connection failed: " . mysqli_connect_error());
// }
// mysqli_select_db($info, $database_info) or die("Database selection failed: " . mysqli_error($info));

// PDO connection
try {
    $conn = new PDO("mysql:host=$hostname_info;dbname=$database_info", $username_info, $password_info, array(PDO::ATTR_PERSISTENT=>true));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Info Database Connection Error: " . $e->getMessage());
    echo "Failed Connection: " . $e->getMessage();
}
?>