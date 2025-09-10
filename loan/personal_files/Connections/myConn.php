<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_myConn = "localhost";
$database_myConn = "career";
$username_myConn = "root";
$password_myConn = "oluwaseyi";
$myConn = mysql_pconnect($hostname_myConn, $username_myConn, $password_myConn) or trigger_error(mysql_error(),E_USER_ERROR); 
?>