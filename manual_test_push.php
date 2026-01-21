<?php
require_once __DIR__ . '/config/EnvConfig.php';
require_once __DIR__ . '/classes/services/NotificationService.php';

use App\Services\NotificationService;

// Setup
$db = null; // Not needed for this method
$service = new NotificationService($db);

// Use Reflection to access private method
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('sendPushNotification');
$method->setAccessible(true);

// Test Data
// REPLACE THIS WITH A REAL PLAYER ID FROM YOUR ONESIGNAL DASHBOARD
$playerId = '2daf6e55-0435-4945-ab58-f42e4ad86c23'; 
$title = 'Test Notification';
$message = 'This is a test message from the admin panel script.';

echo "Attempting to send push notification...\n";
echo "Player ID: $playerId\n";

try {
    if ($playerId === 'REPLACE_WITH_REAL_PLAYER_ID') {
        throw new Exception("Please edit this file and replace 'REPLACE_WITH_REAL_PLAYER_ID' with a valid OneSignal Player ID.");
    }

    $result = $method->invoke($service, $playerId, $title, $message);
    
    echo "Result:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
