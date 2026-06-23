<?php
require_once('Connections/coop.php');

$searchTerm = $_GET['term'] ?? '';

// Query to get search suggestions
$query = "SELECT
	tblemployees.CoopID,
	concat(tblemployees.FirstName,' , ',tblemployees.MiddleName,' ',tblemployees.LastName) AS `name`,
	IFNULL(tblaccountno.Bank,'') AS Bank,
	IFNULL(tblaccountno.AccountNo,'') AS AccountNo,
	IFNULL(tblaccountno.bank_code,'') AS BankCode
FROM
	tblemployees
	LEFT JOIN tblaccountno ON tblaccountno.COOPNO = tblemployees.CoopID
WHERE CoopID LIKE :term OR lastname LIKE :term OR firstname LIKE :term OR middlename LIKE :term
LIMIT 10";

try {
    $stmt = $db->prepare($query);
    $term = "%$searchTerm%";
    $stmt->execute(['term' => $term]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $suggestions = [];
    foreach ($result as $row) {
        $data['id'] = $row['CoopID'];
        $data['coopname'] = $row['name'];
        $data['label'] = $row['name'].' - '.$row['CoopID'];
        $data['value'] = $row['CoopID'];
        $data['bank'] = $row['Bank'];
        $data['AccountNo'] = $row['AccountNo'];
        $data['BankCode'] = $row['BankCode'];
        array_push($suggestions, $data);
    }
} catch (PDOException $e) {
    // error_log($e->getMessage());
    $suggestions = [];
}

// Return suggestions as JSON
echo json_encode($suggestions);
?>