<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"

require_once __DIR__ . '/../config/EnvConfig.php';

// Load database configuration from .env
$dbConfig = EnvConfig::getDatabaseConfig();
$hostname = $dbConfig['host'];
$database = $dbConfig['name'];
$username = $dbConfig['user'];
$password = $dbConfig['password'];

// MySQLi connection
$coop = mysqli_connect($hostname, $username, $password);
if (!$coop) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_select_db($coop, $database) or die("Database selection failed: " . mysqli_error($coop));

// Set charset to utf8
mysqli_set_charset($coop, "utf8");

// PDO connection
try {
    $conn = new PDO("mysql:host=$hostname;dbname=$database", $username, $password, array(PDO::ATTR_PERSISTENT=>true));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    echo "Failed Connection: " . $e->getMessage();
}
?>
