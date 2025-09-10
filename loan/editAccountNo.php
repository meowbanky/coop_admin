<?php require_once('Connections/coopSky.php'); ?>
<?php
if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{

  $theValue = function_exists("mysql_real_escape_string") ? mysqli_real_escape_string($theValue) : mysqli_escape_string($theValue);

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

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
	mysqli_select_db($coopSky,$database_coopSky);
$query_Checkcoopid = sprintf("SELECT * FROM tblaccountno WHERE tblaccountno.COOPNO = %s", GetSQLValueString($_POST['txtCoopid'], "text"));
$Checkcoopid = mysqli_query($coopSky,$query_Checkcoopid) or die(mysqli_error($coopSky));
$row_Checkcoopid = mysqli_fetch_assoc($Checkcoopid);
$totalRows_Checkcoopid = mysqli_num_rows($Checkcoopid);

if ($totalRows_Checkcoopid > 0) {
	
	
  $updateSQL = sprintf("UPDATE tblaccountno SET Bank=%s, AccountNo=%s WHERE COOPNO=%s",
                       GetSQLValueString($_POST['txtBank'], "text"),
                       GetSQLValueString($_POST['txtAccountNo'], "text"),
                       GetSQLValueString($_POST['txtCoopid'], "text"));
}elseif ($totalRows_Checkcoopid == 0){
	$updateSQL = sprintf("INSERT INTO tblaccountno (Bank, AccountNo, coopno) VALUES(%s,%s,%s)",
                       GetSQLValueString($_POST['txtBank'], "text"),
                       GetSQLValueString($_POST['txtAccountNo'], "text"),
					   GetSQLValueString($_POST['txtCoopid'], "text"));
}

  mysqli_select_db($coopSky,$database_coopSky);
  $Result1 = mysqli_query($coopSky,$updateSQL) or die(mysqli_error($coopSky));
}
?>
<?php require_once('Connections/coopSky.php'); ?>
<?php
$col_coopID = "-1";
if (isset($_GET['coopid'])) {
  $col_coopID = $_GET['coopid'];
}
mysqli_select_db($coopSky,$database_coopSky);
$query_coopID = sprintf("SELECT concat (CoopID, ' - ', lastname, ' , ', firstname, '  ', middlename) as coopname, coopid, IFNULL(Bank,-1) AS Bank, IFNULL(AccountNo,-1) AS AccountNo FROM tblaccountno RIGHT JOIN tblemployees ON tblemployees.CoopID = tblaccountno.COOPNO 
 WHERE coopid = %s", GetSQLValueString($col_coopID, "text"));
$coopID = mysqli_query($coopSky,$query_coopID) or die(mysqli_error($coopSky));
$row_coopID = mysqli_fetch_assoc($coopID);
$totalRows_coopID = mysqli_num_rows($coopID);

mysqli_select_db($coopSky,$database_coopSky);
$query_Bank = "SELECT tblbankcode.bank, tblbankcode.bankcode  FROM tblbankcode ";
$Bank = mysqli_query($coopSky,$query_Bank) or die(mysqli_error($coopSky));
$row_Bank = mysqli_fetch_assoc($Bank);
$totalRows_Bank = mysqli_num_rows($Bank);


//session_start();
//if(!isset($_SESSION['UserID'])){
//	header("Location:index.php"); 
//}
?>
<?php

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}


