<?php require_once('Connections/conn_career.php'); ?>
<?php
$colname_vacancyShow = "-1";
if (isset($_GET['id'])) {
  $colname_vacancyShow = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}
mysql_select_db($database_conn_career, $conn_career);
$query_vacancyShow = sprintf("SELECT * FROM tbl_vacancy WHERE id = %s", $colname_vacancyShow);
$vacancyShow = mysql_query($query_vacancyShow, $conn_career) or die(mysql_error());
$row_vacancyShow = mysql_fetch_assoc($vacancyShow);
$totalRows_vacancyShow = mysql_num_rows($vacancyShow);
?><html><head>


<title>Careers at MTN Nigeria</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<!--Fireworks MX 2004 Dreamweaver MX 2004 target.  Created Sat Dec 04 17:23:24 GMT+0100 2004-->
<link href="vacancies_show_files/oouth.css" rel="stylesheet" type="text/css">
<script language="JavaScript" src="vacancies_show_files/general.js" type="text/javascript"></script>


<script language="JavaScript" type="text/JavaScript">
<!--
function MM_jumpMenuGo(selName,targ,restore){ //v3.0
  var selObj = MM_findObj(selName); if (selObj) MM_jumpMenu(targ,selObj,restore);
}
//-->
</script>
</head><body>
<table width="100%" border="0" cellpadding="0" cellspacing="0" height="100%">
<!-- fwtable fwsrc="MTN4U.png" fwbase="index.jpg" fwstyle="Dreamweaver" fwdocid = "1226677029" fwnested="0" -->
  <tbody><tr>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="750" border="0" height="1"></td>
  </tr>

  <tr>
   <td class="centerAligned" valign="top" height="100"><div align="center"></div>
<table width="750" border="0" cellpadding="0" cellspacing="0">
<!-- fwtable fwsrc="Untitled" fwbase="top.gif" fwstyle="Dreamweaver" fwdocid = "2000728079" fwnested="0" -->
  <tbody><tr>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="7" border="0" height="1"></td>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="78" border="0" height="1"></td>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="491" border="0" height="1"></td>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="153" border="0" height="1"></td>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="21" border="0" height="1"></td>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
  </tr>

  <tr>
   <td colspan="5"><img name="top_r1_c1" src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
  </tr>
  <tr>
   <td rowspan="4"><img name="top_r2_c1" src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
    <td rowspan="4"><a href="http://www.mtnonline.com/"><img src="vacancies_show_files/oouthLogo.gif" width="79" border="0" height="80"></a></td>
    <td colspan="2" rowspan="4" align="right"><img src="vacancies_show_files/careers_at_oouth.gif" width="300" height="40"><img name="top_r4_c4" src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
    <td>&nbsp;</td>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="17"></td>
  </tr>
  <tr>
   <td rowspan="3"><img name="top_r3_c5" src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="1"></td>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="37"></td>
  </tr>
  <tr>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="25"></td>
  </tr>
  <tr>
   <td><img src="vacancies_show_files/spacer.gif" alt="" width="1" border="0" height="11"></td>
  </tr>
</tbody></table>

</td>
  </tr>
  <tr>
    <td class="mainNav" valign="top" height="21">&nbsp;</td>
  </tr>
  <tr>
   <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r3_c1" src="vacancies_show_files/index_r3_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="globalNav" valign="top" height="25"><table width="750" border="0" cellpadding="0" cellspacing="0" height="21">
     <tbody><tr>
       <td class="rightAligned" width="10"><img src="vacancies_show_files/spacer.gif" width="1" height="1"></td>
       <td><img src="vacancies_show_files/spacer.gif" width="6"></td>
       <td class="leftAligned" width="12"><img src="vacancies_show_files/spacer.gif" width="1" height="1"></td>
     </tr>
   </tbody></table>

