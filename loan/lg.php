<?php require_once('Connections/myConn.php'); ?>
<?php
$colname_Recordset1 = "-1";
if (isset($_GET['employ_id'])) {
  $colname_Recordset1 = (get_magic_quotes_gpc()) ? $_GET['employ_id'] : addslashes($_GET['employ_id']);
}
mysql_select_db($database_myConn, $myConn);
$query_Recordset1 = sprintf("SELECT LG FROM state_local_govt WHERE StateID = '%s'", $colname_Recordset1);
$Recordset1 = mysql_query($query_Recordset1, $myConn) or die(mysql_error());
$row_Recordset1 = mysql_fetch_assoc($Recordset1);
$totalRows_Recordset1 = mysql_num_rows($Recordset1);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /><LINK 
rel=stylesheet type=text/css 
href="Careers%20at%20oouth_registration_files/oouth.css">
<title>Untitled Document</title>
</head>

<body>
<label>
<select name="select" class=innerBox>
  <?php
do {  
?>
  <option value="<?php echo $row_Recordset1['LG']?>"><?php echo $row_Recordset1['LG']?></option>
  <?php
} while ($row_Recordset1 = mysql_fetch_assoc($Recordset1));
  $rows = mysql_num_rows($Recordset1);
  if($rows > 0) {
      mysql_data_seek($Recordset1, 0);
	  $row_Recordset1 = mysql_fetch_assoc($Recordset1);
  }
?>
</select>
</label>
</body>
</html>
<?php
mysql_free_result($Recordset1);
?>
