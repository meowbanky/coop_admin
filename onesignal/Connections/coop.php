<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_coop = "localhost";
$database_coop = "emmaggic_coop";
$username_coop = "emmaggic_root";
$password_coop = "Oluwaseyi";
$conn = mysqli_connect($hostname_coop, $username_coop, $password_coop) or trigger_error(mysqli_error($conn), E_USER_ERROR);
$coop = mysqli_connect($hostname_coop, $username_coop, $password_coop) or trigger_error(mysqli_error($coop), E_USER_ERROR);
