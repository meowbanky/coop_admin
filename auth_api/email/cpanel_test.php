<?php
// cpanel_test.php

function testCpanelConnection($url, $username, $password) {
    echo "Testing connection to: $url\n";

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Basic " . base64_encode($username . ":" . $password)
    ]);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);

    $verbose = fopen('php://temp', 'w+');
    curl_setopt($curl, CURLOPT_STDERR, $verbose);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);

    echo "HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 500) . "\n"; // Show first 500 chars
    echo "Verbose Log: $verboseLog\n";

    if (curl_errno($curl)) {
        echo "Curl Error: " . curl_error($curl) . "\n";
    }

    curl_close($curl);
    return $httpCode == 200;
}

// Test WebLagos specific URLs
$urls = [
    "https://server.weblagos.com:2083/json-api/cpanel",
    "https://weblagos.com:2083/json-api/cpanel",
    "https://cpanel.weblagos.com:2083/json-api/cpanel",
    "https://cpanel.oouth.com:2083/json-api/cpanel",
    // Try without port 2083
    "https://server.weblagos.com/cpanel",
    "https://weblagos.com/cpanel",
    "https://cpanel.weblagos.com/cpanel"
];

$username = 'oouthco';  // Replace with your cPanel username
$password = 'Banky@123';  // Replace with your cPanel password

foreach ($urls as $url) {
    echo "\nTesting URL: $url\n";
    echo "----------------------------------------\n";
    $result = testCpanelConnection($url, $username, $password);
    echo "Result: " . ($result ? "Success" : "Failed") . "\n";
    echo "----------------------------------------\n";
}
?>