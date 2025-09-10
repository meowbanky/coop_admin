<?php require_once('Connections/alertsystem.php'); ?>
<?php 
if (isset($_POST['equality'])){
$equality = $_POST['equality'];} 
else {$equality = '>=';}

if (isset($_POST['coopid'])){$coopid = $_POST['coopid'];}else {$coopid = '1';}

?>
<?php
mysql_select_db($database_alertsystem, $alertsystem);
$query_masterTransaction = "SELECT
Max(tbl_mastertransact.TransactionPeriod) AS Period
FROM
tbl_mastertransact";
$masterTransaction = mysql_query($query_masterTransaction, $alertsystem) or die(mysql_error());
$row_masterTransaction = mysql_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysql_num_rows($masterTransaction);


mysql_select_db($database_alertsystem, $alertsystem);
$query_MaxPeriod = "SELECT tbpayrollperiods.PayrollPeriod FROM tbpayrollperiods where id = " . $row_masterTransaction['Period'] ;
$MaxPeriod = mysql_query($query_MaxPeriod, $alertsystem) or die(mysql_error());
$row_MaxPeriod = mysql_fetch_assoc($MaxPeriod);
$totalRows_MaxPeriod = mysql_num_rows($MaxPeriod);


mysql_select_db($database_alertsystem, $alertsystem);
$query_coopid2 = "SELECT tblemployees.CoopID,tblemployees.MobileNumber FROM tblemployees WHERE right(tblemployees.CoopID,5) " . $equality ." ". $coopid . " AND Status = 'Active'";
$coopid2 = mysql_query($query_coopid2, $alertsystem) or die(mysql_error());
$row_coopid2 = mysql_fetch_assoc($coopid2);
$totalRows_coopid2 = mysql_num_rows($coopid2);


?>
<?php
function postRequestData($url) {
    $fp = @fopen($url, 'rb', false);
    if ($fp === false) {
        return false;
    }
    @stream_set_timeout($fp, 5);
    $response = @stream_get_contents($fp);
    if ($response === false) {
        throw new Exception("Problem reading data from $url, $php_errormsg");
    }
    return $response;
}
  
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>..:OOUTH COOP SMS ALERT:..</title>

</head>



<body>
<div id="progress" style="width:500px;border:1px solid #ccc;"></div>
<!-- Progress information -->
<div id="information" style="width"></div>



<?php
  
$balance = api_getBalance() ;
//echo $balance ;


function doSendMessage($recipients,$message){
    $url = "http://www.50kobo.com/tools/xml/Sms.php";
    $flash = 1;
    $username = 'oouthcoop@gmail.com'; //get_option('sms_useremail');
    $userpass =  'coop7980'; //get_option('sms_password');
    $recipients;
    $listname = '';
    //$message 'We at OOUTH rejoice with you as you add another year. We wish you long healthy life and abundant blessings. Happy Birthday';
    $result = '';

		//$message = 'Happy Birthday to you,';

    if( isset($message)){
        //$sendername = substr($_POST['sendername'],0,11);
        //$recipients = $_POST['recipients'];
        //$message =$_POST['message'];
		
		$sendername = 'OOUTH COOP';
		$sendername = substr($sendername,0,11);
		//$recipients = '07039394218';
		//$message = 'Happy Birthday' ;
		//echo $message;
		      
		if ( get_magic_quotes_gpc() ) {
                //$message = stripslashes($_POST['message']);
				$message = stripslashes($message);
        }
        //$message = substr($_POST['message'],0,160);
		$message = substr($message,0,160);
        $listname = '';

        //Send the sms before re-loading the script page
        $result = 'Nothing sent';

        $result = useXML($url, $username, $userpass, $flash, $sendername, $message, $listname, $recipients);
        //print_r($result);
        return $result;
    }
}

function postXmlData($url, $data, $optional_headers = null){
    //Function to connect to SMS sending server using XML POST request
    $php_errormsg='';
    $params = array( 'http' => array(
                                     'method' => 'POST',
                                     'content' => $data )
    );
    if ($optional_headers!== null) {
            $params['http']['header'] = $optional_headers;
    }
    $ctx = stream_context_create($params);
    $fp = @fopen($url, 'rb', false, $ctx);
    if (!$fp) {
            //echo ("Problem with $url.<br> $url is inaccessible");
            return false;
    }
    stream_set_timeout($fp, 0, 250);
    $response = @stream_get_contents($fp);
    if ($response === false) {
            throw new Exception("Problem reading data from $url, $php_errormsg");
    }
    return $response;
}

