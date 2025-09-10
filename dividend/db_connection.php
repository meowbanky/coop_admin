<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname = "localhost";
$database = "emmaggic_coop";
$username = "emmaggic_root";
$password = "Oluwaseyi";
$coop = mysqli_connect($hostname, $username, $password) or trigger_error(mysqli_error($coop),E_USER_ERROR);


$db_server = "localhost";
$db_user = 	"emmaggic_root";
$db_passwd = "Oluwaseyi";

try {
    $pdo = new PDO("mysql:host=$db_server;dbname=emmaggic_coop", $db_user, $db_passwd, array(PDO::ATTR_PERSISTENT=>true));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
    echo "Failed Connection: " . $e->getMessage();
}
?>