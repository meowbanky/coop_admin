<?php
require_once __DIR__ . '/../../config/Database.php';
$database = new Database();
$db = $database->getConnection();

function saveNotification($db, $staffId, $title, $message) {
    try {
        $sql = "INSERT INTO notifications (staff_id, title, message, status) VALUES (:staff_id, :title, :message, 'unread')";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':staff_id' => $staffId,
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
        $sql = "SELECT tbl_users.staff_id, tbl_users.device_id, employee.NAME FROM tbl_users
                          INNER JOIN employee ON tbl_users.staff_id = employee.staff_id
                          WHERE ISNULL(tbl_users.device_id) = FALSE AND STATUSCD = 'A'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $staffIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Save notification for each staff member
        foreach ($staffIds as $staffId) {
            saveNotification($db, $staffId, $title, $message);
        }
        return true;
    } catch (Exception $e) {
        error_log("Error saving notifications for all: " . $e->getMessage());
        return false;
    }
}

function sendNotificationToDevice($deviceId, $title, $message) {
    $appId = "c04a0f15-e70b-4d40-a3c6-284b1898b5b6";
    $apiKey = "os_v2_app_ybfa6fphbngubi6gfbfrrgfvwynxspmvfroukifxxpmmrooq2lnc7nuy4rfvonjhqc6kbd5ziwsehccorfoo5w2b7zrispcuzvob2ha";

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
    // First save notification for all staff
    saveNotificationForAll($db, $title, $message);

    $appId = "c04a0f15-e70b-4d40-a3c6-284b1898b5b6";
    $apiKey = "os_v2_app_ybfa6fphbngubi6gfbfrrgfvwynxspmvfroukifxxpmmrooq2lnc7nuy4rfvonjhqc6kbd5ziwsehccorfoo5w2b7zrispcuzvob2ha";

    $content = ["en" => $message];
    $headings = ["en" => $title];

    // Modified fields for sending to all users
    $fields = [
        "app_id" => $appId,
        "included_segments" => ["All"],  // Changed to "All" instead of "Subscribed Users"
        "headings" => $headings,
        "contents" => $content,
        "target_channel" => "push",  // Explicitly specify push channel
        "channel_for_external_user_ids" => "push",
        "isAnyWeb" => false,  // Disable web push
        "isIos" => true,      // Enable iOS
        "isAndroid" => true,  // Enable Android
        "large_icon" => "https://oouthsalary.com.ng/assets/images/oouth_logo.png", // Optional: Add your app icon
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
//            echo json_encode([
//                'success' => true,
//                'message' => 'Notification sent successfully!',
//                'recipients' => $responseData['recipients'] ?? 0,
//                'id' => $responseData['id'] ?? null,
//                'response' => $responseData
//            ]);
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