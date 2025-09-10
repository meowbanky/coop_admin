<?php require_once('Connections/coopSky.php'); ?>
<?php
$coop_accountNo = "-1";
if (isset($_GET['id'])) {
  $coop_accountNo = $_GET['id'];
}
mysqli_select_db($coopSky,$database_coopSky);
$query_accountNo = sprintf("SELECT tblemployees.CoopID, concat(tblemployees.FirstName,' , ',tblemployees.MiddleName,' ',tblemployees.LastName) as name, tblaccountno.Bank, tblaccountno.AccountNo, tblaccountno.BankCode FROM tblemployees LEFT JOIN tblaccountno ON tblaccountno.COOPNO = tblemployees.CoopID WHERE coopid = '%s'", $coop_accountNo);
$accountNo = mysqli_query($coopSky,$query_accountNo) or die(mysqli_error($coopSky));
$row_accountNo = mysqli_fetch_assoc($accountNo);
$totalRows_accountNo = mysqli_num_rows($accountNo);


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
	
	function getBankCode(id) {		
		var strURL="bankCode.php?id="+id;
		var req = getXMLHTTP();
		
		if (req) {
			
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					// only if "OK"
					if (req.status == 200) {						
						document.getElementById('bankcode').innerHTML=req.responseText;						
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
</head>

<body>
<form id="eduEntry" name="eduEntry" method="post" action="">
<label>
<div id="BankAccountNo"><input type="text"  class="innerBox" id="txtBankAccountNo" value="<?php echo $row_accountNo['AccountNo']; ?>" size="60" readonly="true" name="txtBankAccountNo" onMouseOver="getBankCode(document.forms[0].txtBankName.value)"> 
  
  <input name="hiddenField" type="hidden" />
</div>
</label>

</form>
</body>
</html>
<?php
mysqli_free_result($accountNo);
?>
