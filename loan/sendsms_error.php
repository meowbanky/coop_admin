<?php require_once('Connections/coopSky.php'); ?>

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
                            <sendtime>2014-08-29 14:24</sendtime>
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
echo "<script>navigate('index.php')</script>";
echo "<a href = \"index.php\"> back </a>";
exit();
}else{


$i=1;
 

set_time_limit(0);
//ob_start();
//ob_end_flush();
$total = 1;   
//for( $i=0; $i <= $total; $i++ ){
// Calculate the percentation
    $percent = intval($i/$total * 100)."%";
	
doSendMessage($recipients =07039394218, $message = 'Kindly appear at the Cooperative Secretariat to sign the Loan Register against Loan issued to you via SKYPAY TRANSFER, for me');


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
      //echo "SMS Sent to :- " . $row_sms_bene['MobileNumber'] . "<br>" ;
	   ob_start();
//}  
$i++;  
} 

echo '<script language="javascript">document.getElementById("information").innerHTML="Process completed"</script>';


 

// Tell user that the process is completed
?>
</body>

</html>

<?php
//mysql_free_result($sms_bene);

?>


