<?php
require_once __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

function doSendMessage($to, $message) {
    $api_key = $_ENV['TERMII_API_KEY'];

    if (empty($api_key)) {
        return json_encode(['error' => true, 'message' => 'API key is not set.']);
    }

    $curl = curl_init();
    $country_code = '234';
    $mobilenumber = trim($to);

    // Format phone number
    if (substr($mobilenumber, 0, 1) == '0') {
        $mobilenumber = $country_code . substr($mobilenumber, 1);
    } elseif (substr($mobilenumber, 0, 1) == '+') {
        $mobilenumber = substr($mobilenumber, 1);
    }

    $data = [
        "to" => [$mobilenumber],
        "from" => "OOUTHCOOP",
        "sms" => $message,
        "type" => "plain",
        "channel" => "generic",
        "api_key" => $api_key
    ];

    $post_data = json_encode($data);

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.ng.termii.com/api/sms/send/bulk',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);

    if ($response === false) {
        $error = curl_error($curl);
        curl_close($curl);
        return json_encode(['error' => true, 'message' => $error]);
    }

    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_code !== 200) {
        return json_encode(['error' => true, 'message' => "HTTP error code: $http_code", 'response' => $response]);
    }

    return $response;
}
?>
