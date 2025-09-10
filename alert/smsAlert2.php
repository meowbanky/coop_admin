<?php require_once('Connections/alertsystem.php'); ?>
<?php
$currentPage = $_SERVER["PHP_SELF"];

$maxRows_masterTransaction = 10;
$pageNum_masterTransaction = 0;
if (isset($_GET['pageNum_masterTransaction'])) {
  $pageNum_masterTransaction = $_GET['pageNum_masterTransaction'];
}
$startRow_masterTransaction = $pageNum_masterTransaction * $maxRows_masterTransaction;

mysql_select_db($database_alertsystem, $alertsystem);
$query_masterTransaction = "SELECT Sum(tbl_mastertransact.savingsAmount) AS Savings, Sum(tbl_mastertransact.sharesAmount) AS Shares, Sum(tbl_mastertransact.loan) AS loan, Sum(tbl_mastertransact.loanRepayment) AS repayment, (Sum(tbl_mastertransact.loan) - Sum(tbl_mastertransact.loanRepayment)) AS outstanding, tbl_mastertransact.COOPID, Max(tbpayrollperiods.PayrollPeriod) as Period, tblemployees.MobileNumber FROM tbl_mastertransact LEFT JOIN tbpayrollperiods ON tbpayrollperiods.id = tbl_mastertransact.TransactionPeriod INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID WHERE tblemployees.`Status` = 'Active' GROUP BY tbl_mastertransact.COOPID";
$query_limit_masterTransaction = sprintf("%s LIMIT %d, %d", $query_masterTransaction, $startRow_masterTransaction, $maxRows_masterTransaction);
$masterTransaction = mysql_query($query_limit_masterTransaction, $alertsystem) or die(mysql_error());
$row_masterTransaction = mysql_fetch_assoc($masterTransaction);

if (isset($_GET['totalRows_masterTransaction'])) {
  $totalRows_masterTransaction = $_GET['totalRows_masterTransaction'];
} else {
  $all_masterTransaction = mysql_query($query_masterTransaction);
  $totalRows_masterTransaction = mysql_num_rows($all_masterTransaction);
}
$totalPages_masterTransaction = ceil($totalRows_masterTransaction/$maxRows_masterTransaction)-1;

mysql_select_db($database_alertsystem, $alertsystem);
$query_grandTotal = "SELECT Sum(tbl_mastertransact.savingsAmount) as savings, sum(tbl_mastertransact.sharesAmount) as shares, ((sum(tbl_mastertransact.loan) )- (sum(tbl_mastertransact.loanRepayment))) as outstanding FROM tbl_mastertransact";
$grandTotal = mysql_query($query_grandTotal, $alertsystem) or die(mysql_error());
$row_grandTotal = mysql_fetch_assoc($grandTotal);
$totalRows_grandTotal = mysql_num_rows($grandTotal);

