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

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
  $updateSQL = sprintf("UPDATE tblaccountno SET Bank=%s, AccountNo=%s WHERE COOPNO=%s",
                       GetSQLValueString($_POST['txtBank'], "text"),
                       GetSQLValueString($_POST['txtAccountNo'], "text"),
                       GetSQLValueString($_POST['txtCoopid'], "text"));

  mysql_select_db($database_coopSky, $coopSky);
  $Result1 = mysql_query($updateSQL, $coopSky) or die(mysql_error());
}
?>
<?php require_once('Connections/coopSky.php'); ?>
<?php
$col_coopID = "-1";
if (isset($_GET['coopid'])) {
  $col_coopID = $_GET['coopid'];
}
mysql_select_db($database_coopSky, $coopSky);
$query_coopID = sprintf("SELECT concat (CoopID, ' - ', lastname, ' , ', firstname, '  ', middlename) as coopname, coopid, Bank, AccountNo FROM tblaccountno INNER JOIN tblemployees ON tblemployees.CoopID = tblaccountno.COOPNO WHERE coopid = %s", GetSQLValueString($col_coopID, "text"));
$coopID = mysql_query($query_coopID, $coopSky) or die(mysql_error());
$row_coopID = mysql_fetch_assoc($coopID);
$totalRows_coopID = mysql_num_rows($coopID);

mysql_select_db($database_coopSky, $coopSky);
$query_Bank = "SELECT tblbankcode.bank, tblbankcode.bankcode  FROM tblbankcode ";
$Bank = mysql_query($query_Bank, $coopSky) or die(mysql_error());
$row_Bank = mysql_fetch_assoc($Bank);
$totalRows_Bank = mysql_num_rows($Bank);

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

  mysql_select_db($database_coopSky, $coopSky);
  $Result1 = mysql_query($insertSQL, $coopSky) or die(mysql_error());
  
  $insertGoTo = "batchcreate.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $insertGoTo .= (strpos($insertGoTo, '?')) ? "&" : "?";
    $insertGoTo .= $_SERVER['QUERY_STRING'];
  }
  header(sprintf("Location: %s", $insertGoTo));
}

