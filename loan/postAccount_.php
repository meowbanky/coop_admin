<?php require_once('Connections/coop.php'); ?>
<?php
// Function kept for compatibility if needed, though PDO bindValue is better
function GetSQLValueString($theValue, $theType)
{
    return $theValue;
}

if ((isset($_GET['batchid'])) && ($_GET['batchid'] != "")) {
    try {
        $batchId = $_GET['batchid'];
        
        // Delete existing
        $deleteSql = "DELETE FROM tbl_loanapproval WHERE batch = :batch";
        $stmt = $db->prepare($deleteSql);
        $stmt->execute(['batch' => $batchId]);
        
        // Insert new
        $insert = "INSERT INTO tbl_loanapproval (coopID, approvalDate, LoanAmount, loanapproval_id,batch) 
                   SELECT BeneficiaryCode, NOW(), Amount, PaymentRefID, Batch 
                   FROM excel WHERE Batch = :batch";
        $stmt = $db->prepare($insert);
        $result = $stmt->execute(['batch' => $batchId]);
        
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>Untitled Document</title>
</head>

<body>
	<?php if (isset($result) && $result) {
		echo 'Account Posting of batch:' . htmlspecialchars($_GET['batchid']) . ' Successful';
	} ?>

</body>

</html>