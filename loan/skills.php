<?php require_once('Connections/conn_career.php'); ?>
<?php
session_start();
if (!isset($_SESSION['UserID'])){
header("Location:index.php");}elseif (!isset($_GET['action'])){
header("Location:mycv.php");} else{
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

if ((isset($_GET['id'])) && ($_GET['id'] != "") && ($_GET['action']=="delete")) {
  $deleteSQL = sprintf("DELETE FROM tbl_skills WHERE SkillID=%s",
                       GetSQLValueString($_GET['id'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($deleteSQL, $conn_career) or die(mysql_error());
}

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_update"])) && ($_POST["Submit"] == "Save")) {
  $insertSQL = sprintf("INSERT INTO tbl_skills (Skill, `Level`, `Years of Experience`, UserID) VALUES (%s, %s, %s, %s)",
                       GetSQLValueString($_POST['txtSkill'], "text"),
                       GetSQLValueString($_POST['txtSkillLevel'], "text"),
                       GetSQLValueString($_POST['txtYearsOfExp'], "int"),
					   GetSQLValueString($_SESSION['UserID'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($insertSQL, $conn_career) or die(mysql_error());
}

if ((isset($_POST["MM_update"])) && ($_POST["Submit"] == "Update")) {
  $updateSQL = sprintf("UPDATE tbl_skills SET Skill=%s, `Level`=%s, `Years of Experience`=%s WHERE SkillID=%s",
                       GetSQLValueString($_POST['txtSkill'], "text"),
                       GetSQLValueString($_POST['txtSkillLevel'], "text"),
                       GetSQLValueString($_POST['txtYearsOfExp'], "int"),
                       //GetSQLValueString($_POST['dtp'], "int"));
					   GetSQLValueString($_GET['id'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($updateSQL, $conn_career) or die(mysql_error());
}
 ?>
<?php
$colname_personal = "-1";
if (isset($_SESSION['UserID'])) {
  $colname_personal = (get_magic_quotes_gpc()) ? $_SESSION['UserID'] : addslashes($_SESSION['UserID']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_personal = sprintf("SELECT FirstName FROM tbl_personalinfo WHERE UserID = %s", $colname_personal);
$personal = mysql_query($query_personal, $conn_career) or die(mysql_error());
$row_personal = mysql_fetch_assoc($personal);
$totalRows_personal = mysql_num_rows($personal);

$colname_skill = "-1";
if (isset($_SESSION['UserID'])) {
  $colname_skill = (get_magic_quotes_gpc()) ? $_SESSION['UserID'] : addslashes($_SESSION['UserID']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_skill = sprintf("SELECT * FROM tbl_skills WHERE UserID = %s", $colname_skill);
$skill = mysql_query($query_skill, $conn_career) or die(mysql_error());
$row_skill = mysql_fetch_assoc($skill);
$totalRows_skill = mysql_num_rows($skill);

$colname_sill_edit = "-1";
if (isset($_GET['id'])) {
  $colname_sill_edit = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_sill_edit = sprintf("SELECT * FROM tbl_skills WHERE SkillID = %s", $colname_sill_edit);
$sill_edit = mysql_query($query_sill_edit, $conn_career) or die(mysql_error());
$row_sill_edit = mysql_fetch_assoc($sill_edit);
$totalRows_sill_edit = mysql_num_rows($sill_edit);

$colname_Education = "-1";
if (isset($_SESSION['UserID'])) {
  $colname_Education = (get_magic_quotes_gpc()) ? $_SESSION['UserID'] : addslashes($_SESSION['UserID']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_Education = sprintf("SELECT * FROM tbl_education WHERE UserID = %s", $colname_Education);
$Education = mysql_query($query_Education, $conn_career) or die(mysql_error());
$row_Education = mysql_fetch_assoc($Education);
$totalRows_Education = mysql_num_rows($Education);
}
?><html><head>


<title>Careers at OOUTH</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<!--Fireworks MX 2004 Dreamweaver MX 2004 target.  Created Sat Dec 04 17:23:24 GMT+0100 2004-->
<link href="skills_files/oouth.css" rel="stylesheet" type="text/css">
<script language="JavaScript" src="skills_files/general.js" type="text/javascript"></script>
<script type="text/javascript" src="skills_files/popcalendar.js"></script></head><body><div onClick="bShow=true" id="calendar" style="z-index: 999; position: absolute; visibility: hidden;"><table style="border: 1px solid rgb(160, 160, 160); font-size: 11px; font-family: arial;" width="220" bgcolor="#ffffff"><tbody><tr bgcolor="#0000aa"><td><table width="218"><tbody><tr><td style="padding: 2px; font-family: arial; font-size: 11px;"><font color="#ffffff"><b><span id="caption"><span id="spanLeft" style="border: 1px solid rgb(51, 102, 255); cursor: pointer;" onmouseover='swapImage("changeLeft","left2.gif");this.style.borderColor="#88AAFF";window.status="Click to scroll to previous month. Hold mouse button to scroll automatically."' onClick="javascript:decMonth()" onmouseout='clearInterval(intervalID1);swapImage("changeLeft","left1.gif");this.style.borderColor="#3366FF";window.status=""' onmousedown='clearTimeout(timeoutID1);timeoutID1=setTimeout("StartDecMonth()",500)' onMouseUp="clearTimeout(timeoutID1);clearInterval(intervalID1)">&nbsp;<img id="changeLeft" src="skills_files/left1.gif" width="10" border="0" height="11">&nbsp;</span>&nbsp;<span id="spanRight" style="border: 1px solid rgb(51, 102, 255); cursor: pointer;" onmouseover='swapImage("changeRight","right2.gif");this.style.borderColor="#88AAFF";window.status="Click to scroll to next month. Hold mouse button to scroll automatically."' onmouseout='clearInterval(intervalID1);swapImage("changeRight","right1.gif");this.style.borderColor="#3366FF";window.status=""' onClick="incMonth()" onmousedown='clearTimeout(timeoutID1);timeoutID1=setTimeout("StartIncMonth()",500)' onMouseUp="clearTimeout(timeoutID1);clearInterval(intervalID1)">&nbsp;<img id="changeRight" src="skills_files/right1.gif" width="10" border="0" height="11">&nbsp;</span>&nbsp;<span id="spanMonth" style="border: 1px solid rgb(51, 102, 255); cursor: pointer;" onmouseover='swapImage("changeMonth","drop2.gif");this.style.borderColor="#88AAFF";window.status="Click to select a month."' onmouseout='swapImage("changeMonth","drop1.gif");this.style.borderColor="#3366FF";window.status=""' onClick="popUpMonth()"></span>&nbsp;<span id="spanYear" style="border: 1px solid rgb(51, 102, 255); cursor: pointer;" onmouseover='swapImage("changeYear","drop2.gif");this.style.borderColor="#88AAFF";window.status="Click to select a year."' onmouseout='swapImage("changeYear","drop1.gif");this.style.borderColor="#3366FF";window.status=""' onClick="popUpYear()"></span>&nbsp;</span></b></font></td><td align="right"><a href="javascript:hideCalendar()"><img src="skills_files/close.gif" alt="Close the Calendar" width="15" border="0" height="13"></a></td></tr></tbody></table></td></tr><tr><td style="padding: 5px;" bgcolor="#ffffff"><span id="content"></span></td></tr><tr bgcolor="#f0f0f0"><td style="padding: 5px;" align="center"><span id="lblToday">Today is <a onmousemove='window.status="Go To Current Month"' onmouseout='window.status=""' title="Go To Current Month" style="text-decoration: none; color: black;" href="javascript:monthSelected=monthNow;yearSelected=yearNow;constructCalendar();">Wed, 8 Jun	2011</a></span></td></tr></tbody></table></div><div id="selectMonth" style="z-index: 999; position: absolute; visibility: hidden;"></div><div id="selectYear" style="z-index: 999; position: absolute; visibility: hidden;"></div>



<table width="100%" border="0" cellpadding="0" cellspacing="0" height="100%">
<!-- fwtable fwsrc="MTN4U.png" fwbase="index.jpg" fwstyle="Dreamweaver" fwdocid = "1226677029" fwnested="0" -->
  <tbody><tr>
   <td><img src="skills_files/spacer.gif" alt="" width="750" border="0" height="1"></td>
  </tr>

  <tr>
   <td class="centerAligned" valign="top" height="100"><div align="center"></div>
<table width="750" border="0" cellpadding="0" cellspacing="0">
<!-- fwtable fwsrc="Untitled" fwbase="top.gif" fwstyle="Dreamweaver" fwdocid = "2000728079" fwnested="0" -->
  <tbody><tr>
   <td><img src="skills_files/spacer.gif" alt="" width="7" border="0" height="1"></td>
   <td><img src="skills_files/spacer.gif" alt="" width="78" border="0" height="1"></td>
   <td><img src="skills_files/spacer.gif" alt="" width="491" border="0" height="1"></td>
   <td><img src="skills_files/spacer.gif" alt="" width="153" border="0" height="1"></td>
   <td><img src="skills_files/spacer.gif" alt="" width="21" border="0" height="1"></td>
   <td><img src="skills_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
  </tr>

  <tr>
   <td colspan="5"><img name="top_r1_c1" src="skills_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
   <td><img src="skills_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
  </tr>
  <tr>
   <td rowspan="4"><img name="top_r2_c1" src="skills_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
    <td rowspan="4"><a href="http://www.oouth.com/"><img src="skills_files/oouthLogo.gif" width="79" border="0" height="80"></a></td>
    <td colspan="2" rowspan="4" align="right"><img src="skills_files/careers_at_oouth.gif" width="300" height="40"><img name="top_r4_c4" src="skills_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
    <td>&nbsp;</td>
   <td><img src="skills_files/spacer.gif" alt="" width="1" border="0" height="17"></td>
  </tr>
  <tr>
   <td rowspan="3"><img name="top_r3_c5" src="skills_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
   <td><img src="skills_files/spacer.gif" alt="" width="1" border="0" height="37"></td>
  </tr>
  <tr>
   <td><img src="skills_files/spacer.gif" alt="" width="1" border="0" height="25"></td>
  </tr>
  <tr>
   <td><img src="skills_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
  </tr>
</tbody></table>

</td>
  </tr>
  <tr>
   <td class="mainNav" valign="top" height="21"><table width="750" border="0" cellpadding="0" cellspacing="0" height="21">
     <tbody><tr>
       <td class="mainNavTxt" valign="bottom">&nbsp;</td>
       <td class="leftAligned" width="12">&nbsp;</td>
     </tr>
   </tbody></table>
</td>
  </tr>
  <tr>
   <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r3_c1" src="skills_files/index_r3_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="globalNav" valign="top" height="25"><table width="750" border="0" cellpadding="0" cellspacing="0" height="21">
     <tbody><tr>
       <td class="rightAligned" width="10"><img src="skills_files/spacer.gif" width="1" height="1"></td>
       <td><img src="skills_files/spacer.gif" width="6"></td>
       <td class="leftAligned" width="12"><img src="skills_files/spacer.gif" width="1" height="1"></td>
     </tr>
   </tbody></table>

</td>
  </tr>
  <tr>
   <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r5_c1" src="skills_files/index_r5_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top"><table width="750" border="0" cellpadding="0" cellspacing="0">
     <tbody><tr>
       <td rowspan="2" width="8"><img src="skills_files/spacer.gif" width="1" height="1"></td>
       <td colspan="2" class="breadcrumbs" valign="bottom" height="20"><a href="http://careers.mtnonline.com/index.asp">Home</a> / <a href="index.php">My CV</a> / Skills</td>
       <td rowspan="2" width="12"><img src="skills_files/spacer.gif" width="1" height="1"></td>
     </tr>
     <tr>
       <td class="Content" valign="top" width="180">

<p>&nbsp;</p><br>

<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr> 
    <td class="sidenavtxt" align=""> <em><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Welcome,</font></em> 
      <font size="1" face="Verdana, Arial, Helvetica, sans-serif"><span><?php echo $row_personal['FirstName']; ?><br> 
<img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0">		  
<a href="changepasswd.php">Change Password</a> <br> 
<img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0">
<a href="personal.php?action=edit">Edit Details</a> <br> 
<img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0">		  
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
    <td align=""><img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" width="100%" align=""><a href="http://careers.mtnonline.com/myapplications.asp">My Applications</a> </td>
  </tr>
  
</tbody></table>
<br>

<br>
<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr>
    <td colspan="2" class="sidenavtxt" align=""><p><a href="index.php">View My CV</a><img src="skills_files/spacer.gif" width="8" height="8">
        
        <font color="#009966"><?php if (($totalRows_Education > 0) && ($totalRows_personal > 0) ) {echo "<IMG alt=\"CV Completed\" align=absMiddle src=\"mycv_files\/cv_completed.gif\" width=16 height=12>" ; } else {echo "<IMG alt=\"CV Incompleted\" align=absMiddle                   src=\"mycv_files\/cv_uncompleted.gif\" width=16 height=12>" ; }?></font>
        
<br>
        
      </p>
    </td>
  </tr>


  <tr valign="top">
    <td align=""><img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" width="100%" align=""> <a href="personal.php">Personal Information </a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="beneficiary.php?action=add">Educational History</a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="workhistory.php?action=add">Work Experience</a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="profcert.php?action=add">Professional Certifications</a></td>
  </tr>
  <tr valign="top">
    <td align=""><img src="skills_files/spacer.gif" width="1" border="0" height="8"><img src="skills_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" align=""> <a href="skills.php?action=add">Skills</a><br>
    <br></td>
  </tr>
  <tr>
  
    <td colspan="2" class="legend" align="">Legend<em><br>      
      <img src="skills_files/cv_completed.gif" alt="CV Completed" width="9" align="absmiddle" height="8">-Complete<img src="skills_files/spacer.gif" width="8" height="8"> 
      <font color="#009966"><img src="skills_files/cv_uncompleted.gif" alt="CV Completed" width="9" align="absmiddle" height="8"></font>-Incomplete </em></td>
  </tr>
</tbody></table>

<br>
<script language="JavaScript1.2" src="skills_files/misc.htm"></script>
</td>
       <td rowspan="2" class="Content" valign="top"><img src="skills_files/mycv.gif" width="350" height="30"> <hr size="1" width="500" align="left" color="#cccccc">
         <table width="500" border="0" cellpadding="0" cellspacing="0">
           <tbody><tr>
             <td class="toplinks2" valign="top"><div align="justify">
                 <table class="Content" width="100%" border="0" cellpadding="4" cellspacing="0">
                   <tbody><tr>
                     <td valign="top"><span class="homeContentSmaller">
                       
                       </span>
                        <form action="<?php echo $editFormAction; ?>" method="POST" name="eduEntry" onSubmit="YY_checkform('eduEntry','txtSkill','#q','0','Field Skills\' is not valid.','txtYearsOfExp','#q','0','Field Year of Experience is not valid.','txtSkillLevel','#q','1','Field Skills Level is not valid.');return document.MM_returnValue">
                         <fieldset>
                         <legend class="contentHeader1">Skills<a name="top"></a></legend>
                         <table width="96%" align="center" cellpadding="4" cellspacing="0">
                         <tbody><tr valign="top" align="left">
                           <td colspan="2" height="1"><img src="skills_files/spacer.gif" width="1" height="1"></td>
                         </tr>
                         <tr valign="top" align="left">
                           <td class="greyBgd" width="43%" align="right" height="35">Skill: </td>
                           <td class="greyBgd" width="57%" align="left">
<input name="txtSkill" type="text" class="innerBox" id="txtSkill" value="<?php echo $row_sill_edit['Skill']; ?>">
*</td>
                         </tr>
                         <tr valign="top" align="left">
                           <td class="greyBgd" width="43%" align="right" height="35">Level:</td>
                           <td class="greyBgd" width="57%" align="left">
                             <select name="txtSkillLevel" class="innerBox" id="txtSkillLevel">
                               <option value="" <?php if (!(strcmp("", $row_sill_edit['Level']))) {echo "selected=\"selected\"";} ?>>Select ...</option>
                               <option value="1" <?php if (!(strcmp(1, $row_sill_edit['Level']))) {echo "selected=\"selected\"";} ?>>Beginner</option>
                               <option value="2" <?php if (!(strcmp(2, $row_sill_edit['Level']))) {echo "selected=\"selected\"";} ?>>Intermediate</option>
                               <option value="3" <?php if (!(strcmp(3, $row_sill_edit['Level']))) {echo "selected=\"selected\"";} ?>>Expert</option>
                             </select>
                             
* </td>
                           </tr>
                         <tr valign="top" align="left">
                           <td class="greyBgd" width="43%" align="right" height="35">Years of Experience:</td>
                           <td class="greyBgd" width="57%" align="left"><input name="txtYearsOfExp" type="text" class="innerBox" id="txtYearsOfExp" value="<?php echo $row_sill_edit['Years of Experience']; ?>"> 
                             * 
                               <input name="dtp" id="dtp" value="<?php echo $row_sill_edit['SkillID']; ?>" type="hidden"></td>
                         </tr>
                         <tr valign="top" align="center">
                           <td colspan="2" valign="middle" height="10"><input name="Submit" onClick="location.href='mycv.php'" class="formbutton" value="Back to My CV" type="button">
                             <input name="Submit" class="formbutton" value=<?php if (($_GET['action'])== "edit") { echo "Update" ; } else {echo "Save"; }?> type="submit"></td>
                           </tr>
                         <tr valign="top" align="left">
                           <td colspan="2" height="3"><img src="skills_files/spacer.gif" width="1" height="1"></td>
                         </tr>
                       </tbody></table>
                       </fieldset>
                         <input type="hidden" name="MM_update" value=<?php if (($_GET['action'])== "update") { echo "edit" ; } else {echo "insert"; }?> >
                        </form>
					   
                
                         <br><fieldset>
                         <legend class="contentHeader1">Skills  Preview </legend>
                          
<script language="JavaScript" type="text/JavaScript">
<!--
function GP_popupConfirmMsg(msg) { //v1.0
  document.MM_returnValue = confirm(msg);
}
//-->
</script>



  <table width="96%" align="center" cellpadding="4" cellspacing="0">
      <tbody>
        <tr valign="top" align="right">
          <td class="content" align="left" height="1"><a href="skills.php?action=add">Add</a></td>
            <td colspan="4" class="content" align="right" height="1"><a href="#top">Top</a></td>
          </tr>
          <tr valign="top">
            <td class="greyBgdHeader" valign="middle" width="0" height="35"><strong>Skill</strong></td>
            <td class="greyBgdHeader" valign="middle"><strong>Level</strong></td>
            <td class="greyBgdHeader" valign="middle" width="0"><strong>Years of Experience</strong></td>
            <td colspan="2" class="greyBgdHeader" valign="middle">&nbsp;</td>
          </tr>
        <?php do { ?>
          <tr valign="top">
            
              <td class="greyBgd" valign="middle" height="35"><?php echo $row_skill['Skill']; ?></td>
              <td class="greyBgd" valign="middle"><?php echo $row_skill['Level']; ?></td>
              <td class="greyBgd" valign="middle"><?php echo $row_skill['Years of Experience']; ?></td>
            <?php if ($totalRows_skill > 0) { // Show if recordset not empty ?>  <td class="greyBgd" valign="middle"><a href="skills.php?action=edit&id=<?php echo $row_skill['SkillID']; ?>">Edit</a></td>
              <td class="greyBgd" valign="middle"><a href="skills.php?action=delete&amp;id=<?php echo $row_skill['SkillID']; ?>" onClick="GP_popupConfirmMsg('Are you sure you want to delete this entry?\rTo continue, click \'Ok\' otherwise, click \'Cancel\'');return document.MM_returnValue">Delete</a></td><?php } // Show if recordset not empty ?>
              
          </tr>
        <?php } while ($row_skill = mysql_fetch_assoc($skill)); ?>
        
          <tr valign="top" align="left">
            <td colspan="5" class="Content" align="right" height="3"><p><img src="skills_files/spacer.gif" width="1" height="1"></p></td>
          </tr>
        </tbody>
  </table>
  </fieldset>
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
   <td class="innerPg" valign="top" height="1"><img name="index_r7_c1" src="skills_files/index_r7_c1.jpg" alt="" width="750" border="0" height="1"></td>
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
   <td class="innerPg" valign="top" height="1"><img name="index_r9_c1" src="skills_files/index_r9_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top">&nbsp;</td>
  </tr>
</tbody></table>
</body></html>
<?php
mysql_free_result($personal);

mysql_free_result($skill);

mysql_free_result($sill_edit);

mysql_free_result($Education);
?>