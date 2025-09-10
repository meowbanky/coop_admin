<?php require_once('Connections/alertsystem.php'); ?>
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Untitled Document</title>
</head>

<body> 
<table width="100%" border="1">
  <tr>
    <td width="5%">COOPID</td>
    <td width="7%">Period</td>
    <td width="7%">Savings</td>
    <td width="3%">Shares</td>
    <td width="3%">Interest Paid </td>
    <td width="3%">Dev. Levy </td>
    <td width="3%">Commodity</td>
    <td width="3%">Commodity Payment</td>
    <td width="3%">Loan</td>
    <td width="3%">Loan Repayment </td>
    <td width="3%">&nbsp;</td>
  </tr>
  
<?php


mysql_select_db($database_alertsystem, $alertsystem);
$query_coopid = "SELECT tblemployees.CoopID FROM tblemployees";
$coopid = mysql_query($query_coopid, $alertsystem) or die(mysql_error());
$row_coopid = mysql_fetch_assoc($coopid);
$totalRows_coopid = mysql_num_rows($coopid);
?>  

  
  
<?php do { ?>
<?php mysql_select_db($database_alertsystem, $alertsystem);
$query_masterTransaction = "SELECT tblemployees.CoopID, tblemployees.FirstName, tblemployees.MiddleName, tblemployees.LastName, tbpayrollperiods.PayrollPeriod, sum(tbl_mastertransact.savingsAmount) as savingsAmount, sum(tbl_mastertransact.sharesAmount) as sharesAmount, sum(tbl_mastertransact.InterestPaid) as InterestPaid, sum(tbl_mastertransact.DevLevy) as DevLevy, sum(tbl_mastertransact.Stationery) as Stationery, sum(tbl_mastertransact.EntryFee) as EntryFee, sum(tbl_mastertransact.Commodity) as Commodity, sum(tbl_mastertransact.CommodityRepayment) as CommodityRepayment, sum(tbl_mastertransact.loan) as loan, sum(tbl_mastertransact.loanRepayment) as loanRepayment FROM tbl_mastertransact INNER JOIN tbpayrollperiods ON tbpayrollperiods.id = tbl_mastertransact.TransactionPeriod INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID WHERE tbl_mastertransact.TransactionPeriod BETWEEN 54 AND 62 AND tblemployees.CoopID = '". $row_coopid['CoopID'] . "' GROUP BY tbl_mastertransact.TransactionPeriod ORDER BY tbl_mastertransact.COOPID ASC";
$masterTransaction = mysql_query($query_masterTransaction, $alertsystem) or die(mysql_error());
$row_masterTransaction = mysql_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysql_num_rows($masterTransaction);
?>

<?php do { ?> <tr>
    
		  <td><?php echo $row_masterTransaction['CoopID']; ?></td>
		  <td><?php echo $row_masterTransaction['PayrollPeriod']; ?></td>
		  <td><div align="right"><?php echo number_format($row_masterTransaction['savingsAmount'],2,'.',','); ?></div></td>
		  <td><div align="right"><?php echo number_format($row_masterTransaction['sharesAmount'],2,'.',','); ?></div></td>
		  <td><div align="right"><?php echo number_format ($row_masterTransaction['InterestPaid'],2,'.',','); ?></div></td>
		  <td><div align="right"><?php echo number_format ($row_masterTransaction['DevLevy'],2,'.',','); ?></div></td>
		  <td><div align="right"><?php echo number_format ($row_masterTransaction['Commodity'],2,'.',','); ?></div></td>
		  <td><div align="right"><?php echo number_format ($row_masterTransaction['CommodityRepayment'],2,'.',','); ?></div></td>
		  <td><div align="right"><?php echo number_format ($row_masterTransaction['loan'],2,'.',','); ?></div></td>
		  <td><div align="right"><?php echo number_format ($row_masterTransaction['loanRepayment'],2,'.',','); ?></div></td>
		  
		  <?php } while ($row_masterTransaction = mysql_fetch_assoc($masterTransaction)); ?>
		  <?php } while ($row_coopid = mysql_fetch_assoc($coopid)); ?>
		  </tr>
          <tr>
          <td>&nbsp;
          </td>
          <td>&nbsp;
          </td>
          <td>&nbsp;
          </td><td>&nbsp;
          </td><td>&nbsp;
          </td><td>&nbsp;
          </td><td>&nbsp;
          </td><td>&nbsp;
          </td><td>&nbsp;
          </td><td>&nbsp;
          </td>
          </tr>
</table>
</body>
</html>
<?php
mysql_free_result($masterTransaction);

mysql_free_result($coopid);
?>
