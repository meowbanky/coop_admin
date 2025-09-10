<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_conn_career = "localhost";
$database_conn_career = "career";
$username_conn_career = "root";
$password_conn_career = "oluwaseyi";
$conn_career = mysql_pconnect($hostname_conn_career, $username_conn_career, $password_conn_career) or trigger_error(mysql_error(),E_USER_ERROR); 
?>