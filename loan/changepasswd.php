<?php require_once('../Connections/conn_career.php'); ?>
<?php
session_start();
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



if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1") && ($_POST['dtp'] == $_POST['txtCurrPasswd'])) {
  $updateSQL = sprintf("UPDATE logininfo SET password=%s WHERE userid=%s",
                       GetSQLValueString($_POST['txtNewPasswd'], "text"),
                       GetSQLValueString($_SESSION['UserID'], "int"));

  mysql_select_db($database_conn_career, $conn_career);
  $Result1 = mysql_query($updateSQL, $conn_career) or die(mysql_error());
}else {$error = "Error";}


$colname_Personal = "-1";
if (isset($_SESSION['UserID'])) {
  $colname_Personal = (get_magic_quotes_gpc()) ? $_SESSION['UserID'] : addslashes($_SESSION['UserID']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_Personal = sprintf("SELECT FirstName FROM tbl_personalinfo WHERE UserID = %s", $colname_Personal);
$Personal = mysql_query($query_Personal, $conn_career) or die(mysql_error());
$row_Personal = mysql_fetch_assoc($Personal);
$totalRows_Personal = mysql_num_rows($Personal);

$colname_Education = "-1";
if (isset($_SESSION['UserID'])) {
  $colname_Education = (get_magic_quotes_gpc()) ? $_SESSION['UserID'] : addslashes($_SESSION['UserID']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_Education = sprintf("SELECT * FROM tbl_education WHERE UserID = %s", $colname_Education);
$Education = mysql_query($query_Education, $conn_career) or die(mysql_error());
$row_Education = mysql_fetch_assoc($Education);
$totalRows_Education = mysql_num_rows($Education);

$colname_loginInfo = "-1";
if (isset($_SESSION['UserID'])) {
  $colname_loginInfo = (get_magic_quotes_gpc()) ? $_SESSION['UserID'] : addslashes($_SESSION['UserID']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_loginInfo = sprintf("SELECT * FROM logininfo WHERE userid = %s", $colname_loginInfo);
$loginInfo = mysql_query($query_loginInfo, $conn_career) or die(mysql_error());
$row_loginInfo = mysql_fetch_assoc($loginInfo);
$totalRows_loginInfo = mysql_num_rows($loginInfo);
?><html><head>


<title>Careers at OOUTH</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<!--Fireworks MX 2004 Dreamweaver MX 2004 target.  Created Sat Dec 04 17:23:24 GMT+0100 2004-->
<link href="changepasswd_files/oouth.css" rel="stylesheet" type="text/css">
<script language="JavaScript" src="changepasswd_files/general.js" type="text/javascript"></script>


</head><body>
<table width="100%" border="0" cellpadding="0" cellspacing="0" height="100%">
<!-- fwtable fwsrc="MTN4U.png" fwbase="index.jpg" fwstyle="Dreamweaver" fwdocid = "1226677029" fwnested="0" -->
  <tbody><tr>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="750" border="0" height="1"></td>
  </tr>

  <tr>
   <td class="centerAligned" valign="top" height="100"><div align="center"></div>
<table width="750" border="0" cellpadding="0" cellspacing="0">
<!-- fwtable fwsrc="Untitled" fwbase="top.gif" fwstyle="Dreamweaver" fwdocid = "2000728079" fwnested="0" -->
  <tbody><tr>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="7" border="0" height="1"></td>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="78" border="0" height="1"></td>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="491" border="0" height="1"></td>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="153" border="0" height="1"></td>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="21" border="0" height="1"></td>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
  </tr>

  <tr>
   <td colspan="5"><img name="top_r1_c1" src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
  </tr>
  <tr>
   <td rowspan="4"><img name="top_r2_c1" src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
    <td rowspan="4"><a href="http://www.oouth.com/"><img src="changepasswd_files/oouthLogo.gif" width="79" border="0" height="80"></a></td>
    <td colspan="2" rowspan="4" align="right"><img src="changepasswd_files/careers_at_oouth.gif" width="300" height="40"><img name="top_r4_c4" src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
    <td>&nbsp;</td>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="17"></td>
  </tr>
  <tr>
   <td rowspan="3"><img name="top_r3_c5" src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="37"></td>
  </tr>
  <tr>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="25"></td>
  </tr>
  <tr>
   <td><img src="changepasswd_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
  </tr>
</tbody></table>

</td>
  </tr>
  <tr>
   <td class="mainNav" valign="top" height="21"><table width="750" border="0" cellpadding="0" cellspacing="0" height="21">
     <tbody><tr>
       <td class="rightAligned" width="10">&nbsp;</td>
       <td class="mainNavTxt" valign="bottom">&nbsp;</td>
       <td class="leftAligned" width="12">&nbsp;</td>
     </tr>
   </tbody></table>
</td>
  </tr>
  <tr>
   <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r3_c1" src="changepasswd_files/index_r3_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="globalNav" valign="top" height="25"><table width="750" border="0" cellpadding="0" cellspacing="0" height="21">
     <tbody><tr>
       <td class="rightAligned" width="10"><img src="changepasswd_files/spacer.gif" width="1" height="1"></td>
       <td><img src="changepasswd_files/spacer.gif" width="6"></td>
       <td class="leftAligned" width="12"><img src="changepasswd_files/spacer.gif" width="1" height="1"></td>
     </tr>
   </tbody></table>

</td>
  </tr>
  <tr>
   <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r5_c1" src="changepasswd_files/index_r5_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top"><table width="750" border="0" cellpadding="0" cellspacing="0">
     <tbody><tr>
       <td rowspan="2" width="8"><img src="changepasswd_files/spacer.gif" width="1" height="1"></td>
       <td colspan="2" class="breadcrumbs" valign="bottom" height="20"><a href="http://careers.oouth.com/index.asp">Home</a> / Change Password</td>
       <td rowspan="2" width="12"><img src="changepasswd_files/spacer.gif" width="1" height="1"></td>
     </tr>
     <tr>
       <td class="Content" valign="top" width="180">

<p>&nbsp;</p><br>

<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr> 
    <td class="sidenavtxt" align=""> <em><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Welcome,</font></em> 
      <font size="1" face="Verdana, Arial, Helvetica, sans-serif"><span> 
      <?php echo $row_Personal['FirstName']; ?> <br> 
<img src="changepasswd_files/spacer.gif" width="1" border="0" height="8"><img src="changepasswd_files/arrow_bullets2.gif" border="0">		  
<a href="changepasswd.php">Change Password</a> <br> 
<img src="changepasswd_files/spacer.gif" width="1" border="0" height="8"><img src="changepasswd_files/arrow_bullets2.gif" border="0">
<a href="personal.php">Edit Details</a> <br> 
<img src="changepasswd_files/spacer.gif" width="1" border="0" height="8"><img src="changepasswd_files/arrow_bullets2.gif" border="0">		  
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
    <td align=""><img src="changepasswd_files/spacer.gif" width="1" border="0" height="8"><img src="changepasswd_files/arrow_bullets2.gif" border="0"></td>
    <td class="sidenavtxt" width="100%" align=""><a href="http://careers.mtnonline.com/myapplications.asp">My Applications</a> </td>
  </tr>
  
</tbody></table>
<br>

<br>
<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr>
    <td colspan="2" class="sidenavtxt" align=""><p><a href="index.php">View My CV</a><img src="changepasswd_files/spacer.gif" width="8" height="8">
        
        <font color="#009966"><?php if (($totalRows_Education > 0) && ($totalRows_Personal > 0) ) {echo "<IMG alt=\"CV Completed\" align=absMiddle src=\"mycv_files\/cv_completed.gif\" width=16 height=12>" ; } else {echo "<IMG alt=\"CV Incompleted\" align=absMiddle                   src=\"mycv_files\/cv_uncompleted.gif\" width=16 height=12>" ; }?></font>
        
<br>
        
      </p>
    </td>
  </tr>

    <tr><td colspan="2" class="legend" align="">Legend<em><br>      
      <img src="changepasswd_files/cv_completed.gif" alt="CV Completed" width="9" align="absmiddle" height="8">-Complete<img src="changepasswd_files/spacer.gif" width="8" height="8"> 
      <font color="#009966"><img src="changepasswd_files/cv_uncompleted.gif" alt="CV Completed" width="9" align="absmiddle" height="8"></font>-Incomplete </em></td>
  </tr>
</tbody></table>

<br>
<script language="JavaScript1.2" src="changepasswd_files/misc.htm"></script>

</td>
       <td rowspan="2" class="Content" valign="top"><img src="changepasswd_files/changepasswd.gif" width="350" height="30"> <hr size="1" width="400" align="left" color="#cccccc">
         <table width="400" border="0" cellpadding="0" cellspacing="0">
           <tbody><tr>
             <td class="toplinks2" valign="top"><div align="justify">
                 <table class="Content" width="100%" border="0" cellpadding="4" cellspacing="0">
                   <tbody><tr>
                     <td valign="top"><span class="homeContentSmaller">
                       <?php if(isset($_POST["MM_update"])){if (isset($error)) { echo "<table class=\"errorBox\" width=\"500\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
  <tbody><tr>
    <td>There was an Error Changing your Password !!! </td>
  </tr>
</tbody></table>" ;} else { echo "<table class=\"errorBox\" width=\"500\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">
  <tbody><tr>
    <td>Your update was successful !!! </td>
  </tr>
</tbody></table>" ;}}?>
                       </span>
                       <form action="<?php echo $editFormAction; ?>" method="POST" name="form1" onSubmit="YY_checkform('form1','txtCurrPasswd','#q','0','Please enter current password','txtNewPasswd','#q','0','Please enter new password','txtConfirmNewPasswd','#txtNewPasswd','6','Passwords do not match');return document.MM_returnValue">
                       <fieldset>
                         <legend class="contentHeader1">Change Password</legend>
                         <table width="96%" align="center" cellpadding="4" cellspacing="0">
                         <tbody><tr valign="top" align="left">
                             <td colspan="2" class="Content" height="1"><img src="changepasswd_files/spacer.gif" width="1" height="1">Fields marked * are compulsory</td>
                         </tr>
                           <tr valign="middle" align="left">
                             <td class="greyBgd" width="43%" align="right" height="35">Current Password : </td>
                             <td class="greyBgd" width="57%" align="left"><input name="txtCurrPasswd" class="innerBox" id="txtCurrPasswd" type="password">
        *</td>
                           </tr>
                           <tr valign="middle" align="left">
                             <td class="greyBgd" width="43%" align="right" height="35">New Password : </td>
                             <td class="greyBgd" width="57%" align="left"><input name="txtNewPasswd" class="innerBox" id="txtNewPasswd" type="password">
        * </td>
                           </tr>
                           <tr valign="middle" align="left">
                             <td class="greyBgd" width="43%" align="right" height="35">Confirm New Password: </td>
                             <td class="greyBgd" width="57%" align="left"><input name="txtConfirmNewPasswd" class="innerBox" id="txtConfirmNewPasswd" type="password">
        *
          <input name="dtp" id="dtp" value="<?php echo $row_loginInfo['password']; ?>" type="hidden"></td>
                           </tr>
                           <tr valign="top" align="left">
                             <td valign="middle" height="10">&nbsp;</td>
                             <td valign="middle"><input name="Submit" class="formbutton" value="Change Password" type="submit"></td>
                           </tr>
                           <tr valign="top" align="left">
                             <td colspan="2" height="3"><img src="changepasswd_files/spacer.gif" width="1" height="1"></td>
                           </tr>
                         </tbody></table>
                       </fieldset>
                       <input type="hidden" name="MM_update" value="form1">
                       </form>
                     <p>&nbsp;</p>
                         <p>&nbsp;</p></td></tr>
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
   <td class="innerPg" valign="top" height="1"><img name="index_r7_c1" src="changepasswd_files/index_r7_c1.jpg" alt="" width="750" border="0" height="1"></td>
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
   <td class="innerPg" valign="top" height="1"><img name="index_r9_c1" src="changepasswd_files/index_r9_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top">&nbsp;</td>
  </tr>
</tbody></table>
</body></html>
<?php
mysql_free_result($Personal);

mysql_free_result($Education);

mysql_free_result($loginInfo);
?>