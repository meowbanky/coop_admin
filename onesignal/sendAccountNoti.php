<?php
require_once('Connections/coop.php');
require_once('oneSginalfunctions.php');

mysqli_select_db($coop, $database_coop);

$sql = "SELECT * from oneSignal";
$result = mysqli_query($coop, $sql) or die(mysqli_error($coop));
$row = mysqli_fetch_array($result);
$totalRows = mysqli_num_rows($result);

do {

    $notification = createNotificationPlayer("Come to the coop house to collect your gift", $row['player_id'], $row['coop_id']);
    $result1 = $apiInstance->createNotification($notification);
    echo $row['coop_id'] . '<br>';
    echo $row['player_id'] . '<br>';
} while ($row = mysqli_fetch_array($result));

print_r($result1);
