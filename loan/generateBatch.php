<?php require_once('Connections/coopSky.php'); ?>
<?php

mysqli_select_db($coopSky,$database_coopSky);
$query_Recordset1 = "select DATE_FORMAT(CURRENT_TIMESTAMP(),'%D%M%y_%h:%i%p') as batch";
$Recordset1 = mysqli_query($coopSky,$query_Recordset1) or die(mysqli_error($coopSky));
$row_Recordset1 = mysqli_fetch_assoc($Recordset1);
$totalRows_Recordset1 = mysqli_num_rows($Recordset1);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Untitled Document</title>
</head>

<body>
<label>
<div id="batch"><input name="batch" type="text" class="innerBox" id="batch" value="<?php echo $row_Recordset1['batch']; ?>" size="60" readonly="true" > 
  <input type="hidden" name="hiddenField" / id="hiddenField">
</div>

</label>
</body>
</html>
<?php
mysqli_free_result($Recordset1);
?>