</td>
  </tr>
  <tr>
   <td class="dividerCenterAligned" valign="top" height="1"><img name="index_r5_c1" src="vacancies_show_files/index_r5_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top"><table width="750" border="0" cellpadding="0" cellspacing="0">
     <tbody><tr>
       <td rowspan="2" width="8"><img src="vacancies_show_files/spacer.gif" width="1" height="1"></td>
       <td colspan="2" class="breadcrumbs" valign="bottom" height="20"><a href="http://careers.mtnonline.com/index.asp">Home</a> / Vacancies </td>
       <td rowspan="2" width="12"><img src="vacancies_show_files/spacer.gif" width="1" height="1"></td>
     </tr>
     <tr>
       <td class="Content" valign="top" width="180">

<p>&nbsp;</p><br>

<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr> 
    <td class="sidenavtxt" align=""> <em><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Welcome,</font></em> 
      <font size="1" face="Verdana, Arial, Helvetica, sans-serif"><span> 
      Guest
      </span></font> </td>
  </tr>
</tbody></table>
<br>
<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr>
    <td colspan="2" class="sidenavtxt" width="100%" align=""><p><a href="vacancies.php">View Vacancies</a> <br>
    </p></td>
  </tr>
  
</tbody></table>
<br>

<table class="innerWhiteBox" width="96%" border="0" cellpadding="4" cellspacing="0">
  <tbody><tr>
    <td class="sidenavtxt" width="100%" align=""><p>To <a href="http://careers.mtnonline.com/loginuser.asp">login</a>, click here<br>
      <br>
      Not registered? To <a href="http://careers.mtnonline.com/register.asp">register</a>, click here<br>
        
      </p>
    </td>
  </tr>
</tbody></table>

<br>
<script language="JavaScript1.2" src="vacancies_show_files/misc.htm"></script>

</td>
       <td rowspan="2" class="Content" valign="top"><img src="vacancies_show_files/vacancies.gif" width="350" height="30"> <hr size="1" width="500" align="left" color="#cccccc">
         <table width="500" border="0" cellpadding="0" cellspacing="0">
           <tbody><tr>
             <td class="toplinks2" valign="top"><div align="justify">
                 <table class="Content" width="100%" border="0" cellpadding="4" cellspacing="0">
                   <tbody><tr>
                     <td valign="top">
					 
<p>Welcome to our vacancies section, to view vacancies by departments, use the form below:</p>
                       
                       
                       <form name="form1" method="post" action="">
                         <table class="dataBox" width="98%" align="center" border="0" cellpadding="4" cellspacing="0">
                           <tbody><tr>
                             <td valign="middle" align="center"><strong>Select Department</strong><img src="vacancies_show_files/spacer.gif" width="10" border="0" height="8"><img src="vacancies_show_files/spacer.gif" width="10" border="0" height="8">                               <select name="menu1" class="innerBox" onChange="MM_jumpMenu('parent',this,0)">
                                 <option value="vacancies.asp?deptid=0" selected="selected">All</option>
                                 
<option value="vacancies.asp?deptid=1">Capital Programs Group</option>
                               
<option value="vacancies.asp?deptid=11">CEO's Office</option>
                               
<option value="vacancies.asp?deptid=2" selected="selected">Corporate Services</option>
                               
<option value="vacancies.asp?deptid=3">Customer Relations</option>
                               
<option value="vacancies.asp?deptid=12">Enterprise Solutions</option>
                               
<option value="vacancies.asp?deptid=4">Finance</option>
                               
<option value="vacancies.asp?deptid=5">Human Resources</option>
                               
<option value="vacancies.asp?deptid=7">Information Systems</option>
                               
<option value="vacancies.asp?deptid=6">Internal Audit</option>
                               
<option value="vacancies.asp?deptid=8">Marketing and Strategy</option>
                               
<option value="vacancies.asp?deptid=9">Network Group</option>
                               
<option value="vacancies.asp?deptid=10">Sales and Distribution</option>
                               </select>
                               <img src="vacancies_show_files/spacer.gif" width="10" border="0" height="8">
                               <input name="Button1" class="formbutton" onClick="MM_jumpMenuGo('menu1','parent',0)" value="Go" type="button">
                               
