<?php require_once('Connections/paymaster.php'); ?>
<?php
$errors         = array();      // array to hold validation errors
$response           = array();      // array to pass back data

if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) {
    $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
  }

  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}
}

$col_Recordset1 = "-1";
if (isset($_POST['emp_no'])) {
  $col_Recordset1 = $_POST['emp_no'];
}
mysql_select_db($database_salary, $salary);
$query_Recordset1 = sprintf("SELECT personal_info.staff_id  FROM personal_info WHERE staff_id = %s", GetSQLValueString($col_Recordset1, "text"));


$Recordset1 = mysql_query($query_Recordset1, $salary) or die(mysql_error());
$row_Recordset1 = mysql_fetch_assoc($Recordset1);
$totalRows_Recordset1 = mysql_num_rows($Recordset1);


if ($totalRows_Recordset1 > 0){
//	$data[] = true;
        // echo json_encode($data);
 //echo '"Employee No. already exists."';
   // echo (json_encode(true));
    echo "false";
}else {
    echo "true";
    
}

 ?>

