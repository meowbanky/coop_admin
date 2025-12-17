<?php
require_once __DIR__ . '/../config/Database.php';
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('I am here');

    $title = $_POST['title'];
    $message = $_POST['message'];

    // Check if sending to specific staff or all subscribers
    if (isset($_POST['membersDevice']) && $_POST['membersDevice'] !== 'all') {
        // Send to specific staff
        list($coopid, $deviceId) = explode(',', $_POST['membersDevice']);

        // Save the notification to the database for specific staff
        saveNotification($db, $coopid, $title, $message);

        // Send to specific device
        sendNotificationToDevice($deviceId, $title, $message);
    } else {
        // Send to all subscribers
        sendNotificationToAll($db, $title, $message);
    }
}

function saveNotification($db, $coopid, $title, $message) {
    try {
        $sql = "INSERT INTO notifications (coop_id, title, message, status) VALUES (:staff_id, :title, :message, 'unread')";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':staff_id' => $coopid,
            ':title' => $title,
            ':message' => $message,
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error saving notification: " . $e->getMessage());
        return false;
    }
}

function saveNotificationForAll($db, $title, $message) {
    try {
        // Get all staff IDs from your employees table
        $sql = "SELECT CoopID, onesignal_id, concat(tblemployees.LastName,' ',tblemployees.LastName) as Name FROM tblemployees
                WHERE ISNULL(tblemployees.onesignal_id) = FALSE AND tblemployees.onesignal_id <> '' AND Status = 'Active'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $coopids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Save notification for each staff member
        foreach ($coopids as $coopid) {
            saveNotification($db, $coopid, $title, $message);
        }
        return true;
    } catch (Exception $e) {
        error_log("Error saving notifications for all: " . $e->getMessage());
        return false;
    }
}

function sendNotificationToDevice($deviceId, $title, $message) {
    $appId = "aeef154f-9807-4dff-b7a6-d215ac0c1281";
    $apiKey = "os_v2_app_v3xrkt4ya5g77n5g2ik2ydasqfuwqyu5tz4uovun25qlklavydx2gpkufgp7rpjraxumfmzejgg2m5jl26saqgtizfk5h3h62cbqkpq";

    $content = ["en" => $message];
    $headings = ["en" => $title];

    $fields = [
        "app_id" => $appId,
        "include_player_ids" => [$deviceId],
        "headings" => $headings,
        "contents" => $content
    ];

    return sendOneSignalNotification($fields, $apiKey);
}

function sendNotificationToAll($db, $title, $message) {
    if (empty($title) || empty($message)) {
        error_log("Invalid notification: Title or message is empty");
        return false;
    }

    // First save notification for all staff
    saveNotificationForAll($db, $title, $message);

    $appId = "aeef154f-9807-4dff-b7a6-d215ac0c1281";
    $apiKey = "os_v2_app_v3xrkt4ya5g77n5g2ik2ydasqfuwqyu5tz4uovun25qlklavydx2gpkufgp7rpjraxumfmzejgg2m5jl26saqgtizfk5h3h62cbqkpq";

    $content = ["en" => $message];
    $headings = ["en" => $title];

    // Modified fields for sending to all users
    $fields = [
        "app_id" => $appId,
        "included_segments" => ["All"],
        "headings" => $headings,
        "contents" => $content,
        "target_channel" => "push",  // Explicitly specify push channel
        "channel_for_external_user_ids" => "push",
        "isAnyWeb" => false,  // Disable web push
        "isIos" => false,      // Enable iOS
        "isAndroid" => true,  // Enable Android
        "large_icon" => "https://emmaggi.com/coop_admin/images/oouth_coop_logo.png", // Optional: Add your app icon
        "priority" => 10,     // High priority
        "data" => [          // Optional: Add custom data
            "type" => "broadcast",
            "timestamp" => time()
        ]
    ];

    // Debug log before sending
    error_log("Sending OneSignal notification with fields: " . json_encode($fields));

    return sendOneSignalNotification($fields, $apiKey);
}

function sendOneSignalNotification($fields, $apiKey) {
    try {
        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json; charset=utf-8",
            "Authorization: Basic " . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Debug log for request
        error_log("OneSignal Request Headers: " . json_encode(curl_getinfo($ch)));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Debug log for response
        error_log("OneSignal Response Code: $httpCode");
        error_log("OneSignal Response: $response");

        if (curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            echo json_encode([
                'success' => false,
                'message' => "Curl error: " . curl_error($ch)
            ]);
            return false;
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode == 200) {
            // Add more detailed success response
            echo json_encode([
                'success' => true,
                'message' => 'Notification sent successfully!',
                'recipients' => $responseData['recipients'] ?? 0,
                'id' => $responseData['id'] ?? null,
                'response' => $responseData
            ]);
            return true;
        } else {
            error_log("OneSignal API Error: $response");
            echo json_encode([
                'success' => false,
                'message' => "Failed to send notification. HTTP code: $httpCode",
                'error' => $responseData['errors'] ?? 'Unknown error',
                'response' => $responseData
            ]);
            return false;
        }
    } catch (Exception $e) {
        error_log("Error sending notification: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => "Error sending notification: " . $e->getMessage()
        ]);
        return false;
    }
}
?>