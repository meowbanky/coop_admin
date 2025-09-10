<?php
function generateStrongPassword($length = 6)
{
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $password .= $characters[$index];
    }

    return $password;
}

function checkEmailExists($email, $domain, $cpanelUsername, $apiToken)
{
    $apiUrl = "https://{$domain}:2083/execute/Email/list_pops";

    $headers = [
        "Authorization: cpanel {$cpanelUsername}:{$apiToken}",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        return false;
    }

    curl_close($ch);

    $decodedResponse = json_decode($response, true);

    if (isset($decodedResponse['status']) && $decodedResponse['status'] === 1) {
        $existingEmails = array_column($decodedResponse['data'], 'email');
        return in_array($email, $existingEmails);
    } else {
        echo 'Failed to retrieve email list. Response: ' . $response . PHP_EOL;
        return false;
    }
}

function createEmail($postEmail)
{
    // Generate a strong password
    $password = generateStrongPassword(10);

    // Configuration (replace with secure environment variables or configuration)
    $apiToken = getenv('CPANEL_API_TOKEN') ?: '7IJBJPYHKF15Z41YTPKPOBEN9BNHP7JL';
    $cpanelUsername = getenv('CPANEL_USERNAME') ?: 'oouthco';
    $domain = 'oouth.com';
    $quota = 250; // Mailbox size in MB

    // Determine email address
    $email = strpos($postEmail, '@' . $domain) !== false ? $postEmail : $postEmail . '@' . $domain;

    // Check if the email already exists
    if (checkEmailExists($email, $domain, $cpanelUsername, $apiToken)) {
        echo 'Email already exists: ' . $email . PHP_EOL;
        return;
    }

    // API endpoint for email creation
    $apiUrl = "https://{$domain}:2083/execute/Email/add_pop";

    // Data to be sent in the POST request
    $data = [
        'domain' => $domain,
        'email' => $email,
        'password' => $password,
        'quota' => $quota,
    ];

    // Headers including the API token
    $headers = [
        "Authorization: cpanel {$cpanelUsername}:{$apiToken}",
    ];

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the cURL session
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
    } else {
        $decodedResponse = json_decode($response, true);
        if (isset($decodedResponse['status']) && $decodedResponse['status'] === 1) {
            echo 'Email created successfully: ' . $email . PHP_EOL;
            echo 'Password: ' . $password . PHP_EOL;
        } else {
            echo 'Failed to create email. Response: ' . $response . PHP_EOL;
        }
    }

    // Close the cURL session
    curl_close($ch);
}

// Example usage
$postEmail = "adeko.oloruntoba"; // Replace with input email
createEmail($postEmail);
?>
