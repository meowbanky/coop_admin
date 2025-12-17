<?php
/*******EDIT LINES 3-8*******/
$DB_Server = "localhost"; //MySQL Server 
$DB_Username = "emmaggic_root"; //MySQL Username 
$DB_Password = "Oluwaseyi";             //MySQL Password 
$DB_DBName = "emmaggic_coop";         //MySQL Database Name 
$DB_TBLName = "excel"; //MySQL Table Name
//$filename = "excelfilename";         //File Name
$filename = $_GET['BATCH'];
/*******YOU DO NOT NEED TO EDIT ANYTHING BELOW THIS LINE*******/
//create MySQL connection
$sql = "SELECT concat(excel.Narration,' ',excel.PaymentRefID) as 'Payment Reference', beneficiaryCode as 'Beneficiary code', excel.BeneficiaryName as 'Beneficiary Name', excel.AccountNumber as 'Account Number', excel.AccountType as 'Account Type', excel.CBNCode, excel.IsCashCard as 'Is Cash Card', excel.Narration, excel.Amount, excel.EMailAddress as 'Email Address', excel.NGN as 'Currency code' FROM excel where Batch ='".$filename."'";
$Connect = @mysqli_connect($DB_Server, $DB_Username, $DB_Password)
    or die("Couldn't connect to MySQL:<br>" . mysqli_error($Connect) . "<br>" . mysql_errno());
//select database
$Db = @mysqli_select_db($Connect,$DB_DBName)
    or die("Couldn't select database:<br>" . mysqli_error($Connect). "<br>" . mysql_errno());
//execute query
$result = @mysqli_query($Connect,$sql)
    or die("Couldn't execute query:<br>" . mysqli_error($Connect). "<br>" . mysql_errno());
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
$sep = "\t";
//start of printing column names as names of MySQL fields
for ($i = 0; $i < mysqli_num_fields($result); $i++) {
//print_r( mysqli_fetch_field_direct($result,$i) ). "\t";

echo $x[$i]. "\t";
}


mysqli_data_seek($result, 0);

print("\n");
//end of printing column names
//start while loop to get data
    while($row = mysqli_fetch_row($result))
    {
        $schema_insert = "";
        for($j=0; $j<mysqli_num_fields($result);$j++)
        {
            if(!isset($row[$j]))
                $schema_insert .= "NULL".$sep;
            elseif ($row[$j] != "")
                $schema_insert .= "$row[$j]".$sep;
            else
                $schema_insert .= "".$sep;
        }
        $schema_insert = str_replace($sep."$", "", $schema_insert);
 $schema_insert = preg_replace("/\r\n|\n\r|\n|\r/", " ", $schema_insert);
        $schema_insert .= "\t";
        print(trim($schema_insert));
        print "\n";
    }
?>

