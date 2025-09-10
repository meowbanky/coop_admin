<?php require_once('Connections/conn_career.php'); ?>
<?php
session_start();
if(!isset($_SESSION['UserID'])){
	header("Location:index.php"); 
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

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "eduEntry")) {
  $updateSQL = sprintf("UPDATE tbl_personalinfo SET FirstName=%s, MiddleName=%s, LastName=%s, Gender=%s, MaritalStatus=%s, DOB=%s, Address1=%s, Address2=%s, City=%s, `State`=%s, CountryOfOrigin=%s, StateOfOrigin=%s, LGA=%s, NYSC=%s, MobilePhoneNo=%s, DayPhone=%s WHERE UserID=%s",
                       GetSQLValueString($_POST['txtFname'], "text"),
                       GetSQLValueString($_POST['txtMname'], "text"),
                       GetSQLValueString($_POST['txtLname'], "text"),
                       GetSQLValueString($_POST['gender'], "text"),
                       GetSQLValueString($_POST['txtMStatus'], "text"),
                       GetSQLValueString($_POST['txtDOB'], "date"),
                       GetSQLValueString($_POST['txtAddress'], "text"),
                       GetSQLValueString($_POST['txtAddress2'], "text"),
                       GetSQLValueString($_POST['txtCity'], "text"),
                       GetSQLValueString($_POST['txtState'], "text"),
                       GetSQLValueString($_POST['txtcountry'], "text"),
                       GetSQLValueString($_POST['txtStateOfOrigin'], "text"),
                       GetSQLValueString($_POST['txtLGA'], "text"),
                       GetSQLValueString($_POST['txtNYSCCompleted'], "text"),
                       GetSQLValueString($_POST['txtMobPhone'], "text"),
                       GetSQLValueString($_POST['txtDayPhone'], "text"),
                       GetSQLValueString($_POST['dtp'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($updateSQL, $conn_career) or die(mysql_error());
}


$colname_rstPersonal = "-1";
if (isset($_SESSION['UserID'])) {
  $colname_rstPersonal = (get_magic_quotes_gpc()) ? $_SESSION['UserID'] : addslashes($_SESSION['UserID']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_rstPersonal = sprintf("SELECT * FROM tbl_personalinfo WHERE UserID = %s ORDER BY UserID ASC", $colname_rstPersonal);
$rstPersonal = mysql_query($query_rstPersonal, $conn_career) or die(mysql_error());
$row_rstPersonal = mysql_fetch_assoc($rstPersonal);
$totalRows_rstPersonal = mysql_num_rows($rstPersonal);

$colname_Education = "-1";
if (isset($_SESSION['UserID'])) {
  $colname_Education = (get_magic_quotes_gpc()) ? $_SESSION['UserID'] : addslashes($_SESSION['UserID']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_Education = sprintf("SELECT * FROM tbl_education WHERE UserID = %s", $colname_Education);
$Education = mysql_query($query_Education, $conn_career) or die(mysql_error());
$row_Education = mysql_fetch_assoc($Education);
$totalRows_Education = mysql_num_rows($Education);
?>
<html>
<head>


<title>Careers at OOUTH</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<!--Fireworks MX 2004 Dreamweaver MX 2004 target.  Created Sat Dec 04 17:23:24 GMT+0100 2004-->
<link href="personal_files/oouth.css" rel="stylesheet" type="text/css">
<script language="JavaScript" src="personal_files/general.js" type="text/javascript"></script>
<script type="text/javascript" src="personal_files/popcalendar.js"></script></head><body><div onClick="bShow=true" id="calendar" style="z-index: 999; position: absolute; visibility: hidden;"><table style="border: 1px solid rgb(160, 160, 160); font-size: 11px; font-family: arial;" width="220" bgcolor="#ffffff"><tbody><tr bgcolor="#0000aa"><td><table width="218"><tbody><tr><td style="padding: 2px; font-family: arial; font-size: 11px;"><font color="#ffffff"><b><span id="caption"><span id="spanLeft" style="border: 1px solid rgb(51, 102, 255); cursor: pointer;" onmouseover='swapImage("changeLeft","left2.gif");this.style.borderColor="#88AAFF";window.status="Click to scroll to previous month. Hold mouse button to scroll automatically."' onClick="javascript:decMonth()" onmouseout='clearInterval(intervalID1);swapImage("changeLeft","left1.gif");this.style.borderColor="#3366FF";window.status=""' onmousedown='clearTimeout(timeoutID1);timeoutID1=setTimeout("StartDecMonth()",500)' onMouseUp="clearTimeout(timeoutID1);clearInterval(intervalID1)">&nbsp;<img id="changeLeft" src="personal_files/left1.gif" width="10" border="0" height="11">&nbsp;</span>&nbsp;<span id="spanRight" style="border: 1px solid rgb(51, 102, 255); cursor: pointer;" onmouseover='swapImage("changeRight","right2.gif");this.style.borderColor="#88AAFF";window.status="Click to scroll to next month. Hold mouse button to scroll automatically."' onmouseout='clearInterval(intervalID1);swapImage("changeRight","right1.gif");this.style.borderColor="#3366FF";window.status=""' onClick="incMonth()" onmousedown='clearTimeout(timeoutID1);timeoutID1=setTimeout("StartIncMonth()",500)' onMouseUp="clearTimeout(timeoutID1);clearInterval(intervalID1)">&nbsp;<img id="changeRight" src="personal_files/right1.gif" width="10" border="0" height="11">&nbsp;</span>&nbsp;<span id="spanMonth" style="border: 1px solid rgb(51, 102, 255); cursor: pointer;" onmouseover='swapImage("changeMonth","drop2.gif");this.style.borderColor="#88AAFF";window.status="Click to select a month."' onmouseout='swapImage("changeMonth","drop1.gif");this.style.borderColor="#3366FF";window.status=""' onClick="popUpMonth()"></span>&nbsp;<span id="spanYear" style="border: 1px solid rgb(51, 102, 255); cursor: pointer;" onmouseover='swapImage("changeYear","drop2.gif");this.style.borderColor="#88AAFF";window.status="Click to select a year."' onmouseout='swapImage("changeYear","drop1.gif");this.style.borderColor="#3366FF";window.status=""' onClick="popUpYear()"></span>&nbsp;</span></b></font></td><td align="right"><a href="javascript:hideCalendar()"><img src="personal_files/close.gif" alt="Close the Calendar" width="15" border="0" height="13"></a></td></tr></tbody></table></td></tr><tr><td style="padding: 5px;" bgcolor="#ffffff"><span id="content"></span></td></tr><tr bgcolor="#f0f0f0"><td style="padding: 5px;" align="center"><span id="lblToday">Today is <a onmousemove='window.status="Go To Current Month"' onmouseout='window.status=""' title="Go To Current Month" style="text-decoration: none; color: black;" href="javascript:monthSelected=monthNow;yearSelected=yearNow;constructCalendar();">Wed, 8 Jun	2011</a></span></td></tr></tbody></table></div><div id="selectMonth" style="z-index: 999; position: absolute; visibility: hidden;"></div><div id="selectYear" style="z-index: 999; position: absolute; visibility: hidden;"></div>



<table width="100%" border="0" cellpadding="0" cellspacing="0" height="100%">
<!-- fwtable fwsrc="MTN4U.png" fwbase="index.jpg" fwstyle="Dreamweaver" fwdocid = "1226677029" fwnested="0" -->
  <tbody><tr>
   <td><img src="personal_files/spacer.gif" alt="" width="750" border="0" height="1"></td>
  </tr>

  <tr>
   <td class="centerAligned" valign="top" height="100"><div align="center"></div>
<table width="750" border="0" cellpadding="0" cellspacing="0">
<!-- fwtable fwsrc="Untitled" fwbase="top.gif" fwstyle="Dreamweaver" fwdocid = "2000728079" fwnested="0" -->
  <tbody><tr>
   <td><img src="personal_files/spacer.gif" alt="" width="7" border="0" height="1"></td>
   <td><img src="personal_files/spacer.gif" alt="" width="78" border="0" height="1"></td>
   <td><img src="personal_files/spacer.gif" alt="" width="491" border="0" height="1"></td>
   <td><img src="personal_files/spacer.gif" alt="" width="153" border="0" height="1"></td>
   <td><img src="personal_files/spacer.gif" alt="" width="21" border="0" height="1"></td>
   <td><img src="personal_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
  </tr>

  <tr>
   <td colspan="5"><img name="top_r1_c1" src="personal_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
   <td><img src="personal_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
  </tr>
  <tr>
   <td rowspan="4"><img name="top_r2_c1" src="personal_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
    <td rowspan="4"><a href="http://www.oouth.com"><img src="personal_files/oouthLogo.gif" width="79" border="0" height="80"></a></td>
    <td colspan="2" rowspan="4" align="right"><img src="personal_files/careers_at_oouth.gif" width="300" height="40"><img name="top_r4_c4" src="personal_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
    <td>&nbsp;</td>
   <td><img src="personal_files/spacer.gif" alt="" width="1" border="0" height="17"></td>
  </tr>
  <tr>
   <td rowspan="3"><img name="top_r3_c5" src="personal_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
   <td><img src="personal_files/spacer.gif" alt="" width="1" border="0" height="37"></td>
  </tr>
  <tr>
   <td><img src="personal_files/spacer.gif" alt="" width="1" border="0" height="25"></td>
  </tr>
  <tr>
   <td><img src="personal_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
  </tr>
</tbody></table>

</td>
  </tr>
  <tr>
   <td class="mainNav" valign="top" height="21"><table width="750" border="0" cellpadding="0" cellspacing="0" height="21">
     <tbody><tr>
       <td class="rightAligned" width="10">&nbsp;</td>
       <td class="mainNavTxt" valign="bottom"><table width="100%" border="0" cellpadding="0" cellspacing="0">
         <!-- fwtable fwsrc="Untitled" fwbase="nav.gif" fwstyle="Dreamweaver" fwdocid = "1284367442" fwnested="0" -->
         
         <tbody><tr>
           <td><a href="http://careers.mtnonline.com/index.asp"></a></td>
           <td><img src="personal_files/spacer.gif" alt="" width="8" border="0" height="8"></td>
           <td><a href="http://careers.mtnonline.com/departments.asp"></a></td>
           <td><img src="personal_files/spacer.gif" alt="" width="8" border="0" height="8"></td>
           <td><a href="http://careers.mtnonline.com/vacancies.asp"></a></td>
           <td><img src="personal_files/spacer.gif" alt="" width="8" border="0" height="8"></td>
           <td><a href="http://careers.mtnonline.com/lifeatmtn.asp"></a></td>
           <td><img src="personal_files/spacer.gif" alt="" width="8" border="0" height="8"></td>
           <td><a href="http://careers.mtnonline.com/mycv.asp"></a></td>
           <td><img src="personal_files/spacer.gif" alt="" width="8" border="0" height="8"></td>

           <td><a href="http://careers.mtnonline.com/logout.asp"></a></td>
           </tr>
       </tbody></table></td>
       <td class="leftAligned" width="12">&nbsp;</td>
     </tr>
   </tbody></table>
</td>
  </tr>
  <tr>
   <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r3_c1" src="personal_files/index_r3_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="globalNav" valign="top" height="25"><table width="750" border="0" cellpadding="0" cellspacing="0" height="21">
     <tbody><tr>
       <td class="rightAligned" width="10"><img src="personal_files/spacer.gif" width="1" height="1"></td>
       <td><img src="personal_files/spacer.gif" width="6"></td>
       <td class="leftAligned" width="12"><img src="personal_files/spacer.gif" width="1" height="1"></td>
     </tr>
   </tbody></table>

</td>
  </tr>
  <tr>
   <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r5_c1" src="personal_files/index_r5_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top"><table width="750" border="0" cellpadding="0" cellspacing="0">
     <tbody><tr>
       <td rowspan="2" width="8"><img src="personal_files/spacer.gif" width="1" height="1"></td>
       <td colspan="2" class="breadcrumbs" valign="bottom" height="20"><a href="http://www.oouth.com">Home</a> / <a href="http://careers.mtnonline.com/mycv.asp">My CV</a> / Personal Details </td>
       <td rowspan="2" width="12"><img src="personal_files/spacer.gif" width="1" height="1"></td>
     </tr>
     <tr>
       <td class="Content" valign="top" width="180">

<p>&nbsp;</p><br>

<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr> 
    <td class="sidenavtxt" align=""> <em><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Welcome,</font></em> 
      <font size="1" face="Verdana, Arial, Helvetica, sans-serif"><span><?php echo $row_rstPersonal['FirstName']; ?><br> 
<img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0">		  
<a href="changepasswd.php">Change Password</a> <br> 
<img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0">
<a href="personal.php">Edit Details</a> <br> 
<img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0">		  
<a href="http://careers.mtnonline.com/logout.asp">Logout</a>
      </span></font> </td>
  </tr>
</tbody></table>
<br>
<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr>
    <td colspan="2" class="sidenavtxt" width="100%" align=""><p><a href="vacancies.php">View Vacancies</a> <br>
    </p></td>
  </tr>
  
  <tr>
    <td align=""><img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" width="100%" align=""><a href="http://careers.mtnonline.com/myapplications.asp">My Applications</a> </td>
  </tr>
  
</tbody></table>
<br>

<br>
<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr>
    <td colspan="2" class="sidenavtxt" align=""><p><a href="index.php">View My CV</a><img src="personal_files/spacer.gif" width="8" height="8">
        
        <font color="#009966"><?php if (($totalRows_Education > 0) && ($totalRows_rstPersonal > 0) ) {echo "<IMG alt=\"CV Completed\" align=absMiddle src=\"mycv_files\/cv_completed.gif\" width=16 height=12>" ; } else {echo "<IMG alt=\"CV Incompleted\" align=absMiddle                   src=\"mycv_files\/cv_uncompleted.gif\" width=16 height=12>" ; }?></font>
        
<br>
        
      </p>    </td>
  </tr>


  <tr valign="top">
    <td align=""><img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" width="100%" align=""> <a href="personal.php
	">Personal Information </a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="beneficiary.php?action=add">Educational History</a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="workhistory.php?action=add">Work Experience</a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="profcert.php?action=add">Professional Certifications</a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="personal_files/spacer.gif" width="1" border="0" height="8"><img src="personal_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="skills.php?action=add">Skills</a><br>
    <br></td>
  </tr>
  <tr>
  
    <td colspan="2" class="legend" align="">Legend<em><br>      
      <img src="personal_files/cv_completed.gif" alt="CV Completed" width="9" align="absmiddle" height="8">-Complete<img src="personal_files/spacer.gif" width="8" height="8"> 
      <font color="#009966"><img src="personal_files/cv_uncompleted.gif" alt="CV Completed" width="9" align="absmiddle" height="8"></font>-Incomplete </em></td>
  </tr>
</tbody></table>

<br>
<script language="JavaScript1.2" src="personal_files/misc.htm"></script>
</td>
       <td rowspan="2" class="Content" valign="top"><img src="personal_files/mycv.gif" width="350" height="30"> <hr size="1" width="500" align="left" color="#cccccc">
         <table width="500" border="0" cellpadding="0" cellspacing="0">
           <tbody><tr>
             <td class="toplinks2" valign="top"><div align="justify">
                 <table class="Content" width="100%" border="0" cellpadding="4" cellspacing="0">
                   <tbody><tr>
                     <td valign="top"><span class="homeContentSmaller">
 <?php if ((isset($_POST['dtp'])) && (($_POST['dtp'])== ($_SESSION['UserID']))){ echo "<table class=\"errorBox\" width=\"500\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
  <tbody><tr>
    <td>Your update was successful</td>
  </tr>
</tbody></table>" ;} ?>
 <br>


                     </span>
<form action="<?php echo $editFormAction; ?>" method="POST" name="eduEntry" onSubmit="YY_checkform('eduEntry','txtInstitution','#q','0','Please enter institution of study','txtStudyCourse','#q','0','Please enter course of study','txtStartDate','#^\([0-9][0-9]\)\/\([0-9][0-9]\)\/\([0-9]{4}\)$#2#1#3','3','Please enter start date','txtEndDate','#^\([0-9][0-9]\)\/\([0-9][0-9]\)\/\([0-9]{4}\)$#2#1#3','3','Please enter end date','txtEduLevel','#q','1','Please indicate educational level','txtQualification','#q','1','Please select qualification obtained','txtClass','#q','1','Please select class obtained');return document.MM_returnValue">
                         <fieldset>
                         <legend class="contentHeader1">Personal Information </legend>
                         
                         
                         <table width="96%" align="center" cellpadding="4" cellspacing="0">
                             <tbody><tr valign="top" align="left">
                                <td colspan="2" height="1"><img src="personal_files/spacer.gif" width="1" height="1"></td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">First Name: </td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtFname" type="text" class="innerBox" id="txtFname" value="<?php echo $row_rstPersonal['FirstName']; ?>">
          *</td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Middle Name: </td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtMname" class="innerBox" id="txtMname" value="<?php echo $row_rstPersonal['MiddleName']; ?>" type="text">
                                </td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Last Name: </td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtLname" class="innerBox" id="txtLname" value="<?php echo $row_rstPersonal['LastName']; ?>" type="text">
          *</td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Gender:</td>
                                <td class="greyBgd" width="57%" align="left"><p>
                                    
							        <label>
                                    <input <?php if (!(strcmp($row_rstPersonal['Gender'],"Male"))) {echo "checked=\"checked\"";} ?> name="gender" value="Male" checked="checked" type="radio">
                                    Male</label>
                                    <label>
                                    <input <?php if (!(strcmp($row_rstPersonal['Gender'],"Female"))) {echo "checked=\"checked\"";} ?> name="gender" value="Female" type="radio">
                                    Female</label>
							        
                                    <br>
                                </p></td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Marital Status: </td>
                                <td class="greyBgd" width="57%" align="left"><select name="txtMStatus" class="innerBox" id="txtMStatus">
                                  <option selected="selected" value="" <?php if (!(strcmp("", $row_rstPersonal['MaritalStatus']))) {echo "selected=\"selected\"";} ?>>Select ...</option>
                                  <option value="Single" selected="selected" <?php if (!(strcmp("Single", $row_rstPersonal['MaritalStatus']))) {echo "selected=\"selected\"";} ?>> Single</option>
                                  <option value="Married" <?php if (!(strcmp("Married", $row_rstPersonal['MaritalStatus']))) {echo "selected=\"selected\"";} ?>> Married</option>
                                  <option value=" Divorced" <?php if (!(strcmp(" Divorced", $row_rstPersonal['MaritalStatus']))) {echo "selected=\"selected\"";} ?>> Divorced</option>
                                    
                                   </select>
                                </td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Date of Birth [mm/dd/yyyy]: </td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtDOB" class="innerBox" id="txtDOB" value="<?php echo $row_rstPersonal['DOB']; ?>" type="text">
                                  <input src="personal_files/ew_calendar.gif" alt="Pick a Date" onClick="popUpCalendar(this, this.form.txtDOB,'yyyy-mm-dd');return false;" type="image">
          * </td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Address:</td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtAddress" class="innerBox" id="txtAddress" value="<?php echo $row_rstPersonal['Address1']; ?>" type="text"></td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Address 2: </td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtAddress2" class="innerBox" id="txtAddress2" value="<?php echo $row_rstPersonal['Address2']; ?>" type="text"></td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">City:</td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtCity" class="innerBox" id="txtCity" value="<?php echo $row_rstPersonal['City']; ?>" type="text"></td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">State:</td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtState" class="innerBox" id="txtState" value="<?php echo $row_rstPersonal['State']; ?>" type="text"></td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Country of Origin: </td>
                                <td class="greyBgd" width="57%" align="left"><select name="txtcountry" size="1" class="innerBox" id="txtcountry">
                                  <option value="" selected="selected" <?php if (!(strcmp("", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Select Country ...</option><option id="AF0" value="Afghanistan" <?php if (!(strcmp("Afghanistan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Afghanistan </option><option value="Albania" id="AL0" <?php if (!(strcmp("Albania", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Albania </option><option id="DZ0" value="Algeria" <?php if (!(strcmp("Algeria", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Algeria </option><option id="AS0" value="American Samoa" <?php if (!(strcmp("American Samoa", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>American Samoa </option><option id="AD0" value="Andorra" <?php if (!(strcmp("Andorra", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Andorra </option><option id="AO0" value="Angola" <?php if (!(strcmp("Angola", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Angola </option><option id="AI0" value="Anguilla" <?php if (!(strcmp("Anguilla", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Anguilla </option><option id="AQ0" value="Antarctica" <?php if (!(strcmp("Antarctica", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Antarctica </option><option id="AG0" value="Antigua and Barbuda" <?php if (!(strcmp("Antigua and Barbuda", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Antigua and Barbuda </option><option id="AR0" value="Argentina" <?php if (!(strcmp("Argentina", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Argentina </option><option id="AM0" value="Armenia" <?php if (!(strcmp("Armenia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Armenia </option><option id="AW0" value="Aruba" <?php if (!(strcmp("Aruba", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Aruba </option><option id="AU0" value="Australia" <?php if (!(strcmp("Australia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Australia </option><option id="AT0" value="Austria" <?php if (!(strcmp("Austria", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Austria </option><option id="AZ0" value="Azerbaijan" <?php if (!(strcmp("Azerbaijan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Azerbaijan </option><option id="BS0" value="Bahamas" <?php if (!(strcmp("Bahamas", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bahamas </option><option id="BH0" value="Bahrain" <?php if (!(strcmp("Bahrain", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bahrain </option><option id="BD0" value="Bangladesh" <?php if (!(strcmp("Bangladesh", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bangladesh </option><option id="BB0" value="Barbados" <?php if (!(strcmp("Barbados", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Barbados </option><option id="BY0" value="Belarus" <?php if (!(strcmp("Belarus", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Belarus </option><option id="BE0" value="Belgium" <?php if (!(strcmp("Belgium", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Belgium </option><option id="BZ0" value="Belize" <?php if (!(strcmp("Belize", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Belize </option><option id="BJ0" value="Benin" <?php if (!(strcmp("Benin", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Benin </option><option id="BM0" value="Bermuda" <?php if (!(strcmp("Bermuda", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bermuda </option><option id="BT0" value="Bhutan" <?php if (!(strcmp("Bhutan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bhutan </option><option id="BO0" value="Bolivia" <?php if (!(strcmp("Bolivia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bolivia </option><option id="BA0" value="Bosnia and Herzegovina" <?php if (!(strcmp("Bosnia and Herzegovina", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bosnia and Herzegovina </option><option id="BW0" value="Botswana" <?php if (!(strcmp("Botswana", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Botswana </option><option id="BV0" value="Bouvet Island" <?php if (!(strcmp("Bouvet Island", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bouvet Island </option><option id="BR0" value="Brazil" <?php if (!(strcmp("Brazil", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Brazil </option><option id="IO0" value="British Indian Ocean Territory" <?php if (!(strcmp("British Indian Ocean Territory", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>British Indian Ocean Territory </option><option id="BN0" value="Brunei" <?php if (!(strcmp("Brunei", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Brunei </option><option id="BG0" value="Bulgaria" <?php if (!(strcmp("Bulgaria", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bulgaria </option><option id="BF0" value="Burkina Faso" <?php if (!(strcmp("Burkina Faso", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Burkina Faso </option><option id="BI0" value="Burundi" <?php if (!(strcmp("Burundi", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Burundi </option><option id="KH0" value="Cambodia" <?php if (!(strcmp("Cambodia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cambodia </option><option id="CM0" value="Cameroon" <?php if (!(strcmp("Cameroon", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cameroon </option><option id="CA0" value="Canada" <?php if (!(strcmp("Canada", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Canada </option><option id="CV0" value="Cape Verde" <?php if (!(strcmp("Cape Verde", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cape Verde </option><option id="KY0" value="Cayman Islands" <?php if (!(strcmp("Cayman Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cayman Islands </option><option id="CF0" value="Central African Republic" <?php if (!(strcmp("Central African Republic", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Central African Republic </option><option id="TD0" value="Chad" <?php if (!(strcmp("Chad", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Chad </option><option id="CL0" value="Chile" <?php if (!(strcmp("Chile", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Chile </option><option id="CN0" value="China" <?php if (!(strcmp("China", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>China </option><option id="CX0" value="Christmas Island" <?php if (!(strcmp("Christmas Island", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Christmas Island </option><option id="CC0" value="Cocos (Keeling) Islands" <?php if (!(strcmp("Cocos (Keeling) Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cocos (Keeling) Islands </option><option id="CO0" value="Colombia" <?php if (!(strcmp("Colombia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Colombia </option><option id="KM0" value="Comoros" <?php if (!(strcmp("Comoros", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Comoros </option><option id="CG0" value="Congo" <?php if (!(strcmp("Congo", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Congo </option><option id="CK0" value="Cook Islands" <?php if (!(strcmp("Cook Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cook Islands </option><option id="CR0" value="Costa Rica" <?php if (!(strcmp("Costa Rica", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Costa Rica </option><option id="CI0" value="Côte d'Ivoire" <?php if (!(strcmp("Côte d\'Ivoire", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Côte d'Ivoire </option><option id="HR0" value="Croatia (Hrvatska)" <?php if (!(strcmp("Croatia (Hrvatska)", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Croatia (Hrvatska) </option><option id="CU0" value="Cuba" <?php if (!(strcmp("Cuba", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cuba </option><option id="CY0" value="Cyprus" <?php if (!(strcmp("Cyprus", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cyprus </option><option id="CZ0" value="Czech Republic" <?php if (!(strcmp("Czech Republic", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Czech Republic </option><option id="CD0" value="Congo (DRC)" <?php if (!(strcmp("Congo (DRC)", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Congo (DRC) </option><option id="DK0" value="Denmark" <?php if (!(strcmp("Denmark", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Denmark </option><option id="DJ0" value="Djibouti" <?php if (!(strcmp("Djibouti", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Djibouti </option><option id="DM0" value="Dominica" <?php if (!(strcmp("Dominica", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Dominica </option><option id="DO0" value="Dominican Republic" <?php if (!(strcmp("Dominican Republic", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Dominican Republic </option><option id="TP0" value="East Timor" <?php if (!(strcmp("East Timor", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>East Timor </option><option id="EC0" value="Ecuador" <?php if (!(strcmp("Ecuador", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ecuador </option><option id="EG0" value="Egypt" <?php if (!(strcmp("Egypt", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Egypt </option><option id="SV0" value="El Salvador" <?php if (!(strcmp("El Salvador", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>El Salvador </option><option id="GQ0" value="Equatorial Guinea" <?php if (!(strcmp("Equatorial Guinea", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Equatorial Guinea </option><option id="ER0" value="Eritrea" <?php if (!(strcmp("Eritrea", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Eritrea </option><option id="EE0" value="Estonia" <?php if (!(strcmp("Estonia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Estonia </option><option id="ET0" value="Ethiopia" <?php if (!(strcmp("Ethiopia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ethiopia </option><option id="FK0" value="Falkland Islands (Islas Malvinas)" <?php if (!(strcmp("Falkland Islands (Islas Malvinas)", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Falkland Islands (Islas Malvinas) </option><option id="FO0" value="Faroe Islands" <?php if (!(strcmp("Faroe Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Faroe Islands </option><option id="FJ0" value="Fiji Islands" <?php if (!(strcmp("Fiji Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Fiji Islands </option><option id="FI0" value="Finland" <?php if (!(strcmp("Finland", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Finland </option><option id="FR0" value="France" <?php if (!(strcmp("France", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>France </option><option id="GF0" value="French Guiana" <?php if (!(strcmp("French Guiana", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>French Guiana </option><option id="PF0" value="French Polynesia" <?php if (!(strcmp("French Polynesia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>French Polynesia </option><option id="TF0" value="French Southern and Antarctic Lands" <?php if (!(strcmp("French Southern and Antarctic Lands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>French Southern and Antarctic Lands </option><option id="GA0" value="Gabon" <?php if (!(strcmp("Gabon", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Gabon </option><option id="GM0" value="Gambia" <?php if (!(strcmp("Gambia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Gambia </option><option id="GE0" value="Georgia" <?php if (!(strcmp("Georgia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Georgia </option><option id="DE0" value="Germany" <?php if (!(strcmp("Germany", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Germany </option><option id="GH0" value="Ghana" <?php if (!(strcmp("Ghana", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ghana </option><option id="GI0" value="Gibraltar" <?php if (!(strcmp("Gibraltar", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Gibraltar </option><option id="GR0" value="Greece" <?php if (!(strcmp("Greece", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Greece </option><option id="GL0" value="Greenland" <?php if (!(strcmp("Greenland", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Greenland </option><option id="GD0" value="Grenada" <?php if (!(strcmp("Grenada", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Grenada </option><option id="GP0" value="Guadeloupe" <?php if (!(strcmp("Guadeloupe", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Guadeloupe </option><option id="GU0" value="Guam" <?php if (!(strcmp("Guam", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Guam </option><option id="GT0" value="Guatemala" <?php if (!(strcmp("Guatemala", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Guatemala </option><option id="GN0" value="Guinea" <?php if (!(strcmp("Guinea", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Guinea </option><option id="GW0" value="Guinea-Bissau" <?php if (!(strcmp("Guinea-Bissau", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Guinea-Bissau </option><option id="GY0" value="Guyana" <?php if (!(strcmp("Guyana", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Guyana </option><option id="HT0" value="Haiti" <?php if (!(strcmp("Haiti", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Haiti </option><option id="HM0" value="Heard Island and McDonald Islands" <?php if (!(strcmp("Heard Island and McDonald Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Heard Island and McDonald Islands </option><option id="HN0" value="Honduras" <?php if (!(strcmp("Honduras", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Honduras </option><option id="HK0" value="Hong Kong SAR" <?php if (!(strcmp("Hong Kong SAR", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Hong Kong SAR </option><option id="HU0" value="Hungary" <?php if (!(strcmp("Hungary", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Hungary </option><option id="IS0" value="Iceland" <?php if (!(strcmp("Iceland", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Iceland </option><option id="IN0" value="India" <?php if (!(strcmp("India", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>India </option><option id="ID0" value="Indonesia" <?php if (!(strcmp("Indonesia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Indonesia </option><option id="IR0" value="Iran" <?php if (!(strcmp("Iran", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Iran </option><option id="IQ0" value="Iraq" <?php if (!(strcmp("Iraq", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Iraq </option><option id="IE0" value="Ireland" <?php if (!(strcmp("Ireland", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ireland </option><option id="IL0" value="Israel" <?php if (!(strcmp("Israel", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Israel </option><option id="IT0" value="Italy" <?php if (!(strcmp("Italy", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Italy </option><option id="JM0" value="Jamaica" <?php if (!(strcmp("Jamaica", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Jamaica </option><option id="JP0" value="Japan" <?php if (!(strcmp("Japan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Japan </option><option id="JO0" value="Jordan" <?php if (!(strcmp("Jordan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Jordan </option><option id="KZ0" value="Kazakhstan" <?php if (!(strcmp("Kazakhstan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kazakhstan </option><option id="KE0" value="Kenya" <?php if (!(strcmp("Kenya", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kenya </option><option id="KI0" value="Kiribati" <?php if (!(strcmp("Kiribati", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kiribati </option><option id="KR0" value="Korea" <?php if (!(strcmp("Korea", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Korea </option><option id="KW0" value="Kuwait" <?php if (!(strcmp("Kuwait", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kuwait </option><option id="KG0" value="Kyrgyzstan" <?php if (!(strcmp("Kyrgyzstan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kyrgyzstan </option><option id="LA0" value="Laos" <?php if (!(strcmp("Laos", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Laos </option><option id="LV0" value="Latvia" <?php if (!(strcmp("Latvia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Latvia </option><option id="LB0" value="Lebanon" <?php if (!(strcmp("Lebanon", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Lebanon </option><option id="LS0" value="Lesotho" <?php if (!(strcmp("Lesotho", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Lesotho </option><option id="LR0" value="Liberia" <?php if (!(strcmp("Liberia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Liberia </option><option id="LY0" value="Libya" <?php if (!(strcmp("Libya", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Libya </option><option id="LI0" value="Liechtenstein" <?php if (!(strcmp("Liechtenstein", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Liechtenstein </option><option id="LT0" value="Lithuania" <?php if (!(strcmp("Lithuania", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Lithuania </option><option id="LU0" value="Luxembourg" <?php if (!(strcmp("Luxembourg", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Luxembourg </option><option id="MO0" value="Macao SAR" <?php if (!(strcmp("Macao SAR", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Macao SAR </option><option id="MK0" value="Macedonia, Former Yugoslav Republic of" <?php if (!(strcmp("Macedonia, Former Yugoslav Republic of", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Macedonia, Former Yugoslav Republic of </option><option id="MG0" value="Madagascar" <?php if (!(strcmp("Madagascar", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Madagascar </option><option id="MW0" value="Malawi" <?php if (!(strcmp("Malawi", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Malawi </option><option id="MY0" value="Malaysia" <?php if (!(strcmp("Malaysia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Malaysia </option><option id="MV0" value="Maldives" <?php if (!(strcmp("Maldives", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Maldives </option><option id="ML0" value="Mali" <?php if (!(strcmp("Mali", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Mali </option><option id="MT0" value="Malta" <?php if (!(strcmp("Malta", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Malta </option><option id="MH0" value="Marshall Islands" <?php if (!(strcmp("Marshall Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Marshall Islands </option><option id="MQ0" value="Martinique" <?php if (!(strcmp("Martinique", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Martinique </option><option id="MR0" value="Mauritania" <?php if (!(strcmp("Mauritania", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Mauritania </option><option id="MU0" value="Mauritius" <?php if (!(strcmp("Mauritius", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Mauritius </option><option id="YT0" value="Mayotte" <?php if (!(strcmp("Mayotte", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Mayotte </option><option id="MX0" value="Mexico" <?php if (!(strcmp("Mexico", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Mexico </option><option id="FM0" value="Micronesia" <?php if (!(strcmp("Micronesia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Micronesia </option><option id="MD0" value="Moldova" <?php if (!(strcmp("Moldova", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Moldova </option><option id="MC0" value="Monaco" <?php if (!(strcmp("Monaco", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Monaco </option><option id="MN0" value="Mongolia" <?php if (!(strcmp("Mongolia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Mongolia </option><option id="MS0" value="Montserrat" <?php if (!(strcmp("Montserrat", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Montserrat </option><option id="MA0" value="Morocco" <?php if (!(strcmp("Morocco", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Morocco </option><option id="MZ0" value="Mozambique" <?php if (!(strcmp("Mozambique", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Mozambique </option><option id="MM0" value="Myanmar" <?php if (!(strcmp("Myanmar", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Myanmar </option><option value="Namibia" id="NA0" <?php if (!(strcmp("Namibia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Namibia </option><option value="Nauru" id="NR0" <?php if (!(strcmp("Nauru", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Nauru </option><option value="Nepal" id="NP0" <?php if (!(strcmp("Nepal", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Nepal </option><option value="Netherlands" id="NL0" <?php if (!(strcmp("Netherlands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Netherlands </option><option value="Netherlands Antilles" id="AN0" <?php if (!(strcmp("Netherlands Antilles", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Netherlands Antilles </option><option value="New Caledonia" id="NC0" <?php if (!(strcmp("New Caledonia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>New Caledonia </option><option value="New Zealand" id="NZ0" <?php if (!(strcmp("New Zealand", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>New Zealand </option><option value="Nicaragua" id="NI0" <?php if (!(strcmp("Nicaragua", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Nicaragua </option><option value="Niger" id="NE0" <?php if (!(strcmp("Niger", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Niger </option><option value="Nigeria" id="NG0" <?php if (!(strcmp("Nigeria", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Nigeria </option><option id="NU0" value="Niue" <?php if (!(strcmp("Niue", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Niue </option><option id="NF0" value="Norfolk Island" <?php if (!(strcmp("Norfolk Island", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Norfolk Island </option><option id="KP0" value="North Korea" <?php if (!(strcmp("North Korea", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>North Korea </option><option id="MP0" value="Northern Mariana Islands" <?php if (!(strcmp("Northern Mariana Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Northern Mariana Islands </option><option id="NO0" value="Norway" <?php if (!(strcmp("Norway", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Norway </option><option id="OM0" value="Oman" <?php if (!(strcmp("Oman", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Oman </option><option id="PK0" value="Pakistan" <?php if (!(strcmp("Pakistan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Pakistan </option><option id="PW0" value="Palau" <?php if (!(strcmp("Palau", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Palau </option><option id="PA0" value="Panama" <?php if (!(strcmp("Panama", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Panama </option><option id="PG0" value="Papua New Guinea" <?php if (!(strcmp("Papua New Guinea", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Papua New Guinea </option><option id="PY0" value="Paraguay" <?php if (!(strcmp("Paraguay", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Paraguay </option><option id="PE0" value="Peru" <?php if (!(strcmp("Peru", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Peru </option><option id="PH0" value="Philippines" <?php if (!(strcmp("Philippines", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Philippines </option><option id="PN0" value="Pitcairn Islands" <?php if (!(strcmp("Pitcairn Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Pitcairn Islands </option><option id="PL0" value="Poland" <?php if (!(strcmp("Poland", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Poland </option><option id="PT0" value="Portugal" <?php if (!(strcmp("Portugal", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Portugal </option><option id="PR0" value="Puerto Rico" <?php if (!(strcmp("Puerto Rico", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Puerto Rico </option><option id="QA0" value="Qatar" <?php if (!(strcmp("Qatar", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Qatar </option><option id="RE0" value="Reunion" <?php if (!(strcmp("Reunion", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Reunion </option><option id="RO0" value="Romania" <?php if (!(strcmp("Romania", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Romania </option><option id="RU0" value="Russia" <?php if (!(strcmp("Russia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Russia </option><option id="RW0" value="Rwanda" <?php if (!(strcmp("Rwanda", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Rwanda </option><option id="WS0" value="Samoa" <?php if (!(strcmp("Samoa", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Samoa </option><option id="SM0" value="San Marino" <?php if (!(strcmp("San Marino", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>San Marino </option><option id="ST0" value="São Tomé and Príncipe" <?php if (!(strcmp("São Tomé and Príncipe", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>São Tomé and Príncipe </option><option id="SA0" value="Saudi Arabia" <?php if (!(strcmp("Saudi Arabia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Saudi Arabia </option><option id="SN0" value="Senegal" <?php if (!(strcmp("Senegal", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Senegal </option><option id="YU0" value="Serbia and Montenegro" <?php if (!(strcmp("Serbia and Montenegro", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Serbia and Montenegro </option><option id="SC0" value="Seychelles" <?php if (!(strcmp("Seychelles", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Seychelles </option><option id="SL0" value="Sierra Leone" <?php if (!(strcmp("Sierra Leone", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Sierra Leone </option><option id="SG0" value="Singapore" <?php if (!(strcmp("Singapore", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Singapore </option><option id="SK0" value="Slovakia" <?php if (!(strcmp("Slovakia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Slovakia </option><option id="SI0" value="Slovenia" <?php if (!(strcmp("Slovenia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Slovenia </option><option id="SB0" value="Solomon Islands" <?php if (!(strcmp("Solomon Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Solomon Islands </option><option id="SO0" value="Somalia" <?php if (!(strcmp("Somalia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Somalia </option><option value="South Africa" id="ZA0" <?php if (!(strcmp("South Africa", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>South Africa </option><option id="GS0" value="South Georgia and the South Sandwich            Islands" <?php if (!(strcmp("South Georgia and the South Sandwich            Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>South Georgia and the South Sandwich Islands </option><option id="ES0" value="Spain" <?php if (!(strcmp("Spain", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Spain </option><option id="LK0" value="Sri Lanka" <?php if (!(strcmp("Sri Lanka", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Sri Lanka </option><option id="SH0" value="St. Helena" <?php if (!(strcmp("St. Helena", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>St. Helena </option><option id="KN0" value="St. Kitts and Nevis" <?php if (!(strcmp("St. Kitts and Nevis", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>St. Kitts and Nevis </option><option id="LC0" value="St. Lucia" <?php if (!(strcmp("St. Lucia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>St. Lucia </option><option id="PM0" value="St. Pierre and Miquelon" <?php if (!(strcmp("St. Pierre and Miquelon", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>St. Pierre and Miquelon </option><option id="VC0" value="St. Vincent and the Grenadines" <?php if (!(strcmp("St. Vincent and the Grenadines", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>St. Vincent and the Grenadines </option><option id="SD0" value="Sudan" <?php if (!(strcmp("Sudan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Sudan </option><option id="SR0" value="Suriname" <?php if (!(strcmp("Suriname", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Suriname </option><option id="SJ0" value="Svalbard and Jan Mayen" <?php if (!(strcmp("Svalbard and Jan Mayen", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Svalbard and Jan Mayen </option><option id="SZ0" value="Swaziland" <?php if (!(strcmp("Swaziland", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Swaziland </option><option id="SE0" value="Sweden" <?php if (!(strcmp("Sweden", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Sweden </option><option id="CH0" value="Switzerland" <?php if (!(strcmp("Switzerland", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Switzerland </option><option id="SY0" value="Syria" <?php if (!(strcmp("Syria", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Syria </option><option id="TW0" value="Taiwan" <?php if (!(strcmp("Taiwan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Taiwan </option><option id="TJ0" value="Tajikistan" <?php if (!(strcmp("Tajikistan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Tajikistan </option><option id="TZ0" value="Tajikistan" <?php if (!(strcmp("Tajikistan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Tajikistan </option><option id="TH0" value="Thailand" <?php if (!(strcmp("Thailand", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Thailand </option><option id="TG0" value="Togo" <?php if (!(strcmp("Togo", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Togo </option><option id="TK0" value="Tokelau" <?php if (!(strcmp("Tokelau", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Tokelau </option><option id="TO0" value="Tonga" <?php if (!(strcmp("Tonga", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Tonga </option><option id="TT0" value="Trinidad and Tobago" <?php if (!(strcmp("Trinidad and Tobago", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Trinidad and Tobago </option><option id="TN0" value="Tunisia" <?php if (!(strcmp("Tunisia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Tunisia </option><option id="TR0" value="Turkey" <?php if (!(strcmp("Turkey", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Turkey </option><option id="TM0" value="Turkmenistan" <?php if (!(strcmp("Turkmenistan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Turkmenistan </option><option id="TC0" value="Turks and Caicos Islands" <?php if (!(strcmp("Turks and Caicos Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Turks and Caicos Islands </option><option id="TV0" value="Tuvalu" <?php if (!(strcmp("Tuvalu", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Tuvalu </option><option id="UG0" value="Uganda" <?php if (!(strcmp("Uganda", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Uganda </option><option id="UA0" value="Ukraine" <?php if (!(strcmp("Ukraine", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ukraine </option><option id="AE0" value="United Arab Emirates" <?php if (!(strcmp("United Arab Emirates", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>United Arab Emirates </option><option id="UK0" value="United Kingdom" <?php if (!(strcmp("United Kingdom", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>United Kingdom </option><option id="US0" value="United States" <?php if (!(strcmp("United States", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>United States </option><option id="UM0" value="United States Minor Outlying Islands" <?php if (!(strcmp("United States Minor Outlying Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>United States Minor Outlying Islands </option><option id="UY0" value="Uruguay" <?php if (!(strcmp("Uruguay", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Uruguay </option><option id="UZ0" value="Uzbekistan" <?php if (!(strcmp("Uzbekistan", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Uzbekistan </option><option id="VU0" value="Vanuatu" <?php if (!(strcmp("Vanuatu", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Vanuatu </option><option id="VA0" value="Vatican City" <?php if (!(strcmp("Vatican City", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Vatican City </option><option id="VE0" value="Venezuela" <?php if (!(strcmp("Venezuela", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Venezuela </option><option id="VN0" value="Viet Nam" <?php if (!(strcmp("Viet Nam", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Viet Nam </option><option id="VG0" value="Virgin Islands (British)" <?php if (!(strcmp("Virgin Islands (British)", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Virgin Islands (British) </option><option id="VI0" value="Virgin Islands" <?php if (!(strcmp("Virgin Islands", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Virgin Islands </option><option id="WF0" value="Wallis and Futuna" <?php if (!(strcmp("Wallis and Futuna", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Wallis and Futuna </option><option id="YE0" value="Yemen" <?php if (!(strcmp("Yemen", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Yemen </option><option id="ZM0" value="Zambia" <?php if (!(strcmp("Zambia", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Zambia </option><option id="ZW0" value="Zimbabwe" <?php if (!(strcmp("Zimbabwe", $row_rstPersonal['CountryOfOrigin']))) {echo "selected=\"selected\"";} ?>>Zimbabwe</option>
                                </select></td>
                             </tr><tr>
                                <td class="greyBgd" align="right" height="35">State of Origin:</td>
                              
                                <td class="greyBgd">
                                  <select name="txtStateOfOrigin" class="innerBox" id="txtStateOfOrigin">
                                    <option value="N/A" selected="selected" <?php if (!(strcmp("N/A", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>N/A</option>
                                    <option value="Abia" <?php if (!(strcmp("Abia", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Abia</option>
                                    <option value="Adamawa" <?php if (!(strcmp("Adamawa", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Adamawa</option>
                                    <option value="Akwa Ibom" <?php if (!(strcmp("Akwa Ibom", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Akwa Ibom</option>
                                    <option value="Anambra" <?php if (!(strcmp("Anambra", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Anambra</option>
                                    <option value="Bauchi" <?php if (!(strcmp("Bauchi", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bauchi</option>
                                    <option value="Bayelsa" <?php if (!(strcmp("Bayelsa", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Bayelsa</option>
                                    <option value="Benue" <?php if (!(strcmp("Benue", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Benue</option>
                                    <option value="Borno" <?php if (!(strcmp("Borno", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Borno</option>
                                    <option value="Cross River" <?php if (!(strcmp("Cross River", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Cross River</option><option value="Delta" <?php if (!(strcmp("Delta", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Delta</option>
                                    <option value="Ebonyi" <?php if (!(strcmp("Ebonyi", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ebonyi</option>
                                    <option value="Edo" <?php if (!(strcmp("Edo", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Edo</option>
                                    <option value="Ekiti" <?php if (!(strcmp("Ekiti", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ekiti</option>
                                    <option value="Enugu" <?php if (!(strcmp("Enugu", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Enugu</option>
                                    <option value="Gombe" <?php if (!(strcmp("Gombe", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Gombe</option><option value="Imo" <?php if (!(strcmp("Imo", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Imo</option>
                                    <option value="Jigawa" <?php if (!(strcmp("Jigawa", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Jigawa</option>
                                    <option value="Kaduna" <?php if (!(strcmp("Kaduna", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kaduna</option>
                                    <option value="Kano" <?php if (!(strcmp("Kano", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kano</option>
                                    <option value="Katsina" <?php if (!(strcmp("Katsina", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Katsina</option>
                                    <option value="Kebbi" <?php if (!(strcmp("Kebbi", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kebbi</option>
                                    <option value="Kogi" <?php if (!(strcmp("Kogi", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kogi</option>
                                    <option value="Kwara" <?php if (!(strcmp("Kwara", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Kwara</option>
                                    <option value="Lagos" <?php if (!(strcmp("Lagos", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Lagos</option>
                                    <option value="Nassarawa" <?php if (!(strcmp("Nassarawa", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Nassarawa</option>
                                    <option value="Niger" <?php if (!(strcmp("Niger", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Niger</option>
                                    <option value="Ogun" selected="selected" <?php if (!(strcmp("Ogun", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ogun</option>
                                    <option value="Ondo" <?php if (!(strcmp("Ondo", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Ondo</option>
                                    <option value="Osun" <?php if (!(strcmp("Osun", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Osun</option>
                                    <option value="Oyo" <?php if (!(strcmp("Oyo", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Oyo</option>
                                    <option value="Plateau" <?php if (!(strcmp("Plateau", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Plateau</option>
                                    <option value="Rivers" <?php if (!(strcmp("Rivers", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Rivers</option>
                                    <option value="Sokoto" <?php if (!(strcmp("Sokoto", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Sokoto</option>
                                    <option value="Taraba" <?php if (!(strcmp("Taraba", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Taraba</option>
                                    <option value="Yobe" <?php if (!(strcmp("Yobe", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Yobe</option>
                                    <option value="Zamfara" <?php if (!(strcmp("Zamfara", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Zamfara</option>
                                    <option value="Abuja" <?php if (!(strcmp("Abuja", $row_rstPersonal['StateOfOrigin']))) {echo "selected=\"selected\"";} ?>>Abuja</option>
                                  </select>
                                  
* </td>
                              </tr><tr>
                                <td class="greyBgd" align="right" height="35">Local Goverment Area (L.G.A.):</td>
                              
                                <td class="greyBgd"><input name="txtLGA" class="innerBox" id="txtLGA" value="<?php echo $row_rstPersonal['LGA']; ?>" type="text">
                                </td>
                              </tr><tr valign="top" align="left">
                                <td class="greyBgd" valign="middle" width="43%" align="right" height="35">Completed NYSC? : </td>
                                <td class="greyBgd" valign="middle" width="57%" align="left"><select name="txtNYSCCompleted" class="innerBox" id="txtNYSCCompleted">
                                  
                                  <option value="Yes" selected="selected">Yes</option>
								  <option value="No">No</option>
                                </select>
                                *                                </td>
                              </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Mobile Phone: </td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtMobPhone" class="innerBox" id="txtMobPhone" value="<?php echo $row_rstPersonal['MobilePhoneNo']; ?>" type="text"></td>
                             </tr>
                             <tr valign="middle" align="left">
                                <td class="greyBgd" width="43%" align="right" height="35">Day Phone: </td>
                                <td class="greyBgd" width="57%" align="left"><input name="txtDayPhone" class="innerBox" id="txtDayPhone" value="<?php echo $row_rstPersonal['DayPhone']; ?>" type="text">
                                <input name="dtp" id="dtp" value="<?php echo $row_rstPersonal['UserID']; ?>" type="hidden"></td>
                             </tr>
                             <tr valign="top" align="left">
                                <td colspan="2" valign="middle" align="center" height="10"><input name="Submit" onClick="location.href='mycv.php'" class="formbutton" value="Back to My CV" type="button">
                             <input name="Submit" class="formbutton" value="Update" type="submit"></td>
                              </tr>
                             <tr valign="top" align="left">
                                <td colspan="2" height="3"><img src="personal_files/spacer.gif" width="1" height="1"></td>
                             </tr>
                         </tbody></table>
                         

                            </fieldset>
                         <input type="hidden" name="MM_update" value="eduEntry">
</form>                       
                         <br>
                         <p>&nbsp;</p>
                         
                       
  <p><br>
                       </p></td></tr>
                 </tbody></table>
             </div></td>
           </tr>
         </tbody></table>
         <br>         <br>            <br>          </td>
       </tr>
     <tr>
       <td class="Content" valign="top">&nbsp;</td>
     </tr>
   </tbody></table></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top" height="1"><img name="index_r7_c1" src="personal_files/index_r7_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top" height="21"><table class="contentHeader1" width="750" border="0" cellpadding="0" cellspacing="0" height="21">
  <tbody><tr>
    <td class="rightAligned" width="10">&nbsp;</td>
    <td class="baseNavTxt">&nbsp;</td>
    <td class="leftAligned" width="12">&nbsp;</td>
  </tr>
</tbody></table>
</td>
  </tr>
  <tr>
   <td class="innerPg" valign="top" height="1"><img name="index_r9_c1" src="personal_files/index_r9_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top">&nbsp;</td>
  </tr>
</tbody></table>
</body></html>
<?php
mysql_free_result($rstPersonal);

mysql_free_result($Education);
?>