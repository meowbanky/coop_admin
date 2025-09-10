<?php require_once('Connections/alertsystem.php'); ?>
<?php 
$equality = '>=';
$coopid = 'COOP-00001';
?>
<?php
mysql_select_db($database_alertsystem, $alertsystem);
$query_masterTransaction = "SELECT
Sum(tbl_mastertransact.savingsAmount) AS Savings,
Sum(tbl_mastertransact.sharesAmount) AS Shares,
Sum(tbl_mastertransact.loan) AS loan,
Sum(tbl_mastertransact.loanRepayment) AS repayment,
(Sum(tbl_mastertransact.loan) - Sum(tbl_mastertransact.loanRepayment)) AS outstanding,
tbl_mastertransact.COOPID,
Max(tbl_mastertransact.TransactionPeriod) AS Period,
tblemployees.MobileNumber,
(IFNULL(tbl_loansavings.Amount,0)+ tbl_monthlycontribution.MonthlyContribution) as contributions
FROM
tbl_mastertransact
LEFT JOIN tbpayrollperiods ON tbpayrollperiods.id = tbl_mastertransact.TransactionPeriod
INNER JOIN tblemployees ON tblemployees.CoopID = tbl_mastertransact.COOPID
left JOIN tbl_monthlycontribution ON tbl_monthlycontribution.coopID = tbl_mastertransact.COOPID
left JOIN tbl_loansavings ON tbl_loansavings.COOPID = tbl_mastertransact.COOPID
WHERE tbl_mastertransact.COOPID >= 'COOP-00001' GROUP BY tbl_mastertransact.COOPID";
$masterTransaction = mysql_query($query_masterTransaction, $alertsystem) or die(mysql_error());
$row_masterTransaction = mysql_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysql_num_rows($masterTransaction);


mysql_select_db($database_alertsystem, $alertsystem);
$query_MaxPeriod = "SELECT tbpayrollperiods.PayrollPeriod FROM tbpayrollperiods where id = " . $row_masterTransaction['Period'] ;
$MaxPeriod = mysql_query($query_MaxPeriod, $alertsystem) or die(mysql_error());
$row_MaxPeriod = mysql_fetch_assoc($MaxPeriod);
$totalRows_MaxPeriod = mysql_num_rows($MaxPeriod);

?>
<?php
  
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>..:OOUTH COOP SMS ALERT:..</title>

</head>



<body>
<!-- Progress bar holder -->
<div id="progress" style="width:500px;border:1px solid #ccc;"></div>
<!-- Progress information -->
<div id="information" style="width"></div>


<?php
set_time_limit(0);
ob_end_flush();
$total = $totalRows_masterTransaction;   
for( $i=0; $i <= $total; $i++ ){
// Calculate the percentation
    $percent = intval($i/$total * 100)."%";
	

echo $i . "  messages sent <br>" ;


#echo "message sent";

 // Javascript for updating the progress bar and information
   echo '<script language="javascript">
         document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd; background-image:url(pbar-ani.gif)\">&nbsp;</div>";
    document.getElementById("information").innerHTML="'.$i.' row(s) processed.";
    </script>';

    
// This is for the buffer achieve the minimum size in order to flush data
    echo str_repeat(' ',1024*64);

    
// Send output to browser immediately
    flush();

    
// Sleep one second so we can see the delay
    sleep(1);
	}
 
// Tell user that the process is completed
echo '<script language="javascript">document.getElementById("information").innerHTML="Process completed"</script>';
?>

</body>

</html>

<?php
mysql_free_result($masterTransaction);
?>


