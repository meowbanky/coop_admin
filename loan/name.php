<?php require_once('Connections/coopSky.php'); ?>
<?php
$coop_name = "-1";
if (isset($_GET['id'])) {
  $coop_name = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}
mysql_select_db($database_coopSky, $coopSky);
$query_name = sprintf("SELECT concat( tblemployees.FirstName,' ', tblemployees.MiddleName,'  ', tblemployees.LastName) as 'name', MobileNumber,coopid FROM tblemployees WHERE coopid = '%s'", $coop_name);
$name = mysql_query($query_name, $coopSky) or die(mysql_error());
$row_name = mysql_fetch_assoc($name);
$totalRows_name = mysql_num_rows($name);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Untitled Document</title>
<script language="javascript" type="text/javascript">
// Roshan's Ajax dropdown code with php
// This notice must stay intact for legal use
// Copyright reserved to Roshan Bhattarai - nepaliboy007@yahoo.com
// If you have any problem contact me at http://roshanbh.com.np
function getXMLHTTP() { //fuction to return the xml http object
		var xmlhttp=false;	
		try{
			xmlhttp=new XMLHttpRequest();
		}
		catch(e)	{		
			try{			
				xmlhttp= new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(e){
				try{
				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
				}
				catch(e1){
					xmlhttp=false;
				}
			}
		}
		 	
		return xmlhttp;
    }
	
	function getName(coopid) {		
		
		var strURL="accountNo1.php?id="+coopid;
		var req = getXMLHTTP();
		
		if (req) {
			
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					// only if "OK"
					if (req.status == 200) {						
						document.getElementById('BankAccountNo').innerHTML=req.responseText;						
					} else {
						alert("There was a problem while using XMLHTTP:\n" + req.statusText);
					}
				}				
			}			
			req.open("GET", strURL, true);
			req.send(null);
		}		
	}
	function getBankName(coopid) {		
		
		var strURL="bankName.php?id="+coopid;
		var req = getXMLHTTP();
		
		if (req) {
			
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					// only if "OK"
					if (req.status == 200) {						
						document.getElementById('BankName').innerHTML=req.responseText;						
					} else {
						alert("There was a problem while using XMLHTTP:\n" + req.statusText);
					}
				}				
			}			
			req.open("GET", strURL, true);
			req.send(null);
		}		
	}
	
	function getAccountNo1(id) {		
		var strURL="accountNo.php?id="+id;
		var req = getXMLHTTP();
		
		if (req) {
			
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					// only if "OK"
					if (req.status == 200) {						
						document.getElementById('txtBankAccountNo').innerHTML=req.responseText;						
					} else {
						alert("There was a problem while using XMLHTTP:\n" + req.statusText);
					}
				}				
			}			
			req.open("GET", strURL, true);
			req.send(null);
		}
				
	}
</script>
<script>
function clearBox()
{
document.forms[0].txtBankName.value = ""

}
</script>
</head>

<body>
<label>
<form id="eduEntry" name="eduEntry" method="post" action="">

<div id="txtCoopName">
  
  <span class="greyBgd">
  <select name="CoopName" class="innerBox" onclick="getBankName(document.getElementById('name_coopid').value); clearbox()">
    <?php
do {  
?>
    <option value="<?php echo $row_name['name']?>"<?php if (!(strcmp($row_name['name'], $row_name['coopid']))) {echo "selected=\"selected\"";} ?>><?php echo $row_name['name']?></option>
    <?php
} while ($row_name = mysql_fetch_assoc($name));
  $rows = mysql_num_rows($name);
  if($rows > 0) {
      mysql_data_seek($name, 0);
	  $row_name = mysql_fetch_assoc($name);
  }
?>
  </select>
  
  <input name="name_coopid" type="hidden" id="name_coopid" value="<?php echo $row_name['coopid']; ?>" />
  
</form>
  
</body>
</html>
<?php
mysql_free_result($name);
?>
