<?php
session_start();
@mysql_connect('localhost','root','oluwaseyi');
mysql_select_db("library_automation");
if (!isset($_SESSION['FirstName'])){
header("Location:home.php");
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<!-- saved from url=(0070)file://C:\Users\emmaggi\Desktop\Careers at MTN Nigeria_after_login.htm -->
<!-- saved from url=(0040)http://careers.mtnonline.com/welcome.asp --><HTML><HEAD><TITLE>Careers at OOUTH</TITLE>
<META content="text/html; charset=windows-1252" http-equiv=Content-Type><!--Fireworks MX 2004 Dreamweaver MX 2004 target.  Created Sat Dec 04 17:23:24 GMT+0100 2004--><LINK 
rel=stylesheet type=text/css 
href="welcome_files/oouth.css">
<SCRIPT language=JavaScript type=text/javascript 
src="welcome_files/general.js"></SCRIPT>

<META name=GENERATOR content="MSHTML 8.00.7600.16385"></HEAD>
<BODY>
<TABLE border=0 cellSpacing=0 cellPadding=0 width="100%" height="100%"><!-- fwtable fwsrc="MTN4U.png" fwbase="index.jpg" fwstyle="Dreamweaver" fwdocid = "1226677029" fwnested="0" -->
  <TBODY>
  <TR>
    <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
      width=750 height=1></TD></TR>
  <TR>
    <TD class=centerAligned height=100 vAlign=top>
      <DIV align=center></DIV>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750><!-- fwtable fwsrc="Untitled" fwbase="top.gif" fwstyle="Dreamweaver" fwdocid = "2000728079" fwnested="0" -->
        <TBODY>
        <TR>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=7 height=1></TD>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=78 height=1></TD>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=491 height=1></TD>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=153 height=1></TD>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=21 height=1></TD>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=1 height=1></TD></TR>
        <TR>
          <TD colSpan=5><IMG border=0 name=top_r1_c1 alt="" 
            src="welcome_files/spacer.gif" width=1 height=1></TD>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=1 height=11></TD></TR>
        <TR>
          <TD rowSpan=4><IMG border=0 name=top_r2_c1 alt="" 
            src="welcome_files/spacer.gif" width=1 height=1></TD>
          <TD rowSpan=4><A href="http://www.mtnonline.com/"><IMG border=0 
            src="welcome_files/oouthLogo.gif" width=79 
          height=80></A></TD>
          <TD rowSpan=4 colSpan=2 align=right><IMG 
            src="welcome_files/careers_at_oouth.gif" width=300 
            height=40><IMG border=0 name=top_r4_c4 alt="" 
            src="welcome_files/spacer.gif" width=1 height=1></TD>
          <TD>&nbsp;</TD>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=1 height=17></TD></TR>
        <TR>
          <TD rowSpan=3><IMG border=0 name=top_r3_c5 alt="" 
            src="welcome_files/spacer.gif" width=1 height=1></TD>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=1 height=37></TD></TR>
        <TR>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=1 height=25></TD></TR>
        <TR>
          <TD><IMG border=0 alt="" src="welcome_files/spacer.gif" 
            width=1 height=11></TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=mainNav height=21 vAlign=top>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750 height=21>
        <TBODY>
        <TR>
          <TD class=rightAligned width=10><IMG 
            src="Careers%20at%20oouth_registration_files/spacer.gif" 
            width=1 height=1></TD>
          <TD>&nbsp;</TD>
          <TD class=leftAligned width=12><IMG 
            src="Careers%20at%20oouth_registration_files/spacer.gif" 
            width=1 height=1></TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=dividerCenterAligned height=1 vAlign=top><IMG border=0 
      name=index_r3_c1 alt="" src="welcome_files/index_r3_c1.jpg" 
      width=750 height=1></TD></TR>
  <TR>
    <TD class=globalNav height=25 vAlign=top>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750 height=21>
        <TBODY>
        <TR>
          <TD class=rightAligned width=10><IMG 
            src="welcome_files/spacer.gif" width=1 height=1></TD>
          <TD><IMG src="welcome_files/spacer.gif" width=6></TD>
          <TD class=leftAligned width=12><IMG 
            src="welcome_files/spacer.gif" width=1 
        height=1></TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=dividerCenterAligned height=1 vAlign=top><IMG border=0 
      name=index_r5_c1 alt="" src="welcome_files/index_r5_c1.jpg" 
      width=750 height=1></TD></TR>
  <TR>
    <TD class=innerPg vAlign=top>
      <TABLE border=0 cellSpacing=0 cellPadding=0 width=750>
        <TBODY>
        <TR>
          <TD rowSpan=2 width=8><IMG 
            src="welcome_files/spacer.gif" width=1 height=1></TD>
          <TD class=breadcrumbs height=20 vAlign=bottom colSpan=2><A 
            href="http://careers.mtnonline.com/index.asp">Home</A> / Welcome to 
            OOUTH Careers </TD>
          <TD rowSpan=2 width=12><IMG 
            src="welcome_files/spacer.gif" width=1 height=1></TD></TR>
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
                  face="Verdana, Arial, Helvetica, sans-serif"><SPAN><?php echo ($_SESSION['FirstName']); ?> 
                  <BR><IMG border=0 src="welcome_files/spacer.gif" 
                  width=1 height=8><IMG border=0 
                  src="welcome_files/arrow_bullets2.gif"> <A 
                  href="changepasswd.php">Change 
                  Password</A> <BR>
                  <IMG border=0 
                  src="welcome_files/spacer.gif" width=1 
                  height=8><IMG border=0 
                  src="welcome_files/arrow_bullets2.gif"> <A 
                  href="personal.php">Edit 
                  Details</A> <BR>
                  <IMG border=0 
                  src="welcome_files/spacer.gif" width=1 
                  height=8><IMG border=0 
                  src="welcome_files/arrow_bullets2.gif"> <A 
                  href="http://careers.mtnonline.com/logout.asp">Logout</A> 
                  </SPAN></FONT></TD></TR></TBODY></TABLE><BR>
            <TABLE class=innerWhiteBox border=0 cellSpacing=0 cellPadding=4 
            width="96%">
              <TBODY>
              <TR>
                <TD class=sidenavtxt width="100%" colSpan=2>
                  <P><A href="vacancies.php">View 
                  Vacancies</A> <BR>
                  </P></TD></TR>
              <TR>
                <TD><IMG border=0 src="welcome_files/spacer.gif" 
                  width=1 height=8><IMG border=0 
                  src="welcome_files/arrow_bullets2.gif"></TD>
                <TD class=sidenavtxt width="100%"><A 
                  href="http://careers.mtnonline.com/myapplications.asp">My 
                  Applications</A> </TD></TR></TBODY></TABLE><BR><BR>
            <TABLE class=innerWhiteBox border=0 cellSpacing=0 cellPadding=4 
            width="96%">
              <TBODY>
              <TR>
                <TD class=sidenavtxt colSpan=2>
                  <P><A href="index.php">View My 
                  CV</A><IMG src="welcome_files/spacer.gif" width=8 
                  height=8> <FONT color=#009966><IMG alt="CV Completed" 
                  align=absMiddle 
                  src="welcome_files/cv_completed.gif" width=16 
                  height=12></FONT> <BR>
                  </FONT></P></TD></TR>
              <TR>
                <TD class=legend colSpan=2>Legend<EM><BR><IMG 
                  alt="CV Completed" align=absMiddle 
                  src="welcome_files/cv_completed.gif" width=9 
                  height=8>-Complete<IMG 
                  src="welcome_files/spacer.gif" width=8 height=8> 
                  <FONT color=#009966><IMG alt="CV Completed" align=absMiddle 
                  src="welcome_files/cv_uncompleted.gif" width=9 
                  height=8></FONT>-Incomplete </EM></TD></TR></TBODY></TABLE><BR>
            <SCRIPT language=JavaScript1.2 
            src="welcome_files/misc.htm"></SCRIPT>
          </TD>
          <TD id=top class=Content vAlign=top rowSpan=2><IMG 
            src="welcome_files/welcome.gif" width=350 height=30> 
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
                      <TD vAlign=top>Welcome to OOUTH Careers Nigeria. OOUTH 
                        recruits personel who are driven by <BR>
                        ambition, 
                        stimulated by challenge, who have the team spirit, and 
                        most of all, people who share our values. Do you have 
                        what it takes to join our team? 
                        <P>Here, vacancies within MTN Nigeria are advertised on 
                        a regular basis. On this site, you can:</P>
                        <UL>
                          <LI>Get <A 
                          href="#">more 
                          information on OOUTH </A> and the <A 
                          href="#">various 
                          departments</A>,<BR>
                          <BR>
                          <LI><A 
                          href="index.php">Administer 
                          your CV</A>, <BR>
                          <BR>
                          <LI><A 
                          href="vacancies.php">View 
                          advertised vacancies</A>, and apply for vacancies - 
                          all at the click of a button. </LI>
                        </UL>
                        <P>You are welcome to MTN careers and good luck!</P>
                        <P></P><BR></TD></TR></TBODY></TABLE></DIV></TD></TR></TBODY></TABLE><BR><BR><BR></TD></TR>
        <TR>
          <TD class=Content vAlign=top>&nbsp;</TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=innerPg height=1 vAlign=top><IMG border=0 name=index_r7_c1 
      alt="" src="welcome_files/index_r7_c1.jpg" width=750 
    height=1></TD></TR>
  <TR>
    <TD class=innerPg height=21 vAlign=top>
      <TABLE class=contentHeader1 border=0 cellSpacing=0 cellPadding=0 width=750 
      height=21>
        <TBODY>
        <TR>
          <TD class=rightAligned width=10>&nbsp;</TD>
          <TD class=contentHeader1>&nbsp;</TD>
          <TD class=leftAligned width=12>&nbsp;</TD></TR></TBODY></TABLE></TD></TR>
  <TR>
    <TD class=innerPg height=1 vAlign=top><IMG border=0 name=index_r9_c1 
      alt="" src="welcome_files/index_r9_c1.jpg" width=750 
    height=1></TD></TR>
  <TR>
    <TD class=innerPg vAlign=top>&nbsp;</TD></TR></TBODY></TABLE></BODY></HTML>
