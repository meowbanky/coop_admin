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
    trigger_error("Connection failed: " . mysqli_connect_error(), E_USER_ERROR);
}
mysqli_select_db($coop, $database) or trigger_error("Database selection failed: " . mysqli_error($coop), E_USER_ERROR);

// PDO connection
$db_server = $hostname;
$db_user = $username;
$db_passwd = $password;

try {
    $pdo = new PDO("mysql:host=$db_server;dbname=$database", $db_user, $db_passwd, array(PDO::ATTR_PERSISTENT=>true));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    echo "Failed Connection: " . $e->getMessage();
}
?>
