<?php
require_once('Connections/coop.php');

// Fetch search term from query string
$searchTerm = mysqli_real_escape_string($coop, $_GET['term']);


// Query to get search suggestions
mysqli_select_db($coop,$database_coop);
$query = "SELECT
	tblemployees.CoopID, 
	concat(tblemployees.FirstName,' , ',tblemployees.MiddleName,' ',tblemployees.LastName) AS `name`, 
	IFNULL(tblaccountno.Bank,'') AS Bank, 
	IFNULL(tblaccountno.AccountNo,'') AS AccountNo, 
	IFNULL(tblbankcode.BankCode,'') AS BankCode
FROM
	tblemployees
	LEFT JOIN
	tblaccountno
	ON 
		tblaccountno.COOPNO = tblemployees.CoopID
		LEFT JOIN
	tblbankcode ON tblaccountno.Bank = tblbankcode.bank
          WHERE CoopID LIKE '%$searchTerm%' OR lastname LIKE '%$searchTerm%' OR firstname LIKE '%$searchTerm%' OR middlename LIKE '%$searchTerm%' 
          LIMIT 10";
$result = mysqli_query($coop, $query);

$suggestions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data['id'] = $row['CoopID'];
    $data['coopname'] = $row['name'];
    $data['label'] = $row['name'].' - '.$row['CoopID'];
    $data['value'] = $row['CoopID'];
    $data['bank'] = $row['Bank'];
    $data['AccountNo'] = $row['AccountNo'];
    $data['BankCode'] = $row['BankCode'];
    array_push($suggestions, $data);
}

// Return suggestions as JSON
echo json_encode($suggestions);
?>