$queryString_masterTransaction = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pageNum_masterTransaction") == false && 
        stristr($param, "totalRows_masterTransaction") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_masterTransaction = "&" . htmlentities(implode("&", $newParams));
  }
}
$queryString_masterTransaction = sprintf("&totalRows_masterTransaction=%d%s", $totalRows_masterTransaction, $queryString_masterTransaction);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- DW6 -->
<head>
<!-- Copyright 2005 Macromedia, Inc. All rights reserved. -->
<title>..:OOUTH COOP SMS ALERT:..</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" href="mm_health_nutr.css" type="text/css" />
<script language="JavaScript" type="text/javascript">
//--------------- LOCALIZEABLE GLOBALS ---------------
var d=new Date();
var monthname=new Array("January","February","March","April","May","June","July","August","September","October","November","December");
//Ensure correct for language. English is "January 1, 2004"
var TODAY = monthname[d.getMonth()] + " " + d.getDate() + ", " + d.getFullYear();
//---------------   END LOCALIZEABLE   ---------------
</script>
<style type="text/css">
<!--
.style1 {
	color: #FF0000;
	font-weight: bold;
}
-->
</style>
</head>
<body bgcolor="#F4FFE4">
<form id="form1" name="form1" method="post" action= "coop.php">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr bgcolor="#D5EDB3">
    <td colspan="2" rowspan="2"><img src="mm_health_photo.jpg" alt="Header image" width="382" height="101" border="0" /></td>
    <td width="652" height="50" id="logo" valign="bottom" align="center" nowrap="nowrap">OOUTH COOP. SOCIETY SMS ALERT SYSTEM </td>
    <td width="64">&nbsp;</td>
  </tr>

  <tr bgcolor="#D5EDB3">
    <td height="51" id="tagline" valign="top" align="center">..sms system </td>
	<td width="64">&nbsp;</td>
  </tr>

  <tr>
    <td colspan="4" bgcolor="#5C743D"><img src="mm_spacer.gif" alt="" width="1" height="2" border="0" /></td>
  </tr>

  <tr>
    <td colspan="4" bgcolor="#99CC66" background="mm_dashed_line.gif"><img src="mm_dashed_line.gif" alt="line decor" width="4" height="3" border="0" /></td>
  </tr>

  <tr bgcolor="#99CC66">
  <td>&nbsp;</td>
  	<td colspan="3" id="dateformat" height="20"><a href="javascript:;">HOME</a>&nbsp;&nbsp;::&nbsp;&nbsp;<script language="JavaScript" type="text/javascript">
      document.write(TODAY);	</script>	</td>
  </tr>

  <tr>
    <td colspan="4" bgcolor="#99CC66" background="mm_dashed_line.gif"><img src="mm_dashed_line.gif" alt="line decor" width="4" height="3" border="0" /></td>
  </tr>

  <tr>
    <td colspan="4" bgcolor="#5C743D"><img src="mm_spacer.gif" alt="" width="1" height="2" border="0" /></td>
  </tr>
 <tr>
    <td width="40">&nbsp;</td>
    <td colspan="2" valign="top">&nbsp;<br />
    &nbsp;<br />
   <table border="0" cellspacing="0" cellpadding="2" width="993">
        <tr>
          <td width="989" class="pageName">Master Transaction Listing - Month of <?php echo $row_masterTransaction['Period']; ?></td>
        </tr>
        <tr>
          <td class="bodyText"><p>&nbsp;</p>		  
            <table width="100%" border="1" bordercolor="#00FF33">
              <tr>
                <th width="18%" scope="col">COOP ID </th>
                <th width="18%" scope="col">SHARES</th>
                <th width="19%" scope="col">SAVINGS</th>
                <th width="20%" scope="col">LOAN BALANCE </th>
                <th width="20%" scope="col">TEL. NO </th>
                <th width="25%" scope="col">PERIOD</th>
              </tr>
             <?php do { ?> <tr>
                
                  <th scope="row"><?php echo $row_masterTransaction['COOPID']; ?></th>
                  <td align="right"><strong><?php echo number_format($row_masterTransaction['Shares'],2,'.',','); ?></strong></td>
                  <td align="right"><strong><?php echo number_format($row_masterTransaction['Savings'],2,'.',','); ?></strong></td>
                  <td align="right"><strong><?php echo number_format($row_masterTransaction['outstanding'],2,'.',','); ?></strong></td>
                  <td><strong><?php echo $row_masterTransaction['MobileNumber']; ?></strong></td>
                  <td><strong><?php echo $row_masterTransaction['Period']; ?></strong></td>
                 </tr><?php } while ($row_masterTransaction = mysql_fetch_assoc($masterTransaction)); ?>
                 <tr>
                   <th scope="row"><strong>GRAND TOTAL </strong></th>
                   <td align="right"><span class="style1"><?php echo number_format($row_grandTotal['shares'],2,'.',','); ?></span></td> 
                   <td align="right"><span class="style1"><?php echo number_format($row_grandTotal['savings'],2,'.',','); ?></span></td>
                   <td align="right"><span class="style1"><?php echo number_format($row_grandTotal['outstanding'],2,'.',','); ?></span></td>
                   <td>&nbsp;</td>
                   <td>&nbsp;</td>
                 </tr> 
                 </table></td>
        </tr>
      </table>
	   
	      
	  &nbsp;
	  <div align="center"><br />
        <a href="<?php printf("%s?pageNum_masterTransaction=%d%s", $currentPage, 0, $queryString_masterTransaction); ?>">First</a>&nbsp; <a href="<?php printf("%s?pageNum_masterTransaction=%d%s", $currentPage, max(0, $pageNum_masterTransaction - 1), $queryString_masterTransaction); ?>">Previous</a><a href="<?php printf("%s?pageNum_masterTransaction=%d%s", $currentPage, min($totalPages_masterTransaction, $pageNum_masterTransaction + 1), $queryString_masterTransaction); ?>">Next</a>
        <input type="submit" name="Submit" value="SEND SMS" />
	    <br />	
        </div></td>
    <td width="64">&nbsp;</td>
  </tr>

 <tr>
    <td width="40">&nbsp;</td>
   
	<td width="64">&nbsp;</td>
  </tr>
</table>

</form>
</body>
</html>
<?php
mysql_free_result($masterTransaction);

mysql_free_result($grandTotal);
?>