function useXML($url, $username, $userpassword, $flash, $sendername, $message, $listname, $recipient){
    $country_code = '234';
    $arr_recipient = explode(',',$recipient);
    $count = count($arr_recipient);
    $msg_ids = array();
    $generated_id = uniqid('int_', false);
    $generated_id = substr($generated_id, 0, 30);
    $recipients = '';

    for( $i=0; $i < $count; $i++ ){
            $mobilenumber = $arr_recipient[$i];
            if ( substr($mobilenumber,0,1) == '0') $mobilenumber = $country_code . substr($mobilenumber,1);
            elseif( substr($mobilenumber,0,1) == '+' ) $mobilenumber = substr($mobilenumber,1);
            $recipients .= '<gsm messageId="'.$generated_id.'_'.$i.'">'.$mobilenumber.'</gsm>'."\n";
            $msg_ids[$mobilenumber] = $generated_id.'_'.$i;
    }

    $xmlrequest =
            "<SMS>
                    <authentification>
                            <username>{$username}</username>
                            <password>{$userpassword}</password>
                    </authentification>
                    <message>
                            <sender>{$sendername}</sender>
                            <msgtext>{$message}</msgtext>
                            <flash>{$flash}</flash>
                            <sendtime></sendtime>
                            <listname>$listname</listname>
                    </message>
                    <recipients>"
                    .$recipients.
                    "</recipients>
            </SMS>";

    return postXmlData($url, $xmlrequest);
}

function api_getBalance(){
    $username = 'oouthcoop@gmail.com';
    $userpass = 'coop7980';
    $url = "http://www.50kobo.com/tools/command.php?";
    $querystring = "username={$username}&password={$userpass}&command=balance";
    $result = postRequestData($url.$querystring);
    return doubleVal($result);
	}

$balance = api_getBalance() ;

