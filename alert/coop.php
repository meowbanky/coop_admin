<?php require_once('Connections/alertsystem.php'); ?>
<?php

$json_url = "https://api.ebulksms.com:4433/sendsms.json";
$xml_url = "https://api.ebulksms.com:4433/sendsms.xml";
$http_get_url = "https://api.ebulksms.com:8080/sendsms";
$username = '';
$apikey = '';


$total = 0;
if (isset($_POST['equality'])) {
    $equality = $_POST['equality'];
} else {
    $equality = '>=';
}

$period = -1;
if (isset($_POST['period'])) {
    $period = $_POST['period'];
}

//$period = 37;

if (isset($_POST['coopid'])) {
    $coopid = $_POST['coopid'];
} else {
    $coopid = '1';
}

?>
<?php

mysqli_select_db($alertsystem, $database_alertsystem);
$query_masterTransaction = "SELECT tbl_mastertransact.TransactionPeriod AS Period FROM tbl_mastertransact where TransactionPeriod = '" . $period . "'";
$masterTransaction = mysqli_query($alertsystem, $query_masterTransaction) or die(mysqlI_error($alertsystem));
$row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysqli_num_rows($masterTransaction);


mysqli_select_db($alertsystem, $database_alertsystem);
$query_MaxPeriod = "SELECT tbpayrollperiods.PayrollPeriod FROM tbpayrollperiods where id = '" . $row_masterTransaction['Period'] . "'";
$MaxPeriod = mysqli_query($alertsystem, $query_MaxPeriod) or die(mysqlI_error($alertsystem));
$row_MaxPeriod = mysqli_fetch_assoc($MaxPeriod);
$totalRows_MaxPeriod = mysqli_num_rows($MaxPeriod);

mysqli_select_db($alertsystem, $database_alertsystem);
$query_coopid2 = "SELECT tblemployees.CoopID,tblemployees.MobileNumber FROM tblemployees WHERE right(tblemployees.CoopID,5) " . $equality . " " . $coopid . " AND Status = 'Active'";
$coopid2 = mysqli_query($alertsystem, $query_coopid2) or die(mysqlI_error($alertsystem));
$row_coopid2 = mysqli_fetch_assoc($coopid2);
$totalRows_coopid2 = mysqli_num_rows($coopid2);




class Ebulksms
{

