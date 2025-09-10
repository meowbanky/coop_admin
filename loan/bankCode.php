<?php require_once('Connections/coopSky.php'); ?>
<?php
$coop_BankCode = "-1";
if (isset($_GET['id'])) {
	$coop_BankCode = $_GET['id'];
}
mysqli_select_db($coopSky, $database_coopSky);
$query_BankCode = sprintf("SELECT tblbankcode.bank, tblbankcode.bankcode FROM tblbankcode WHERE tblbankcode.bank = '%s'", $coop_BankCode);
$BankCode = mysqli_query($coopSky, $query_BankCode) or die(mysqli_error($coopSky));
$row_BankCode = mysqli_fetch_assoc($BankCode);
$totalRows_BankCode = mysqli_num_rows($BankCode);
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

	<label>
		<form id="eduEntry" name="eduEntry" method="post" action="">

			<div id="bankcode"><input name='txtbankcode' type="text" class="innerBox" id="txtbankcode" onMouseOver="javascript:fo2()" value="<?php if ($totalRows_BankCode  > 0) {
																																					echo $row_BankCode['bankcode'];
																																				} ?>" size="60" readonly="readonly">
			</div>
		</form>
	</label>
</body>

</html>
<?php
mysqli_free_result($BankCode);
?>