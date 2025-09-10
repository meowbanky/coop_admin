<?php
require_once('../onesignal/oneSginalfunctions.php');
require_once('../Connections/coop.php');



function useJSON($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
{
    $gsm = array();
    $country_code = '234';
    $arr_recipient = explode(',', $recipients);
    foreach ($arr_recipient as $recipient) {
        $mobilenumber = trim($recipient);
        if (substr($mobilenumber, 0, 1) == '0') {
            $mobilenumber = $country_code . substr($mobilenumber, 1);
        } elseif (substr($mobilenumber, 0, 1) == '+') {
            $mobilenumber = substr($mobilenumber, 1);
        }
        $generated_id = uniqid('int_', false);
        $generated_id = substr($generated_id, 0, 30);
        $gsm['gsm'][] = array('msidn' => $mobilenumber, 'msgid' => $generated_id);
    }
    $message = array(
        'sender' => $sendername,
        'messagetext' => $messagetext,
        'flash' => "{$flash}",
    );

    $request = array('SMS' => array(
        'auth' => array(
            'username' => $username,
            'apikey' => $apikey
        ),
        'message' => $message,
        'recipients' => $gsm
    ));
    $json_data = json_encode($request);
    if ($json_data) {
        $response = doPostRequest($url, $json_data, array('Content-Type: application/json'));
        $result = json_decode($response);
        return $result->response->status;
    } else {
        return false;
    }
}

function useXML($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
{
    $country_code = '234';
    $arr_recipient = explode(',', $recipients);
    $count = count($arr_recipient);
    $msg_ids = array();
    $recipients = '';

    $xml = new SimpleXMLElement('<SMS></SMS>');
    $auth = $xml->addChild('auth');
    $auth->addChild('username', $username);
    $auth->addChild('apikey', $apikey);

    $msg = $xml->addChild('message');
    $msg->addChild('sender', $sendername);
    $msg->addChild('messagetext', $messagetext);
    $msg->addChild('flash', $flash);

    $rcpt = $xml->addChild('recipients');
    for ($i = 0; $i < $count; $i++) {
        $generated_id = uniqid('int_', false);
        $generated_id = substr($generated_id, 0, 30);
        $mobilenumber = trim($arr_recipient[$i]);
        if (substr($mobilenumber, 0, 1) == '0') {
            $mobilenumber = $country_code . substr($mobilenumber, 1);
        } elseif (substr($mobilenumber, 0, 1) == '+') {
            $mobilenumber = substr($mobilenumber, 1);
        }
        $gsm = $rcpt->addChild('gsm');
        $gsm->addchild('msidn', $mobilenumber);
        $gsm->addchild('msgid', $generated_id);
    }
    $xmlrequest = $xml->asXML();

    if ($xmlrequest) {
        $result = doPostRequest($url, $xmlrequest, array('Content-Type: application/xml'));
        $xmlresponse = new SimpleXMLElement($result);
        return $xmlresponse->status;
    }
    return false;
}

//Function to connect to SMS sending server using HTTP GET
function useHTTPGet($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
{
    $query_str = http_build_query(array('username' => $username, 'apikey' => $apikey, 'sender' => $sendername, 'messagetext' => $messagetext, 'flash' => $flash, 'recipients' => $recipients));
    return file_get_contents("{$url}?{$query_str}");
}

//Function to connect to SMS sending server using HTTP POST
function doPostRequest($url, $arr_params, $headers = array('Content-Type: application/x-www-form-urlencoded'))
{
    $response = array();
    $final_url_data = $arr_params;
    if (is_array($arr_params)) {
        $final_url_data = http_build_query($arr_params, '', '&');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_url_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response['body'] = curl_exec($ch);
    $response['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $response['body'];
}



// -------------------------------------------------------------------- \\
//termi API
function doSendMessage($to,$message) {
$curl = curl_init();
 $country_code = '234';
$mobilenumber = trim($to);
        if (substr($mobilenumber, 0, 1) == '0') {
            $mobilenumber = $country_code . substr($mobilenumber, 1);
        } elseif (substr($mobilenumber, 0, 1) == '+') {
            $mobilenumber = substr($mobilenumber, 1);
        }
        
$data = array("to" => [$mobilenumber], "from" => "OOUTHCOOP", 
"sms" => $message, "type" => "plain", "channel" => "generic", "api_key" => "TLg1GY6Gwcnii12H3EY0LWg4tCFQgcsOg4NVLpdQqm413h32QFJR0VxN4q08jT" );

$post_data = json_encode($data);

curl_setopt_array($curl, array(
CURLOPT_URL => 'https://api.ng.termii.com/api/sms/send/bulk',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'POST',
CURLOPT_POSTFIELDS =>$post_data,
CURLOPT_HTTPHEADER => array(
  'Content-Type: application/json'
),
));

$response = curl_exec($curl);

curl_close($curl);
//echo $response;
}



function sendsms($coopid, $period)
{

    global $hostname_coop;
    global $database_coop;
    global $username_coop;
    global $password_coop;
    global  $coop;

    $json_url = "http://api.ebulksms.com:80/sendsms.json";
    $xml_url = "http://api.ebulksms.com:80/sendsms.xml";
    $http_get_url = "http://api.ebulksms.com:80/sendsms";
    $username = '';
    $apikey = '';

    mysqli_select_db($coop, $database_coop);
    $query_masterTransaction = "SELECT tbl_mastertransact.TransactionPeriod AS Period FROM tbl_mastertransact where TransactionPeriod = '" . $period . "'";
    $masterTransaction = mysqli_query($coop, $query_masterTransaction) or die(mysqli_error($coop));
    $row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
    $totalRows_masterTransaction = mysqli_num_rows($masterTransaction);

    mysqli_select_db($coop, $database_coop);
    $query_MaxPeriod = "SELECT tbpayrollperiods.PayrollPeriod FROM tbpayrollperiods where id = '" . $row_masterTransaction['Period'] . "'";
    $MaxPeriod = mysqli_query($coop, $query_MaxPeriod) or die(mysqli_error($coop));
    $row_MaxPeriod = mysqli_fetch_assoc($MaxPeriod);
    $totalRows_MaxPeriod = mysqli_num_rows($MaxPeriod);

    mysqli_select_db($coop, $database_coop);
    $query_coopid2 = "SELECT tblemployees.CoopID,tblemployees.MobileNumber FROM tblemployees WHERE tblemployees.CoopID ='" . $coopid . "' AND Status = 'Active'";
    $coopid2 = mysqli_query($coop, $query_coopid2) or die(mysqli_error($coop));
    $row_coopid2 = mysqli_fetch_assoc($coopid2);
    $totalRows_coopid2 = mysqli_num_rows($coopid2);


?>



<?php


    if (($totalRows_coopid2 > 0)) { // and  ($balance > $totalRows_masterTransaction)) {
        $j = 1;
        do {


            mysqli_select_db($coop, $database_coop);

            $sql_oneSignal = "SELECT * from oneSignal WHERE coop_id = '" . $row_coopid2['CoopID'] . "'";
            $result_oneSignal = mysqli_query($coop, $sql_oneSignal) or die(mysqli_error($coop));
            $row_oneSignal = mysqli_fetch_array($result_oneSignal);
            $totalRows_oneSignal = mysqli_num_rows($result_oneSignal);


            $query_savings = "SELECT Sum(tbl_savings.AmountPaid) as Savings FROM tbl_savings WHERE tbl_savings.CoopID = '" . $row_coopid2['CoopID'] . "' AND DeductionPeriod <= '" . $period . "'";
            $savings = mysqli_query($coop, $query_savings) or die(mysqli_error($coop));
            $row_savings = mysqli_fetch_assoc($savings);
            $totalRows_savings = mysqli_num_rows($savings);

            $query_shares = "SELECT Sum(tbl_shares.sharesAmount) as Shares FROM tbl_shares WHERE tbl_shares.CoopID = '" . $row_coopid2['CoopID'] . "' AND SharesPeriod <= '" . $period . "'";
            $shares = mysqli_query($coop, $query_shares) or die(mysqli_error($coop));
            $row_shares = mysqli_fetch_assoc($shares);
            $totalRows_shares = mysqli_num_rows($shares);

            $query_loan = "SELECT Sum(tbl_loans.LoanAmount) as Loan FROM tbl_loans WHERE tbl_loans.CoopID = '" . $row_coopid2['CoopID'] . "' AND LoanPeriod <= '" . $period . "'";
            $loan = mysqli_query($coop, $query_loan) or die(mysqli_error($coop));
            $row_loan = mysqli_fetch_assoc($loan);
            $totalRows_loan = mysqli_num_rows($loan);
            $loanV = $row_loan['Loan'];

            $query_loanRepayment = "SELECT Sum(tbl_loanrepayment.Repayment) as Repayment FROM tbl_loanrepayment WHERE tbl_loanrepayment.CoopID = '" . $row_coopid2['CoopID'] . "' AND LoanRepaymentPeriod <= '" . $period . "'";
            $loanRepayment = mysqli_query($coop, $query_loanRepayment) or die(mysqli_error($coop));
            $row_loanRepayment = mysqli_fetch_assoc($loanRepayment);
            $totalRows_loanRepayment = mysqli_num_rows($loanRepayment);
            $loanRepaymentV = $row_loanRepayment['Repayment'];


            $Balance = $loanV - $loanRepaymentV;

            $query_commodity = "SELECT Sum(tbl_commodity.amount) as commodity FROM tbl_commodity WHERE tbl_commodity.coopID = '" . $row_coopid2['CoopID'] . "' AND Period <= '" . $period . "'";
            $commodity = mysqli_query($coop, $query_commodity) or die(mysqli_error($coop));
            $row_commodity = mysqli_fetch_assoc($commodity);
            $totalRows_commodity = mysqli_num_rows($commodity);
            $commodityV = $row_commodity['commodity'];


            $query_commodityRepay = "SELECT Sum(tbl_commodityrepayment.CommodityPayment) as ComRepay FROM tbl_commodityrepayment WHERE tbl_commodityrepayment.coopid = '" . $row_coopid2['CoopID'] . "' AND PaymentPeriod <= '" . $period . "'";
            $commodityRepay = mysqli_query($coop, $query_commodityRepay) or die(mysqli_error($coop));
            $row_commodityRepay = mysqli_fetch_assoc($commodityRepay);
            $totalRows_commodityRepay = mysqli_num_rows($commodityRepay);
            $commodityRepayV = $row_commodityRepay['ComRepay'];

            $commodityBalance = $commodityV - $commodityRepayV;


            $query_contribution = "SELECT tbl_mastertransact.COOPID,
				(SUM(tbl_mastertransact.savingsAmount) + SUM(tbl_mastertransact.sharesAmount) + SUM(tbl_mastertransact.InterestPaid) + SUM(tbl_mastertransact.DevLevy) + SUM(tbl_mastertransact.Stationery) + SUM(tbl_mastertransact.EntryFee) + SUM(tbl_mastertransact.CommodityRepayment) + SUM(tbl_mastertransact.loanRepayment)) as contribution ,SUM(tbl_mastertransact.InterestPaid),SUM(tbl_mastertransact.loanRepayment),
				any_value(tbl_mastertransact.Commodity) as Commodity, any_value(tbl_mastertransact.loan) as loan, SUM(tbl_mastertransact.CommodityRepayment) FROM
				tbl_mastertransact
				WHERE
				tbl_mastertransact.COOPID = '" . $row_coopid2['CoopID'] . "' AND
				tbl_mastertransact.TransactionPeriod = '" . $row_masterTransaction['Period'] . "'  
				GROUP BY
				tbl_mastertransact.COOPID";
            $contribution = mysqli_query($coop, $query_contribution) or die(mysqli_error($coop));
            $row_contribution = mysqli_fetch_assoc($contribution);
            $totalRows_contribution = mysqli_num_rows($contribution);

            set_time_limit(0);
            //ob_end_flush();
            //ob_start();
            //ob_end_flush();
            $total = $totalRows_coopid2;




            $username = 'oouthcoop@gmail.com'; //$_POST['username'];
            $apikey = 'de815442fec07461b0822fd72111ed1422dc67ab'; //$_POST['apikey'];
            $sendername = substr('OOUTHCOOP', 0, 11);

            $flash = 0;




            $recipients = $row_coopid2['MobileNumber']; //$_POST['telephone'];
            $message = 'CONTR: ' . number_format($row_contribution['contribution'], 2, '.', ',') . ' SAVINGS:' . number_format($row_savings['Savings'], 2, '.', ',') . ' SHARES:' . number_format($row_shares['Shares'], 2, '.', ',') . ' INT:' . number_format($row_contribution['SUM(tbl_mastertransact.InterestPaid)'], 2, '.', ',') . ' LOANREPAY:' . number_format($row_contribution['SUM(tbl_mastertransact.loanRepayment)'], 2, '.', ',') . ' COMMREPAY:' . number_format($row_contribution['SUM(tbl_mastertransact.CommodityRepayment)'], 2, '.', ',') . ' LOAN: ' . number_format($row_contribution['loan'], 2, '.', ',') . ' LOAN BAL: ' . number_format($Balance, 2, '.', ',') . ' AS AT: ' .   substr($row_MaxPeriod['PayrollPeriod'], 0, 3) . ' - ' . substr($row_MaxPeriod['PayrollPeriod'], -4, 4); //$_POST['message'];
            $flash = 0;

            $message = stripslashes($message);

            $message = substr($message, 0, 320);


            $result = useJSON($json_url, $username, $apikey, $flash, $sendername, $message, $recipients);
            
            // uncomment below to run termini sms
            if(strlen($recipients) == 11){
            doSendMessage($recipients,$message);
}
            // This is for the buffer achieve the minimum size in order to flush data

            if ($totalRows_oneSignal  > 0) {
                if($row_oneSignal['player_id'] != 0) {
                global $apiInstance;
                $notification = createNotificationPlayer($message, $row_oneSignal['player_id'], $row_oneSignal['coop_id']);
                $result__ = $apiInstance->createNotification($notification);
            }
}
            echo str_repeat(' ', 1024 * 64);


            // Send output to browser immediately
            ob_end_flush();
            flush();



            // echo "SMS Sent to :- " . $row_coopid2['MobileNumber'] . "<br>";
            // echo $result;

            ob_start();
        } while ($row_coopid2 = mysqli_fetch_assoc($coopid2));
    }
}

?>