    public function useJSON($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
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
            $response = $this->doPostRequest($url, $json_data, array('Content-Type: application/json'));
            $result = json_decode($response);
            return $result->response->status;
        } else {
            return false;
        }
    }

    public function useXML($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
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
            $result = $this->doPostRequest($url, $xmlrequest, array('Content-Type: application/xml'));
            $xmlresponse = new SimpleXMLElement($result);
            return $xmlresponse->status;
        }
        return false;
    }

    //Function to connect to SMS sending server using HTTP GET
    public function useHTTPGet($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients)
    {
        $query_str = http_build_query(array('username' => $username, 'apikey' => $apikey, 'sender' => $sendername, 'messagetext' => $messagetext, 'flash' => $flash, 'recipients' => $recipients));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$url}?{$query_str}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
        //return file_get_contents("{$url}?{$query_str}");
    }

    //Function to connect to SMS sending server using HTTP POST
    private function doPostRequest($url, $arr_params, $headers = array('Content-Type: application/x-www-form-urlencoded'))
    {
        $response = array('code' => '', 'body' => '');
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
        try {
            $response['body'] = curl_exec($ch);
            $response['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response['code'] != '200') {
                throw new Exception("Problem reading data from $url");
            }
            curl_close($ch);
        } catch (Exception $e) {
            echo 'cURL error: ' . $e->getMessage();
        }
        return $response['body'];
    }
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
    <div id="information">
        <p align="center"></p>
    </div>
    <div id="percentage" style="width"></div>



    <?php

    if (empty($equality)) {
    } else {

        if (($totalRows_coopid2 > 0)) { // and  ($balance > $totalRows_masterTransaction)) {
            $j = 1;
            do {


                mysqli_select_db($alertsystem, $database_alertsystem);
                $query_savings = "SELECT Sum(tbl_savings.AmountPaid) as Savings FROM tbl_savings WHERE tbl_savings.CoopID = '" . $row_coopid2['CoopID'] . "' AND DeductionPeriod <= '" . $period . "'";
                $savings = mysqli_query($alertsystem, $query_savings) or die(mysqlI_error($alertsystem));
                $row_savings = mysqli_fetch_assoc($savings);
                $totalRows_savings = mysqli_num_rows($savings);

                $query_shares = "SELECT Sum(tbl_shares.sharesAmount) as Shares FROM tbl_shares WHERE tbl_shares.CoopID = '" . $row_coopid2['CoopID'] . "' AND SharesPeriod <= '" . $period . "'";
                $shares = mysqli_query($alertsystem, $query_shares) or die(mysqlI_error($alertsystem));
                $row_shares = mysqli_fetch_assoc($shares);
                $totalRows_shares = mysqli_num_rows($shares);

                $query_loan = "SELECT Sum(tbl_loans.LoanAmount) as Loan FROM tbl_loans WHERE tbl_loans.CoopID = '" . $row_coopid2['CoopID'] . "' AND LoanPeriod <= '" . $period . "'";
                $loan = mysqli_query($alertsystem, $query_loan) or die(mysqlI_error($alertsystem));
                $row_loan = mysqli_fetch_assoc($loan);
                $totalRows_loan = mysqli_num_rows($loan);
                $loanV = $row_loan['Loan'];

                $query_loanRepayment = "SELECT Sum(tbl_loanrepayment.Repayment) as Repayment FROM tbl_loanrepayment WHERE tbl_loanrepayment.CoopID = '" . $row_coopid2['CoopID'] . "' AND LoanRepaymentPeriod <= '" . $period . "'";
                $loanRepayment = mysqli_query($alertsystem, $query_loanRepayment) or die(mysqlI_error($alertsystem));
                $row_loanRepayment = mysqli_fetch_assoc($loanRepayment);
                $totalRows_loanRepayment = mysqli_num_rows($loanRepayment);
                $loanRepaymentV = $row_loanRepayment['Repayment'];


                $Balance = $loanV - $loanRepaymentV;

                $query_commodity = "SELECT Sum(tbl_commodity.amount) as commodity FROM tbl_commodity WHERE tbl_commodity.coopID = '" . $row_coopid2['CoopID'] . "' AND Period <= '" . $period . "'";
                $commodity = mysqli_query($alertsystem, $query_commodity) or die(mysqlI_error($alertsystem));
                $row_commodity = mysqli_fetch_assoc($commodity);
                $totalRows_commodity = mysqli_num_rows($commodity);
                $commodityV = $row_commodity['commodity'];


                $query_commodityRepay = "SELECT Sum(tbl_commodityrepayment.CommodityPayment) as ComRepay FROM tbl_commodityrepayment WHERE tbl_commodityrepayment.coopid = '" . $row_coopid2['CoopID'] . "' AND PaymentPeriod <= '" . $period . "'";
                $commodityRepay = mysqli_query($alertsystem, $query_commodityRepay) or die(mysqlI_error($alertsystem));
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
                $contribution = mysqli_query($alertsystem, $query_contribution) or die(mysqlI_error($alertsystem));
                $row_contribution = mysqli_fetch_assoc($contribution);
                $totalRows_contribution = mysqli_num_rows($contribution);
                //$commodityRepayV = $row_commodityRepay['ComRepay'];

                //$commodityBalance = $commodityV - $commodityRepayV;


                set_time_limit(0);
                //ob_end_flush();
                //ob_start();
                //ob_end_flush();
                $total = $totalRows_coopid2;
                //for( $i=0; $i <= $total; $i++ ){
                // Calculate the percentation
                $percent = intval($j / $total * 100) . "%";


                // Ebulk code;

                //$json_url = "https://api.ebulksms.com:8080/sendsms.json";
                //$xml_url = "https://api.ebulksms.com:8080/sendsms.xml";
                $username = '';
                $apikey = '';

                if (isset($_POST['equality'])) {
                    $username = "oouthcoop@gmail.com"; //$_POST['username'];
                    $apikey = "de815442fec07461b0822fd72111ed1422dc67ab"; //$_POST['apikey'];
                    $sendername = substr('OOUTH COOP', 0, 11);
                    $recipients = $row_coopid2['MobileNumber']; //$_POST['telephone'];
                    $message = 'CONTR: ' . number_format($row_contribution['contribution'], 2, '.', ',') . ' SAVINGS:' . number_format($row_savings['Savings'], 2, '.', ',') . ' SHARES:' . number_format($row_shares['Shares'], 2, '.', ',') . ' INT:' . number_format($row_contribution['SUM(tbl_mastertransact.InterestPaid)'], 2, '.', ',') . ' LOANREPAY:' . number_format($row_contribution['SUM(tbl_mastertransact.loanRepayment)'], 2, '.', ',') . ' COMMREPAY:' . number_format($row_contribution['SUM(tbl_mastertransact.CommodityRepayment)'], 2, '.', ',') . ' LOAN: ' . number_format($row_contribution['loan'], 2, '.', ',') . ' LOAN BAL: ' . number_format($Balance, 2, '.', ',') . ' AS AT: ' .   substr($row_MaxPeriod['PayrollPeriod'], 0, 3) . ' - ' . substr($row_MaxPeriod['PayrollPeriod'], -4, 4); //$_POST['message'];
                    $flash = 0;

                    $message = stripslashes($message);

                    $message = substr($message, 0, 320);

                    $Ebulksms = new Ebulksms();
                    #Use the next line for HTTP POST with JSON
                    $result = $Ebulksms->useHTTPGet($json_url, $username, $apikey, $flash, $sendername, $message, $recipients);

                    #Uncomment the next line and comment the one above if you want to use HTTP POST with XML
                    //$result = Ebulksms->useXML($xml_url, $username, $apikey, $flash, $sendername, $message, $recipients);
                }



                // Javascript for updating the progress bar and information
                echo '<script language="javascript">
         document.getElementById("progress").innerHTML="<div style=\"width:' . $percent . ';background-color:#ddd; background-image:url(pbar-ani.gif)\" align=\"center\">' . $percent . '</div>";
		 document.getElementById("information").innerHTML="' . $j . ' row(s) processed.";
		    
    </script>';


                // This is for the buffer achieve the minimum size in order to flush data
                echo str_repeat(' ', 1024 * 64);


                // Send output to browser immediately
                ob_end_flush();
                flush();


                // Sleep one second so we can see the delay
                //sleep(1);


                //echo $i . "  messages sent <br>" ;


                echo "SMS Sent to :- " . $row_coopid2['MobileNumber'] . "<br>";
                echo $result;

                ob_start();
                //}  
                $j++;

                //} while ($row_masterTransaction = mysqli_fetch_assoc($masterTransaction)); 
            } while ($row_coopid2 = mysqli_fetch_assoc($coopid2));
            echo '<script language="javascript">document.getElementById("information").innerHTML="Process completed"</script>';
        }
    }
    // Tell user that the process is completed
    ?>
</body>

</html>

<?php
mysqli_free_result($masterTransaction);
?>