if (!$sock = @fsockopen('www.google.com', 80, $num, $error, 5))
{ echo "<script>alert('THERE IS NO INTERNET CONNECTION NOW!!!')</script>";
echo "<script>navigate('smsalert.php')</script>";
exit();
}else{

if (($totalRows_masterTransaction > 0) and  ($balance > $totalRows_masterTransaction)) {
$i=1;
do { 


				mysql_select_db($database_alertsystem, $alertsystem);
				$query_savings = "SELECT Sum(tbl_savings.AmountPaid) as Savings FROM tbl_savings WHERE tbl_savings.CoopID = '".$row_coopid2['CoopID']."'";
				$savings = mysql_query($query_savings, $alertsystem) or die(mysql_error());
				$row_savings = mysql_fetch_assoc($savings);
				$totalRows_savings = mysql_num_rows($savings);
				
				$query_shares = "SELECT Sum(tbl_shares.sharesAmount) as Shares FROM tbl_shares WHERE tbl_shares.CoopID = '".$row_coopid2['CoopID']."'";
				$shares = mysql_query($query_shares, $alertsystem) or die(mysql_error());
				$row_shares = mysql_fetch_assoc($shares);
				$totalRows_shares = mysql_num_rows($shares);
				
				$query_loan = "SELECT Sum(tbl_loans.LoanAmount) as Loan FROM tbl_loans WHERE tbl_loans.CoopID = '".$row_coopid2['CoopID']."'";
				$loan = mysql_query($query_loan, $alertsystem) or die(mysql_error());
				$row_loan = mysql_fetch_assoc($loan);
				$totalRows_loan = mysql_num_rows($loan);
				$loanV = $row_loan['Loan'];
				
				$query_loanRepayment = "SELECT Sum(tbl_loanrepayment.Repayment) as Repayment FROM tbl_loanrepayment WHERE tbl_loanrepayment.CoopID = '".$row_coopid2['CoopID']."'";
				$loanRepayment = mysql_query($query_loanRepayment, $alertsystem) or die(mysql_error());
				$row_loanRepayment = mysql_fetch_assoc($loanRepayment);
				$totalRows_loanRepayment = mysql_num_rows($loanRepayment);
				$loanRepaymentV = $row_loanRepayment['Repayment'];
				
				
				$Balance = $loanV - $loanRepaymentV;
				
				$query_commodity = "SELECT Sum(tbl_commodity.amount) as commodity FROM tbl_commodity WHERE tbl_commodity.coopID = '".$row_coopid2['CoopID']."'";
				$commodity = mysql_query($query_commodity, $alertsystem) or die(mysql_error());
				$row_commodity = mysql_fetch_assoc($commodity);
				$totalRows_commodity = mysql_num_rows($commodity);
				$commodityV = $row_commodity['commodity'];
				
				
				$query_commodityRepay = "SELECT Sum(tbl_commodityrepayment.CommodityPayment) as ComRepay FROM tbl_commodityrepayment WHERE tbl_commodityrepayment.coopid = '".$row_coopid2['CoopID']."'";
				$commodityRepay = mysql_query($query_commodityRepay, $alertsystem) or die(mysql_error());
				$row_commodityRepay = mysql_fetch_assoc($commodityRepay);
				$totalRows_commodityRepay = mysql_num_rows($commodityRepay);
				$commodityRepayV = $row_commodityRepay['ComRepay'];
				
				$commodityBalance = $commodityV - $commodityRepayV;
				
				
				$query_contribution = "SELECT tbl_mastertransact.COOPID,
				(SUM(tbl_mastertransact.savingsAmount) + SUM(tbl_mastertransact.sharesAmount) + SUM(tbl_mastertransact.InterestPaid) + SUM(tbl_mastertransact.DevLevy) + SUM(tbl_mastertransact.Stationery) + SUM(tbl_mastertransact.EntryFee) + SUM(tbl_mastertransact.CommodityRepayment) + SUM(tbl_mastertransact.loanRepayment)) as contribution ,
				tbl_mastertransact.Commodity, tbl_mastertransact.loan FROM
				tbl_mastertransact
				WHERE
				tbl_mastertransact.COOPID = '".$row_coopid2['CoopID']."' AND
				tbl_mastertransact.TransactionPeriod = ".$row_masterTransaction['Period']. " 
				GROUP BY
				tbl_mastertransact.COOPID";
				$contribution = mysql_query($query_contribution, $alertsystem) or die(mysql_error());
				$row_contribution = mysql_fetch_assoc($contribution);
				$totalRows_contribution = mysql_num_rows($contribution);
				//$commodityRepayV = $row_commodityRepay['ComRepay'];
				
				//$commodityBalance = $commodityV - $commodityRepayV;


set_time_limit(0);
//ob_end_flush();
//ob_start();
//ob_end_flush();
$total = $totalRows_masterTransaction;   
//for( $i=0; $i <= $total; $i++ ){
// Calculate the percentation
    $percent = intval($i/$total * 100)."%";
	
doSendMessage($recipients = $row_coopid2['MobileNumber'], $message = 'COOP ACCT. BAL, MONTHLY CONTR: '.number_format($row_contribution['contribution'],2,'.',','). ' SAVINGS: '.number_format($row_savings['Savings'],2,'.',',').'  SHARES: '. number_format($row_shares['Shares'],2,'.',',').'  COMM: '.number_format($row_contribution['Commodity'],2,'.',','). ' COMM BAL: '.number_format($commodityBalance,2,'.',','). ' LOAN: '.number_format($row_contribution['loan'],2,'.',',').' LOAN BAL: '.number_format($Balance,2,'.',',').' AS AT: '.   substr($row_MaxPeriod['PayrollPeriod'],0,3) .' - '. substr($row_MaxPeriod['PayrollPeriod'],-4,4) );


// Javascript for updating the progress bar and information
   echo '<script language="javascript">
         document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd; background-image:url(pbar-ani.gif)\">&nbsp;</div>";
    document.getElementById("information").innerHTML="'.$i.' row(s) processed.";
    </script>';

    
// This is for the buffer achieve the minimum size in order to flush data
    echo str_repeat(' ',1024*64);

    
// Send output to browser immediately
	ob_end_flush();
    flush();

    
// Sleep one second so we can see the delay
    //sleep(1);
	

//echo $i . "  messages sent <br>" ;
      echo "SMS Sent to :- " . $row_coopid2['MobileNumber'] . "<br>" ;
	  ob_start();
//}  
$i++;

//} while ($row_masterTransaction = mysql_fetch_assoc($masterTransaction)); 
 } while ($row_coopid2 = mysql_fetch_assoc($coopid2)); 
echo '<script language="javascript">document.getElementById("information").innerHTML="Process completed"</script>';

}
 
}
// Tell user that the process is completed
?>
</body>

</html>

<?php
mysql_free_result($masterTransaction);
?>


