<?php
require_once('Connections/coop.php');
require_once('oneSginalfunctions.php');

// mysqli_select_db($coop, $database_coop); // Not needed with PDO DSN

$sql = "SELECT * from oneSignal";
try {
    $stmt = $coop->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // $totalRows = $stmt->rowCount();

    if ($row) {
        do {
            $notification = createNotificationPlayer("Come to the coop house to collect your gift", $row['player_id'], $row['coop_id']);
            try {
                $result1 = $apiInstance->createNotification($notification);
                echo $row['coop_id'] . '<br>';
                echo $row['player_id'] . '<br>';
            } catch (Exception $e) {
                 echo "Error: " . $e->getMessage() . "<br>";
            }
        } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
    }
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

print_r($result1);