if ((isset($_GET['id'])) && ($_GET['id'] != "") && ($_POST['action']="deleteProfCert")) {
  $deleteSQL = sprintf("DELETE FROM tbl_proffcert WHERE ProfCertID=%s",
                       GetSQLValueString($_GET['id'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($deleteSQL, $conn_career) or die(mysql_error());
}

if ((isset($_GET['id'])) && ($_GET['id'] != "") && ($_POST['action']="deleteSkill")) {
  $deleteSQL = sprintf("DELETE FROM tbl_skills WHERE SkillID=%s",
                       GetSQLValueString($_GET['id'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($deleteSQL, $conn_career) or die(mysql_error());
}

if ((isset($_GET['id'])) && ($_GET['id'] != "") && ($_POST['action']="deleteWExp")) {
  $deleteSQL = sprintf("DELETE FROM tbl_workexperience WHERE WEID=%s",
                       GetSQLValueString($_GET['id'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($deleteSQL, $conn_career) or die(mysql_error());
}

if ((isset($_GET['id'])) && ($_GET['id'] != "") && ($_POST['action']="deleteEdu")) {
  $deleteSQL = sprintf("DELETE FROM tbl_education WHERE EducationID=%s",
                       GetSQLValueString($_GET['id'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($deleteSQL, $conn_career) or die(mysql_error());
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
<TABLE border=0 cellSpacing=0 cellPadding=0 width="100%" height="100%"><!-- fwtable fwsrc="MTN4U.png" fwbase="index.jpg" fwstyle="Dreamweaver" fwdocid = "1226677029" fwnested="0" -->
  <TBODY>
  <TR>
    <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=750 
  height=1></TD></TR>
  <TR>
    <TD class=centerAligned height=100 vAlign=top>
      <DIV align=center></DIV>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750><!-- fwtable fwsrc="Untitled" fwbase="top.gif" fwstyle="Dreamweaver" fwdocid = "2000728079" fwnested="0" -->
        <TBODY>
        <TR>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=7 
            height=1></TD>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=78 
            height=1></TD>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=491 
            height=1></TD>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=153 
            height=1></TD>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=21 
            height=1></TD>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=1 
            height=1></TD></TR>
        <TR>
          <TD colSpan=5><IMG border=0 name=top_r1_c1 alt="" 
            src="mycv_files/spacer.gif" width=1 height=1></TD>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=1 
            height=11></TD></TR>
        <TR>
          <TD rowSpan=4><IMG border=0 name=top_r2_c1 alt="" 
            src="mycv_files/spacer.gif" width=1 height=1></TD>
          <TD rowSpan=4><A href="http://www.oouth.com"><IMG border=0 
            src="mycv_files/oouthLogo.gif" width=79 height=80></A></TD>
          <TD rowSpan=4 colSpan=2 align=right><IMG 
            src="mycv_files/careers_at_oouth.gif" width=300 height=40><IMG 
            border=0 name=top_r4_c4 alt="" src="mycv_files/spacer.gif" width=1 
            height=1></TD>
          <TD>&nbsp;</TD>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=1 
            height=17></TD></TR>
        <TR>
          <TD rowSpan=3><IMG border=0 name=top_r3_c5 alt="" 
            src="mycv_files/spacer.gif" width=1 height=1></TD>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=1 
            height=37></TD></TR>
        <TR>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=1 
            height=25></TD></TR>
        <TR>
          <TD><IMG border=0 alt="" src="mycv_files/spacer.gif" width=1 
            height=11></TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD height=21 vAlign=top class=mainNav>&nbsp;</TD>
  </TR>
  <TR>
    <TD class=dividerCenterAligned height=1 vAlign=top><IMG border=0 
      name=index_r3_c1 alt="" src="mycv_files/index_r3_c1.jpg" width=750 
      height=1></TD></TR>
  <TR>
    <TD class=globalNav height=25 vAlign=top>&nbsp;</TD>
  </TR>
  <TR>
    <TD class=dividerCenterAligned height=1 vAlign=top><IMG border=0 
      name=index_r5_c1 alt="" src="mycv_files/index_r5_c1.jpg" width=750 
      height=1></TD></TR>
  <TR>
    <TD vAlign=top class=innerPg>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750>
        <TBODY>
        <TR>
          <TD rowSpan=2 width=8><IMG src="mycv_files/spacer.gif" width=1 
            height=1></TD>
          <TD class=breadcrumbs height=20 vAlign=bottom colSpan=2><A 
            href="http://www.oouth.com">Home</A> / Edit Account No. </TD>
          <TD rowSpan=2 width=12><IMG src="mycv_files/spacer.gif" width=1 
            height=1></TD></TR>
        <TR>
          <TD class=Content vAlign=top width=180>
            <P>&nbsp;</P><br>

<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr> 
    <td class="sidenavtxt" align=""> <em><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Welcome,</font></em> 
      <font size="1" face="Verdana, Arial, Helvetica, sans-serif"><span> <br> 
<img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0">		  
<a href="changepasswd.php"></a> <br> 
<img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0">
<a href="personal.php"></a> <br> 
<img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0">		  
<a href="http://careers.mtnonline.com/logout.asp"></a>
      </span></font> </td>
  </tr>
</tbody></table>
<br>
<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr>
    <td colspan="2" class="sidenavtxt" width="100%" align=""><p><a href="vacancies.php"></a> <br>
    </p></td>
  </tr>
  
  <tr>
    <td align=""><img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" width="100%" align=""><a href="http://careers.mtnonline.com/myapplications.asp"></a> </td>
  </tr>
  
</tbody></table>
<br>

<br>
<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr>
    <td colspan="2" class="sidenavtxt" align=""><p><br>
        
      </p>    </td>
  </tr>


  <tr valign="top">
    <td align=""><img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" width="100%" align=""> <a href="personal.php"></a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="beneficiary.php?action=add"></a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="workhistory.php?action=add"></a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="profcert.php?action=add"></a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="education_files/spacer.gif" width="1" border="0" height="8"><img src="education_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="http://careers.mtnonline.com/skills.asp"></a><br>
    <br></td>
  </tr>
  <tr>
  
    <td colspan="2" class="legend" align="">&nbsp;</td>
  </tr>
</tbody></table>

<br>
<script language="JavaScript1.2" src="education_files/misc.htm"></script>

          </TD>
          <TD id=top class=Content vAlign=top rowSpan=2><IMG 
            src="mycv_files/mycv.gif" width=350 height=30> 
            <HR align=left color=#cccccc SIZE=1 width=500>

            <TABLE border=0 cellSpacing=0 cellPadding=0 width=500>
              <TBODY>
              <TR>
                <TD class=toplinks2 vAlign=top>
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
                        <LEGEND class=contentHeader1>Edt Account No<SPAN 
                        id=skills><a name="skill"></a></SPAN></LEGEND>
                        <form action="<?php echo $editFormAction; ?>" name="form1" method="POST" onSubmit="MM_validateForm('batch','','R');return document.MM_returnValue">
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
} while ($row_coopID = mysql_fetch_assoc($coopID));
  $rows = mysql_num_rows($coopID);
  if($rows > 0) {
      mysql_data_seek($coopID, 0);
	  $row_coopID = mysql_fetch_assoc($coopID);
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
} while ($row_Bank = mysql_fetch_assoc($Bank));
  $rows = mysql_num_rows($Bank);
  if($rows > 0) {
      mysql_data_seek($Bank, 0);
	  $row_Bank = mysql_fetch_assoc($Bank);
  }
?>
						      </select>
						  </label></td>
						  </tr>
						  <tr valign="top" align="left">
						  <td class="greyBgd" valign="middle" align="right" height="35">Account No.: </td>
						  <td class="greyBgd" valign="middle" align="left"><label>
						    <input name="txtAccountNo" type="text" class="innerBox" id="txtAccountNo" maxlength="10">
						  </label></td>
						  </tr>
						<tr valign="top" align="left">
                           <td height="35" colspan="2" align="right" valign="middle" class="greyBgd"><div align="center">
                             <input name="CreateBatch" type="submit" class="formbutton" id="CreateBatch" value="Insert Account" onClick="submith()" readonly>
                       `       
                       <input name="Submit" onClick="location.href='batchcreate.php'" class="formbutton" value="Batch" type="button">     </div>
                             </p></td>
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
                        <BR><BR>
                        
                        <SCRIPT language=JavaScript type=text/JavaScript>
<!--
function GP_popupConfirmMsg(msg) { //v1.0
  document.MM_returnValue = confirm(msg);
}
//-->
</SCRIPT>
                        <BR>
                        <BR>
                        </FIELDSET><BR><BR>
                        <BR><BR>
                        <FILDSET><LEGEND class=contentHeader1><SPAN 
                        id=pcert><a name="professional"></a></SPAN></LEGEND>
                        <TABLE cellSpacing=0 cellPadding=4 width="96%" 
                        align=center>
                          <TBODY>
                          <TR vAlign=top>
                            <TD vAlign=center align=left>
                              
                              <BR></TD>
                          </TR>
                          <TR vAlign=top align=left>
                            <TD height=3><IMG 
                              src="mycv_files/spacer.gif" width=1 
                          height=1> </TD>
                          </TR></TBODY></TABLE>
                        </FIELDSET> 
                        <P><BR></P></TD></TR></TBODY></TABLE>
                  </DIV></TD></TR></TBODY></TABLE><BR><BR><BR></TD></TR>
        <TR>
      <TD class=Content vAlign=top>&nbsp;</TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=innerPg height=1 vAlign=top><IMG border=0 name=index_r7_c1 
      alt="" src="mycv_files/index_r7_c1.jpg" width=750 height=1></TD></TR>
  <TR>
    <TD class=innerPg height=21 vAlign=top>
      <TABLE class=contentHeader1 border=0 cellSpacing=0 cellPadding=0 width=750 
      height=21>
        <TBODY>
        <TR>
          <TD class=rightAligned width=10>&nbsp;</TD>
          <TD class=baseNavTxt>&nbsp;</TD>
          <TD class=leftAligned width=12>&nbsp;</TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=innerPg height=1 vAlign=top><IMG border=0 name=index_r9_c1 
      alt="" src="mycv_files/index_r9_c1.jpg" width=750 height=1></TD></TR>
  <TR>
    <TD class=innerPg vAlign=top>&nbsp;</TD></TR></TBODY></TABLE></BODY></HTML>
<?php
mysql_free_result($coopID);

mysql_free_result($Bank);
?>
