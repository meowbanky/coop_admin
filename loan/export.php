<?php
/*******EDIT LINES 3-8*******/
require_once __DIR__ . '/../config/EnvConfig.php';

// Load database configuration from .env
$dbConfig = EnvConfig::getDatabaseConfig();
$DB_Server = $dbConfig['host']; //MySQL Server 
$DB_Username = $dbConfig['user']; //MySQL Username 
$DB_Password = $dbConfig['password'];             //MySQL Password 
$DB_DBName = $dbConfig['name'];         //MySQL Database Name 
$DB_TBLName = "excel"; //MySQL Table Name
//$filename = "excelfilename";         //File Name
$filename = $_GET['BATCH'];
/*******YOU DO NOT NEED TO EDIT ANYTHING BELOW THIS LINE*******/
//create MySQL connection
$sql = "SELECT concat(excel.Narration,' ',excel.PaymentRefID) as 'Payment Reference', beneficiaryCode as 'Beneficiary code', excel.BeneficiaryName as 'Beneficiary Name', excel.AccountNumber as 'Account Number', excel.AccountType as 'Account Type', excel.CBNCode, excel.IsCashCard as 'Is Cash Card', excel.Narration, excel.Amount, excel.EMailAddress as 'Email Address', excel.NGN as 'Currency code' FROM excel where Batch ='".$filename."'";
$Connect = @mysqli_connect($DB_Server, $DB_Username, $DB_Password)
    or die("Couldn't connect to MySQL:<br>" . mysqli_error($Connect) . "<br>" . mysqli_errno($Connect));
//select database
$Db = @mysqli_select_db($Connect,$DB_DBName)
    or die("Couldn't select database:<br>" . mysqli_error($Connect). "<br>" . mysqli_errno($Connect));
//execute query
$result = @mysqli_query($Connect,$sql)
    or die("Couldn't execute query:<br>" . mysqli_error($Connect). "<br>" . mysqli_errno($Connect));
    $row = mysqli_fetch_assoc($result);
    $x = array_keys($row);
$file_ending = "xls";
//header info for browser
header("Content-Type: application/xls");
header("Content-Disposition: attachment; filename=$filename.xls");
header("Pragma: no-cache");
header("Expires: 0");
/*******Start of Formatting for Excel*******/
//define separator (defines columns in excel & tabs in word)
