<?php
require_once('Connections/coop.php');
require_once('oneSginalfunctions.php');

// mysqli_select_db($coop, $database_coop); // Not needed with PDO DSN

$sql = "SELECT * from oneSignal";
try {
    $stmt = $coop->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    //$totalRows = $stmt->rowCount();

    if ($row) {
        do {
            if ($row['player_id'] != 0) {
                $notification = createNotificationPlayer("Happy New Month", $row['player_id'], $row['coop_id']);
                try {
                    $result1 = $apiInstance->createNotification($notification);
                    echo $row['coop_id'] . '<br>';
                    echo $row['player_id'] . '<br>';
                    echo print_r($result1, true) . '<br>';
                } catch (Exception $e) {
                    echo "Error sending notification to " . $row['player_id'] . ": " . $e->getMessage() . "<br>";
                }
            }
        } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
    }
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

print_r($result1);
