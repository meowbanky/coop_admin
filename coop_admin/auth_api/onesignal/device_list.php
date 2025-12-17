<?php
// test_devices.php
require_once __DIR__ . '/../config/Database.php';
$database = new Database();
$db = $database->getConnection();

$appId = "c04a0f15-e70b-4d40-a3c6-284b1898b5b6";
$apiKey = "os_v2_app_ybfa6fphbngubi6gfbfrrgfvwynxspmvfroukifxxpmmrooq2lnc7nuy4rfvonjhqc6kbd5ziwsehccorfoo5w2b7zrispcuzvob2ha";

// Get all registered devices from OneSignal
function getRegisteredDevices() {
    global $appId, $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/players?app_id=$appId&limit=300");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic " . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        return $data['players'] ?? [];
    }
    return [];
}

// Display registered devices
$devices = getRegisteredDevices();
echo "<h2>Registered Devices (" . count($devices) . ")</h2>";
echo "<pre>";
foreach ($devices as $device) {
    echo "Device ID: " . $device['id'] . "\n";
    echo "Device Type: " . $device['device_type'] . "\n";
    echo "Device Model: " . ($device['device_model'] ?? 'Unknown') . "\n";
    echo "Last Active: " . date('Y-m-d H:i:s', $device['last_active']) . "\n";
    echo "Invalid Identifier: " . ($device['invalid_identifier'] ? 'Yes' : 'No') . "\n";
    echo "------------------------\n";
}
echo "</pre>";
?>