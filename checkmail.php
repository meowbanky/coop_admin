<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
include_once('Connections/coop.php');
include_once('sendmail_later.php');



$period = $_GET['PeriodID'];
//$period = 163;

$query = $conn->prepare("SELECT * FROM tblemployees where status = 'Active'");
$res = $query->execute();
$out = $query->fetchAll(PDO::FETCH_ASSOC);

while ($row = array_shift($out)) {
    if (!filter_var($row['EmailAddress'], FILTER_VALIDATE_EMAIL)) {
    echo $row['CoopID'].' - '. $row['EmailAddress'].'<br> ';
    }

}




?>