</td>
                             </tr>
                         </tbody></table>
                       </form>
                       
                       <fieldset>
                       <legend class="contentHeader1">Vacancy Details </legend>
                       <table width="96%" align="center" cellpadding="4" cellspacing="0">
                         <tbody><tr valign="top" align="left">
                           <td colspan="2" class="Content" height="1"><img src="vacancies_show_files/spacer.gif" width="1" height="1"></td>
                         </tr>
                         
                         <tr valign="top" align="left">
                             <td colspan="2" class="greyBgdHeader" valign="middle" height="35"><table class="Content" width="100%" border="0" cellpadding="2" cellspacing="0">
                                  <tbody><tr>
<td width="100%">
<a href="#"><img src="vacancies_show_files/print.gif" onClick="MM_openBrWindow('printpreview.asp?deptid=2&amp;id=1654','printpreview','scrollbars=yes,width=600,height=600')" width="35" border="0" height="15"></a><img src="vacancies_show_files/spacer.gif" width="10" border="0" height="8"><a href="#"><img onClick="MM_openBrWindow('email2friend.asp?deptid=2&amp;id=1654','email2friend','scrollbars=yes,width=600,height=600')" src="vacancies_show_files/email2friend.gif" width="100" border="0" height="15"></a> </td>
<td>
<a href="#"><img src="vacancies_show_files/apply.gif" onClick="MM_openBrWindow('applyonline.asp?deptid=2&amp;id=1654','apply','scrollbars=yes,width=600,height=600')" width="44" border="0" height="15"></a></td>
                                  </tr>
                             </tbody></table></td>
                         </tr>
                         
                         <tr valign="middle" align="left">
                             <td class="greyBgd" width="43%" height="35"><strong>Job Title</strong><br></td>
                             <td class="greyBgd" width="57%" align="left"><strong><?php echo $row_vacancyShow['JobTitle']; ?></strong></td>
                         </tr>                         
                         <tr valign="middle" align="left">
                             <td class="greyBgd" width="43%" height="35">Department:<br></td>
                             <td class="greyBgd" width="57%" align="left"><?php echo $row_vacancyShow['Dept']; ?></td>
                         </tr>                   
                         <tr valign="top" align="left">
                             <td class="greyBgd" width="43%" height="35">Qualification: <br></td>
                             <td class="greyBgd" width="57%" align="left"><?php echo $row_vacancyShow['Qualification']; ?></td>
                         </tr>
                         <tr valign="top" align="left">
                           <td class="greyBgd" height="35">Job Conditions:</td>
                           <td class="greyBgd" align="left"><?php echo $row_vacancyShow['ConditionOfService']; ?></td>
                         </tr>                      
                         <tr valign="top" align="left">
                             <td class="greyBgd" width="43%" height="35">Salary:<br></td>
                             <td class="greyBgd" width="57%" align="left"><?php echo $row_vacancyShow['Salary']; ?></td>
                         </tr>                         
                         <tr valign="top" align="left">
                             <td class="greyBgd" width="43%" height="35">Required Skills: <br></td>
                             <td class="greyBgd" width="57%" align="left"><?php echo $row_vacancyShow['RequiredSkills']; ?></td>
                         </tr>                         
                         <tr valign="top" align="right">
                             <td colspan="2" class="greyBgd" height="35"><br>
                              This vacancy expires on <?php echo $row_vacancyShow['ExpiryDate']; ?> </td>
                           </tr>
						   

                         <tr valign="top" align="left">
                           <td colspan="2" class="Content" height="3"><img src="vacancies_show_files/spacer.gif" width="1" height="1"><a href="javascript:history.back(1)"><img src="vacancies_show_files/prev.gif" width="120" border="0" height="15"></a></td>
                         </tr>
                       </tbody></table>
                       </fieldset>
                       

                       <p><br>
                       </p></td>
                   </tr>
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
   <td class="innerPg" valign="top" height="1"><img name="index_r7_c1" src="vacancies_show_files/index_r7_c1.jpg" alt="" width="750" border="0" height="1"></td>
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
   <td class="innerPg" valign="top" height="1"><img name="index_r9_c1" src="vacancies_show_files/index_r9_c1.jpg" alt="" width="750" border="0" height="1"></td>
  </tr>
  <tr>
   <td class="innerPg" valign="top">&nbsp;</td>
  </tr>
</tbody></table>
</body></html>
<?php
mysql_free_result($vacancyShow);
?>