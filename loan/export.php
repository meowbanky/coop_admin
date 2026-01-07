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

$filename = $_GET['BATCH'] ?? '';
/*******YOU DO NOT NEED TO EDIT ANYTHING BELOW THIS LINE*******/

if (empty($filename)) {
    die("Batch is required");
}

try {
    // create PDO connection
    $dsn = "mysql:host={$DB_Server};dbname={$DB_DBName};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_Username, $DB_Password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // execute query
    $sql = "SELECT concat(excel.Narration,' ',excel.PaymentRefID) as 'Payment Reference', beneficiaryCode as 'Beneficiary code', excel.BeneficiaryName as 'Beneficiary Name', excel.AccountNumber as 'Account Number', excel.AccountType as 'Account Type', excel.CBNCode, excel.IsCashCard as 'Is Cash Card', excel.Narration, excel.Amount, excel.EMailAddress as 'Email Address', excel.NGN as 'Currency code' FROM excel where Batch = :batch";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['batch' => $filename]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        die("No data found for this batch");
    }
    
    $x = array_keys($row);
    $file_ending = "xls";
    //header info for browser
    header("Content-Type: application/xls");
    header("Content-Disposition: attachment; filename=$filename.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    /*******Start of Formatting for Excel*******/
    // Define separator
    $sep = "\t";
    
    // Start generating excel data
    echo implode($sep, $x) . "\n";
    
    // Reset pointer and fetch all rows
    $stmt->execute(['batch' => $filename]);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $schema_insert = "";
        foreach($row as $key => $value) {
            if(!isset($value))
                $schema_insert .= "NULL".$sep;
            elseif ($value != "")
                $schema_insert .= "$value".$sep;
            else
                $schema_insert .= "".$sep;
        }
        $schema_insert = str_replace($sep."$", "", $schema_insert);
        $schema_insert = preg_replace("/\r\n|\n\r|\n|\r/", " ", $schema_insert);
        $schema_insert .= "\t";
        echo $schema_insert;
        echo "\n";
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>