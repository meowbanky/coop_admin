<?php require_once('../Connections/conn_career.php'); ?>
<?php
// *** Validate request to login to this site.
if (!isset($_SESSION)) {
  session_start();
}

$loginFormAction = $_SERVER['PHP_SELF'];
if (isset($_GET['accesscheck'])) {
  $_SESSION['PrevUrl'] = $_GET['accesscheck'];
}

if (isset($_POST['uname'])) {
  $loginUsername=$_POST['uname'];
  $password=$_POST['passwd'];
  $MM_fldUserAuthorization = "";
  $MM_redirectLoginSuccess = "welcome.php";
  $MM_redirectLoginFailed = "loginerror.php";
  $MM_redirecttoReferrer = false;
  mysql_select_db($database_conn_career, $conn_career);
  
  $LoginRS__query=sprintf("SELECT emailAddress, userid, password FROM logininfo WHERE emailAddress='%s' AND password='%s'",
    get_magic_quotes_gpc() ? $loginUsername : addslashes($loginUsername), get_magic_quotes_gpc() ? $password : addslashes($password)); 
   
  $LoginRS = mysql_query($LoginRS__query, $conn_career) or die(mysql_error());
  $row1=mysql_fetch_array($LoginRS);
  
  $loginFoundUser = mysql_num_rows($LoginRS);
  if ($loginFoundUser) {
     $loginStrGroup = "";
    
    //declare two session variables and assign them
    $_SESSION['MM_Username'] = $loginUsername;
    $_SESSION['MM_UserGroup'] = $loginStrGroup;	
	$_SESSION['UserID'] = $row1['userid'];      

    if (isset($_SESSION['PrevUrl']) && false) {
      $MM_redirectLoginSuccess = $_SESSION['PrevUrl'];	
    }
    header("Location: " . $MM_redirectLoginSuccess );
  }
  else {
    header("Location: ". $MM_redirectLoginFailed );
  }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<!-- saved from url=(0038)http://careers.mtnonline.com/error.asp --><HTML><HEAD><TITLE>Careers at OOUTH</TITLE>
<META content="text/html; charset=windows-1252" http-equiv=Content-Type><!--Fireworks MX 2004 Dreamweaver MX 2004 target.  Created Sat Dec 04 17:23:24 GMT+0100 2004--><LINK 
rel=stylesheet type=text/css 
href="loginerror_files/oouth.css">
<SCRIPT language=JavaScript type=text/javascript 
src="loginerror_files/general.js"></SCRIPT>

<META name=GENERATOR content="MSHTML 8.00.7600.16385"></HEAD>
<BODY>
<TABLE border=0 cellSpacing=0 cellPadding=0 width="100%" height="100%"><!-- fwtable fwsrc="MTN4U.png" fwbase="index.jpg" fwstyle="Dreamweaver" fwdocid = "1226677029" fwnested="0" -->
  <TBODY>
  <TR>
    <TD><IMG border=0 alt="" 
      src="loginerror_files/spacer.gif" width=750 
      height=1></TD></TR>
  <TR>
    <TD class=centerAligned height=100 vAlign=top>
      <DIV align=center></DIV>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750><!-- fwtable fwsrc="Untitled" fwbase="top.gif" fwstyle="Dreamweaver" fwdocid = "2000728079" fwnested="0" -->
        <TBODY>
        <TR>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=7 height=1></TD>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=78 height=1></TD>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=491 height=1></TD>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=153 height=1></TD>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=21 height=1></TD>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD></TR>
        <TR>
          <TD colSpan=5><IMG border=0 name=top_r1_c1 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=11></TD></TR>
        <TR>
          <TD rowSpan=4><IMG border=0 name=top_r2_c1 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD>
          <TD rowSpan=4><A href="http://www.oouth.com/"><IMG border=0 
            src="loginerror_files/oouthLogo.gif" 
            width=79 height=80></A></TD>
          <TD rowSpan=4 colSpan=2 align=right><IMG 
            src="loginerror_files/careers_at_oouth.gif" 
            width=300 height=40><IMG border=0 name=top_r4_c4 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD>
          <TD>&nbsp;</TD>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=17></TD></TR>
        <TR>
          <TD rowSpan=3><IMG border=0 name=top_r3_c5 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=37></TD></TR>
        <TR>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=25></TD></TR>
        <TR>
          <TD><IMG border=0 alt="" 
            src="loginerror_files/spacer.gif" 
            width=1 height=11></TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=globalNav height=25 vAlign=top>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750 height=21>
        <TBODY>
        <TR>
          <TD class=rightAligned width=10><IMG 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD>
          <TD><IMG 
            src="loginerror_files/spacer.gif" 
            width=6></TD>
          <TD class=leftAligned width=12><IMG 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=dividerCenterAligned height=1 vAlign=top><IMG border=0 
      name=index_r5_c1 alt="" 
      src="loginerror_files/index_r5_c1.jpg" 
      width=750 height=1></TD></TR>
  <TR>
    <TD class=innerPg vAlign=top>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750>
        <TBODY>
        <TR>
          <TD rowSpan=2 width=8><IMG 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD>
          <TD class=breadcrumbs height=20 vAlign=bottom colSpan=2><A 
            href="http://careers.mtnonline.com/index.asp">Home</A> / Error! </TD>
          <TD rowSpan=2 width=12><IMG 
            src="loginerror_files/spacer.gif" 
            width=1 height=1></TD></TR>
        <TR>
          <TD class=Content vAlign=top width=180>
            <P>&nbsp;</P><BR>
            <TABLE class=innerWhiteBox border=0 cellSpacing=0 cellPadding=4 
            width="96%">
              <TBODY>
              <TR>
                <TD class=sidenavtxt><EM><FONT size=1 
                  face="Verdana, Arial, Helvetica, sans-serif">Welcome,</FONT></EM> 
                  <FONT size=1 
                  face="Verdana, Arial, Helvetica, sans-serif"><SPAN>Guest 
                  </SPAN></FONT></TD></TR></TBODY></TABLE><BR>
            <TABLE class=innerWhiteBox border=0 cellSpacing=0 cellPadding=4 
            width="96%">
              <TBODY>
              <TR>
                <TD class=sidenavtxt width="100%" colSpan=2>
                  <P><A href="vacancies.php">View 
                  Vacancies</A> <BR>
                  </P></TD></TR></TBODY></TABLE><BR>
            <TABLE class=innerWhiteBox border=0 cellSpacing=0 cellPadding=4 
            width="96%">
              <TBODY>
              <TR>
                <TD class=sidenavtxt width="100%">
                  <P>To <A 
                  href="http://careers.mtnonline.com/loginuser.asp">login</A>, 
                  click here<BR><BR>Not registered? To <A 
                  href="http://careers.mtnonline.com/register.asp">register</A>, 
                  click here<BR></FONT></P></TD></TR></TBODY></TABLE><BR>
            <SCRIPT language=JavaScript1.2 
            src="loginerror_files/misc.htm"></SCRIPT>          </TD>
          <TD class=Content vAlign=top rowSpan=2><IMG 
            src="loginerror_files/error.gif" 
            width=350 height=30> 
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
                      <TD vAlign=top width="59%">
                        <P>You have been redirected here for one of the 
                        following reasons: 
                        <UL>
                          <LI>Invalid login username and password <BR><BR>
                          <LI>The page you were trying to view requires you to 
                          log in. <BR><BR>
                          <LI>Your session (after logging in) timed out due to 
                          inactivity. Please log in again. </LI></UL>
                        <P></P>
                        <P><BR></P></TD>
                      <TD vAlign=top width="41%">
                        <FIELDSET><LEGEND class=contentHeader1>Login</LEGEND>
                        <TABLE border=0 cellSpacing=0 cellPadding=2 width="96%" 
                        align=center height="100%">
                          <FORM 
                          onsubmit="YY_checkform('login','uname','#S','2','Please enter valid email address','passwd','#q','0','Please enter your password');return document.MM_returnValue" 
                          method=POST name=login action=<?php echo $loginFormAction; ?>>
                          <TBODY>
                          <TR vAlign=top>
                            <TD class=Content colSpan=2><IMG 
                              src="loginerror_files/spacer.gif" 
                              width=1 height=1></TD></TR>
                          <TR vAlign=center>
                            <TD class=greyBgd height=30 align=middle><INPUT 
                              id=uname class=innerBox size=14 name=uname></TD>
                            <TD class=greyBgd>Email Address </TD></TR>
                          <TR vAlign=center>
                            <TD class=greyBgd height=30 align=middle><INPUT 
                              id=passwd class=innerBox size=14 type=password 
                              name=passwd></TD>
                            <TD class=greyBgd>Password</TD></TR>
                          <TR vAlign=center>
                            <TD class=greyBgd height=30>&nbsp;</TD>
                            <TD class=greyBgd><INPUT class=formbutton value=" Login " type=submit name=Submit></TD></TR>
                          <TR vAlign=top>
                            <TD class=Content height=35 colSpan=2><A 
                              href="register.php">Click 
                              here to register</A> <BR><SPAN class=teenietxt2><A 
                              href="http://careers.mtnonline.com/forgotpassword.asp">Forgot 
                              password?</A></SPAN></TD></TR></FORM></TABLE></FIELDSET></TD></TR></TABLE></DIV></TD></TR></TABLE><BR><BR><BR></TD></TR>
        <TR>
          <TD class=Content vAlign=top>&nbsp;</TD></TR></TABLE></TD></TR>
  <TR>
    <TD class=innerPg height=1 vAlign=top><IMG border=0 name=index_r7_c1 
      alt="" src="loginerror_files/index_r7_c1.jpg" 
      width=750 height=1></TD></TR>
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
      alt="" src="loginerror_files/index_r9_c1.jpg" 
      width=750 height=1></TD></TR>
  <TR>
    <TD class=innerPg vAlign=top>&nbsp;</TD></TR></TBODY></TABLE>
</BODY></HTML>
