<?php require_once('Connections/alertsystem.php'); ?>
<?php 
$equality = $_POST['equality'];
$coopid = $_POST['coopid'];
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
WHERE right(tbl_mastertransact.COOPID,5) " . $equality ." ". $coopid . "
 GROUP BY tbl_mastertransact.COOPID";
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

do { 
$counter = 0;
set_time_limit(0);
doSendMessage($recipients =$row_masterTransaction['MobileNumber'], $message = 'Your COOP ACCT. BALANCE, MONTHLY CONTR. : '.number_format($row_masterTransaction['contributions'],2,'.',','). ' SAVINGS: '.number_format($row_masterTransaction['Savings'],2,'.',',').'  SHARES: '. number_format($row_masterTransaction['Shares'],2,'.',',').'  LOAN BAL: '.number_format($row_masterTransaction['outstanding'],2,'.',','). '  AS AT: '. $row_MaxPeriod['PayrollPeriod']);
$counter = $counter + 1;
echo $counter . "messages sent" ;
} while ($row_masterTransaction = mysql_fetch_assoc($masterTransaction)); 

echo "message sent";

}
 
}
?>
</body>

</html>

<?php
mysql_free_result($masterTransaction);
?>


