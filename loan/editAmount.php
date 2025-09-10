<?php require_once('Connections/coopSky.php'); ?>
<?php
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

function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

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
?>
<?php
$col_coopID = "-1";
if (isset($_GET['coopid'])) {
  $col_coopID = $_GET['coopid'];
}
mysqli_select_db($coopSky,$database_coopSky);
$query_coopID = sprintf("SELECT concat (CoopID, ' - ', lastname, ' , ', firstname, '  ', middlename) as coopname, coopid, Bank, AccountNo FROM tblaccountno INNER JOIN tblemployees ON tblemployees.CoopID = tblaccountno.COOPNO WHERE coopid = %s", GetSQLValueString($col_coopID, "text"));
$coopID = mysqli_query($coopSky,$query_coopID) or die(mysqli_error($coopSky));
$row_coopID = mysqli_fetch_assoc($coopID);
$totalRows_coopID = mysqli_num_rows($coopID);

mysqli_select_db($coopSky,$database_coopSky);
$query_Bank = "SELECT tblbankcode.bank, tblbankcode.bankcode  FROM tblbankcode ";
$Bank = mysqli_query($coopSky,$query_Bank) or die(mysqli_error($coopSky));
$row_Bank = mysqli_fetch_assoc($Bank);
$totalRows_Bank = mysqli_num_rows($Bank);

$col_coopid_edit_query = "-1";
$col_batch_edit_query = "-1";
if ((isset($_GET['coopid'])) and (isset($_GET['batch']))) {
  $col_coopid_edit_query = $_GET['coopid'];
  $col_batch_edit_query = $_GET['batch'];
}

$col_coopid_edit_query = "-1";
if (isset($_GET['coopid'])) {
  $col_coopid_edit_query = $_GET['coopid'];
}
$col_batch_edit_query = "-1";
if (isset($_GET['batch'])) {
  $col_batch_edit_query = $_GET['batch'];
}
mysqli_select_db($coopSky,$database_coopSky);
$query_edit_query = sprintf("SELECT excel.PaymentRefID, excel.BeneficiaryCode, excel.BeneficiaryName, excel.AccountNumber, excel.AccountType, excel.CBNCode, excel.Bank, excel.Narration, excel.Amount, excel.Batch FROM excel WHERE excel.BeneficiaryCode = %s and Batch = %s", GetSQLValueString($col_coopid_edit_query, "text"),GetSQLValueString($col_batch_edit_query, "text"));
$edit_query = mysqli_query($coopSky,$query_edit_query) or die(mysqli_error($coopSky));
$row_edit_query = mysqli_fetch_assoc($edit_query);
$totalRows_edit_query = mysqli_num_rows($edit_query);

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

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form")) {
  $updateSQL = sprintf("UPDATE excel SET BeneficiaryName=%s, AccountNumber=%s, CBNCode=%s, Bank=%s, Narration=%s, Amount=%s, Batch=%s WHERE BeneficiaryCode=%s",
                       GetSQLValueString($_POST['CoopName'], "text"),
                       GetSQLValueString($_POST['txtBankAccountNo'], "text"),
                       GetSQLValueString($_POST['txtbankcode'], "text"),
                       GetSQLValueString($_POST['txtBankName'], "text"),
                       GetSQLValueString($_POST['txNarration'], "text"),
                       GetSQLValueString($_POST['txtAmount'], "double"),
                       GetSQLValueString($_POST['batch'], "text"),
                       GetSQLValueString($_POST['txtCoopid'], "text"));

  mysqli_select_db($coopSky,$database_coopSky);
  $Result1 = mysqli_query($coopSky,$updateSQL) or die(mysqli_error($coopSky));
  
  
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
<!--
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
         <FORM method="POST" action="<?php echo $editFormAction; ?>" name="form">  <TABLE border=0 cellSpacing=0 cellPadding=0 width=444>
              <TBODY>
                <TR>
                  <TD width="444" vAlign=top class=toplinks2>
                    <DIV align=justify>
                      <table width="97%" align="center" cellpadding="4" cellspacing="0">
                        <tbody>
                          <tr valign="top" align="left">
                            <td colspan="3" height="1"><img src="education_files/spacer.gif" alt="a" width="1" height="1"></td>
                          </tr>
                          <tr valign="top" align="left">
                            <td class="greyBgd" valign="middle" width="31%" align="right" height="35">Coop id </td>
                            <td class="greyBgd" valign="middle" width="69%" align="left"><select name="txtCoopid" class="innerBox" id="txtCoopid" title="<?php echo $row_edit_query['BeneficiaryCode']; ?>" onChange="clearBox(); getName(this.value)" >
                              <?php
do {  
?>
                              <option value="<?php echo $row_edit_query['BeneficiaryCode']?>"><?php echo $row_edit_query['BeneficiaryCode']?></option>
                              <?php
} while ($row_edit_query = mysqli_fetch_assoc($edit_query));
  $rows = mysqli_num_rows($edit_query);
  if($rows > 0) {
      mysqli_data_seek($edit_query, 0);
	  $row_edit_query = mysqli_fetch_assoc($edit_query);
  }
