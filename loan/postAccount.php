<?php require_once('Connections/coopSky.php'); ?>
<?php
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
{

	switch ($theType) {
		case "text":
			$theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
			break;
		case "long":
		case "int":
			$theValue = ($theValue != "") ? intval($theValue) : "NULL";
			break;
		case "double":
			$theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
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
 mysqli_select_db($coopSky, $database_coopSky);
if ((isset($_GET['batchid'])) && ($_GET['batchid'] != "")) {
    
    $deleteSql = sprintf(
		"DELETE FROM tbl_loanapproval WHERE batch = %s",
		GetSQLValueString($_GET['batchid'], "text")
	);
    
   $Result = mysqli_query($coopSky, $deleteSql) or die(mysqli_error($coopSky));

 
	$insert = sprintf(
		"INSERT INTO tbl_loanapproval (coopID, approvalDate, LoanAmount, loanapproval_id,batch) SELECT  BeneficiaryCode,NOW(), Amount,PaymentRefID,Batch FROM excel WHERE Batch =%s",
		GetSQLValueString($_GET['batchid'], "text")
	);

	mysqli_select_db($coopSky, $database_coopSky);
	$Result1 = mysqli_query($coopSky, $insert) or die(mysqli_error($coopSky));
    
  
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
			var xmlhttp = false;
			try {
				xmlhttp = new XMLHttpRequest();
			} catch (e) {
				try {
					xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {
					try {
						xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
					} catch (e1) {
						xmlhttp = false;
					}
				}
			}

			return xmlhttp;
		}

		function getName(coopid) {

			var strURL = "BankCode1.php?id=" + coopid;
			var req = getXMLHTTP();

			if (req) {

				req.onreadystatechange = function() {
					if (req.readyState == 4) {
						// only if "OK"
						if (req.status == 200) {
							document.getElementById('BankAccountNo').innerHTML = req.responseText;
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

			var strURL = "bankName.php?id=" + coopid;
			var req = getXMLHTTP();

			if (req) {

				req.onreadystatechange = function() {
					if (req.readyState == 4) {
						// only if "OK"
						if (req.status == 200) {
							document.getElementById('BankName').innerHTML = req.responseText;
						} else {
							alert("There was a problem while using XMLHTTP:\n" + req.statusText);
						}
					}
				}
				req.open("GET", strURL, true);
				req.send(null);
			}
		}

		function getAccountNo(id) {
			var strURL = "BankCode.php?id=" + id;
			var req = getXMLHTTP();

			if (req) {

				req.onreadystatechange = function() {
					if (req.readyState == 4) {
						// only if "OK"
						if (req.status == 200) {
							document.getElementById('BankAccountNo').innerHTML = req.responseText;
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
	<?php if ($Result1) {
		echo 'Account Posting of batch:' . $_GET['batchid'] . ' Successful';
	} ?>

</body>

</html>