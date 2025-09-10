<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_alertsystem = "localhost";
$database_alertsystem = "emmaggic_coop";
$username_alertsystem = "emmaggic_root";
$password_alertsystem = "Oluwaseyi";
$alertsystem = mysqli_connect($hostname_alertsystem, $username_alertsystem, $password_alertsystem) or trigger_error(mysqli_error($alertsystem), E_USER_ERROR);