if ((isset($_POST["CreateBatch"])) && ($_POST["CreateBatch"] == "Create Batch")) {
  $insertSQL = sprintf("INSERT INTO tbl_batch (batch) VALUES (%s)",
                       GetSQLValueString($_POST['batch'], "text"));

  mysqli_select_db($coopSky,$database_coopSky);
  $Result1 = mysqli_query($coopSky,$insertSQL) or die(mysqli_error($coopSky));
  
  $insertGoTo = "batchcreate.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<!-- saved from url=(0061)file://C:\Users\emmaggi\Desktop\Careers at MTN Nigeria_cv.htm -->
<!-- saved from url=(0037)http://careers.mtnonline.com/mycv.asp --><HTML><HEAD><TITLE>Edit Account Info</TITLE>
<META content="text/html; charset=windows-1252" http-equiv=Content-Type><!--Fireworks MX 2004 Dreamweaver MX 2004 target.  Created Sat Dec 04 17:23:24 GMT+0100 2004--><LINK 
rel=stylesheet type=text/css 
href="mycv_files/oouth.css">
<SCRIPT language=JavaScript type=text/javascript 
src="mycv_files/general.js"></SCRIPT>

<script language="javascript" type="text/javascript">

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
	
	function getBatch() {		
		
		var strURL="generateBatch.php";
		var req = getXMLHTTP();
		
		if (req) {
			
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					// only if "OK"
					if (req.status == 200) {						
						document.getElementById('batch').innerHTML=req.responseText;						
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
	
	function getAccountNo(id) {		
		var strURL="accountNo.php?id="+id;
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

function MM_validateForm() { //v4.0
  var i,p,q,nm,test,num,min,max,errors='',args=MM_validateForm.arguments;
  for (i=0; i<(args.length-2); i+=3) { test=args[i+2]; val=MM_findObj(args[i]);
    if (val) { nm=val.name; if ((val=val.value)!="") {
      if (test.indexOf('isEmail')!=-1) { p=val.indexOf('@');
        if (p<1 || p==(val.length-1)) errors+='- '+nm+' must contain an e-mail address.\n';
      } else if (test!='R') { num = parseFloat(val);
        if (isNaN(val)) errors+='- '+nm+' must contain a number.\n';
        if (test.indexOf('inRange') != -1) { p=test.indexOf(':');
          min=test.substring(8,p); max=test.substring(p+1);
          if (num<min || max<num) errors+='- '+nm+' must contain a number between '+min+' and '+max+'.\n';
    } } } else if (test.charAt(0) == 'R') errors += '- '+nm+' is required.\n'; }
  } if (errors) alert('The following error(s) occurred:\n'+errors);
  document.MM_returnValue = (errors == '');
}
//-->
</script>

<script>
function clearBox()
{
document.forms[0].txtBank.value = "Bank"
document.forms[0].txtAccountNo.value = ""
}
</script>

<META name=GENERATOR content="MSHTML 8.00.7600.16385"></HEAD>
<BODY>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=500 bgcolor="#FFFFFF">
        <TBODY>
        <TR>
          <TD width=8><IMG src="mycv_files/spacer.gif" width=1 
            height=1></TD>
          <TD class=breadcrumbs height=20 vAlign=bottom colSpan=2>&nbsp;</TD>
          <TD width=10><IMG src="mycv_files/spacer.gif" width=1 
            height=1></TD></TR>
        <TR>
          <TD height="369" vAlign=top class=Content>&nbsp;</TD>
          <TD width="501" vAlign=top class=Content id=top><IMG 
            src="mycv_files/mycv.gif" width=350 height=30> 
            <HR align=left color=#cccccc SIZE=1 width=500>
            <TABLE border=0 cellSpacing=0 cellPadding=0 width=444>
              <TBODY>
                <TR>
                  <TD width="444" vAlign=top class=toplinks2>
                    <DIV align=justify>
                      <TABLE class=Content border=0 cellSpacing=0 cellPadding=4 
                  width="100%">
                        <TBODY>
                          <TR>
                            <TD vAlign=top>
                              <?php if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) { echo "<table class=\"errorBox\" width=\"500\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
  <tbody><tr>
    <td>Bank Account No Update Successful</td>
  </tr>
</tbody></table>" ;} ?>
                              <FIELDSET>
                                <LEGEND class=contentHeader1>Edt Account No</LEGEND>
                                <form action="<?php echo $editFormAction; ?>" method="POST" name="form1" onSubmit="MM_validateForm('txtAccountNo','','RisNum');return document.MM_returnValue">
                                <table width="97%" align="center" cellpadding="4" cellspacing="0">
                                  <tr valign="top" align="left">
                                      <td class="greyBgd" valign="middle" width="31%" align="right" height="35">Coop id </td>
                                      <td class="greyBgd" valign="middle" width="86%" align="left">
                                        <select name="txtCoopid" class="innerBox" id="txtCoopid" onChange=clearBox(); >
                                          <?php
do {  
?>
                                          <option value="<?php echo $row_coopID['coopid']?>"><?php echo $row_coopID['coopname']?></option>
                                          <?php
} while ($row_coopID = mysqli_fetch_assoc($coopID));
  $rows = mysqli_num_rows($coopID);
  if($rows > 0) {
      mysqli_data_seek($coopID, 0);
	  $row_coopID = mysqli_fetch_assoc($coopID);
  }
?>
                                          </select>
                                        <input name="coopid" type="hidden" value="<?php echo $row_coopID['coopid']; ?>"></td>
                                      </tr>
                                    <tr valign="top" align="left">
                                      <td class="greyBgd" valign="middle" align="right" height="35">Bank</td>
                                      <td class="greyBgd" valign="middle" align="left"><label>
                                        <select name="txtBank" class="innerBox" id="txtBank">
                                          <option value="-1" <?php if (!(strcmp(-1, $row_coopID['Bank']))) {echo "selected=\"selected\"";} ?>>Bank</option>
                                          <?php
do {  
?>
                                          <option value="<?php echo $row_Bank['bank']?>"<?php if (!(strcmp($row_Bank['bank'], $row_coopID['Bank']))) {echo "selected=\"selected\"";} ?>><?php echo $row_Bank['bank']?></option>
                                          <?php
} while ($row_Bank = mysqli_fetch_assoc($Bank));
  $rows = mysqli_num_rows($Bank);
  if($rows > 0) {
      mysqli_data_seek($Bank, 0);
	  $row_Bank = mysqli_fetch_assoc($Bank);
  }
?>
                                          </select>
                                        </label></td>
                                      </tr>
                                    <tr valign="top" align="left">
                                      <td class="greyBgd" valign="middle" align="right" height="35">Account No.: </td>
                                      <td class="greyBgd" valign="middle" align="left"><label>
                                        <input name="txtAccountNo" type="text" class="innerBox" id="txtAccountNo" value="<?php echo $row_coopID['AccountNo']; ?>" maxlength="10">
                                        </label></td>
                                      </tr>
                                    <tr valign="top" align="left">
                                      <td height="35" colspan="2" align="right" valign="middle" class="greyBgd"><div align="center">
                                        <input name="CreateBatch" type="submit" class="formbutton" id="CreateBatch" value="Insert Account" onClick="submith()" readonly>&nbsp;
                                           <input name="close" type="button" class="formbutton" id="close" value="close" onClick="parent.hide(); parent.location.refresh(true)" readonly>
                                      </div></p></td>
                                      </tr>
                                    </table>
                                  <input type="hidden" name="MM_insert" value="form1">
                                  <SCRIPT language=JavaScript type=text/JavaScript>
<!--
function GP_popupConfirmMsg(msg) { //v1.0
  document.MM_returnValue = confirm(msg);
}
//-->
                        </SCRIPT>
                                  <input type="hidden" name="MM_update" value="form1">
                                  </form>
                                </FIELDSET>
                              <SCRIPT language=JavaScript type=text/JavaScript>
<!--
function GP_popupConfirmMsg(msg) { //v1.0
  document.MM_returnValue = confirm(msg);
}
//-->
</SCRIPT>
                              <FILDSET><LEGEND class=contentHeader1></LEGEND>
                              </FILDSET>
                          <P><BR></P></TD></TR></TBODY></TABLE>
      </DIV></TD></TR></TBODY></TABLE><BR><BR><BR></TD></TR></TBODY></TABLE></TD></TR>
  </BODY></HTML>
<?php
mysqli_free_result($coopID);

mysqli_free_result($Bank);


?>
