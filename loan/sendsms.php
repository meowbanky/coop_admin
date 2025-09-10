<?php
require_once('Connections/coopSky.php');
require_once '../classes/sendsms.php';

function getSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") {
    switch ($theType) {
        case "text":
            return ($theValue != "") ? "'" . $theValue . "'" : "NULL";
        case "long":
        case "int":
            return ($theValue != "") ? intval($theValue) : "NULL";
        case "double":
            return ($theValue != "") ? doubleval($theValue) : "NULL";
        case "date":
            return ($theValue != "") ? "'" . $theValue . "'" : "NULL";
        case "defined":
            return ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
        default:
            return "NULL";
    }
}

$batchId = isset($_GET['batch']) ? $_GET['batch'] : "-1";

// Check for internet connectivity
if (!$sock = @fsockopen('www.google.com', 80, $num, $error, 5)) {
    echo "<script>alert('THERE IS NO INTERNET CONNECTION NOW!!!'); window.location.href = 'index.php';</script>";
    exit();
}

try {
//    $db = new PDO("mysql:host=$hostname_coopSky;dbname=$database_coopSky;charset=utf8", $username_coopSky, $password_coopSky);
//    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch data
    $query = "SELECT tblemployees.MobileNumber, excel.BeneficiaryCode 
              FROM excel 
              INNER JOIN tblemployees ON tblemployees.CoopID = excel.BeneficiaryCode 
              WHERE batch = :batch";
    $stmt = $db->prepare($query);
    $stmt->execute([':batch' => $batchId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        echo "No records found.";
        exit();
    }

    $total = count($rows);
    $i = 0;

    echo '<div id="progress" style="width:500px; border:1px solid #ccc;"></div>';
    echo '<div id="information"><p align="center"></p></div>';

    foreach ($rows as $row) {
        $i++;
        $recipients = $row['MobileNumber'];
        $message = 'Kindly appear at the Cooperative Secretariat to sign the Loan Register against Loan issued to you via SKYPAY TRANSFER';
        $message = stripslashes($message);
        $message = substr($message, 0, 320);

        $response = doSendMessage($recipients, $message);
        $obj = json_decode($response);
        echo "Response: " . $response;

        if ($obj === null || json_last_error() !== JSON_ERROR_NONE) {
            echo "Failed to decode JSON response: " . json_last_error_msg() . "<br>";
        } else {
            if (isset($obj->message)) {
                echo "Message sent to : $recipients" . $obj->message[0] . "<br>";
            } else {
                echo "No message found in response.<br>";
            }
        }

        // Update progress
        $percent = intval($i / $total * 100);
        echo '<script>
            document.getElementById("progress").innerHTML = `<div style="width:' . $percent . '%; background-color:#ddd;" align="center">' . $percent . '%</div>`;
            document.getElementById("information").innerHTML = "' . $i . ' row(s) processed.";
        </script>';

        flush();
    }

    echo '<script>document.getElementById("information").innerHTML = "Process completed";</script>';
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
