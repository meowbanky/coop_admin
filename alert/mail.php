<html>
<head>
<title>Sending email using PHP</title>
</head>
<body>
<?php
   $to = "bankole.abiodun@oouth.com";
   $subject = "This is subject";
   $message = "This is simple text message.";
   $header = "From:bankole.abiodun@oouth.com \r\n";
   $retval = mail ($to,$subject,$message,$header);
   if( $retval == true )  
   {
      echo "Message sent successfully...";
   }
   else
   {
      echo "Message could not be sent...";
   }
?>
</body>
</html>