?>
                            </select>
                              *
                              <input type="hidden" name="coopid">
                              <br></td>
                            <td width="69%" rowspan="8" align="left" valign="top" class="greyBgd"><br>
                              <div id="hide2"></div></td>
                          </tr>
                          <tr valign="top" align="left">
                            <td class="greyBgd" valign="middle" width="31%" align="right" height="35">Name:</td>
                            <td class="greyBgd" valign="middle" width="69%" align="left"><div id="txtCoopName">
                              <select name="CoopName" id="CoopName" title="<?php echo $row_edit_query['BeneficiaryName']; ?>">
                                <?php
do {  
?>
                                <option value="<?php echo $row_edit_query['BeneficiaryName']?>"><?php echo $row_edit_query['BeneficiaryName']?></option>
                                <?php
} while ($row_edit_query = mysqli_fetch_assoc($edit_query));
  $rows = mysqli_num_rows($edit_query);
  if($rows > 0) {
      mysqli_data_seek($edit_query, 0);
	  $row_edit_query = mysqli_fetch_assoc($edit_query);
  }
?>
                              </select>
                            </div></td>
                          </tr>
                          <tr valign="top" align="left">
                            <td class="greyBgd" valign="middle" width="31%" align="right" height="35">Bank 
                              
                              Name:</td>
                            <td class="greyBgd" valign="middle" width="69%" align="left"><div id="BankName">
                              <input name="txtBankName" type="text" class="innerBox" id="txtBankName" value="<?php echo $row_edit_query['Bank']; ?>" size="60" readonly>
                              * </div></td>
                          </tr>
                          <tr valign="top" align="left">
                            <td class="greyBgd" valign="middle" width="31%" align="right" height="28">Account No.  :</td>
                            <td class="greyBgd" valign="middle" width="69%" align="left"><label></label>
                              <div id="BankAccountNo">
                                <input name="txtBankAccountNo" type="text" class="innerBox" id="txtBankAccountNo" value="<?php echo $row_edit_query['AccountNumber']; ?>" size="60" readonly >
                                *</div></td>
                          </tr>
                          <tr valign="top" align="left">
                            <td class="greyBgd" valign="middle" width="31%" align="right" height="28">BankCode:</td>
                            <td class="greyBgd" valign="middle" width="69%" align="left"><div id="bankcode">
                              <input name="txtbankcode" type="text" class="innerBox" id="txtbankcode" value="<?php echo $row_edit_query['CBNCode']; ?>" size="60" readonly />
                              * </div></td>
                          </tr>
                          <tr valign="top" align="left">
                            <td class="greyBgd" valign="middle" width="31%" align="right" height="28">Amount:</td>
                            <td class="greyBgd" valign="middle" width="69%" align="left"><div id="amount">
                              <input name="txtAmount" type="text" class="innerBox" id="txtAmount" onKeyUp="this.value=number_format (this.value);" value="<?php echo $row_edit_query['Amount']; ?>" size="60" />
                            </div>
                              * </td>
                          </tr>
                          <tr valign="top" align="left">
                            <td class="greyBgd" valign="middle" align="right" height="35">Narration</td>
                            <td class="greyBgd" valign="middle" align="left"><div id="div">
                              <input name="txNarration" type="text" class="innerBox" id="txNarration" value="<?php echo $row_edit_query['Narration']; ?>" size="60"  />
                            </div></td>
                          </tr>
                          <tr valign="top" align="left">
                            <td class="greyBgd" valign="middle" align="right" height="35">&nbsp;</td>
                            <td class="greyBgd" valign="middle" align="left"><input name="batch" type="hidden" id="batch" value="<?php echo $row_edit_query['Batch']; ?>"></td>
                          </tr>
                          <tr valign="top" align="left">
                            <td colspan="3" valign="middle" align="center" height="10"><!-- <input name="Submit" onClick="location.href='editAccountNo.php'" class="formbutton" value="Edit Account No." type="button"></td>
                         -->
                              <input name="Submit2" type="submit" class="formbutton" value="Update Loan" onClick="submith()"> <input name="Close" type="button" class="formbutton" id="Close" onClick="parent.hide(); parent.location.reload(true)" value="Close">
                          </tr>
                          <tr valign="top" align="left">
                            <td colspan="3" height="3"><img src="education_files/spacer.gif" alt="a" width="1" height="1"></td>
                          </tr>
                        </tbody>
                      </table>
                  </DIV></TD></TR></TBODY></TABLE>
           <input type="hidden" name="MM_update" value="form">
         </FORM><BR><BR><BR></TD></TR></TBODY></TABLE></TD></TR>
  </BODY></HTML>
<?php
mysqli_free_result($coopID);

mysqli_free_result($Bank);

mysqli_free_result($edit_query);
?>
