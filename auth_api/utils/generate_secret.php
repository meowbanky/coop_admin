<?php
// utils/generate_secret.php

// Method 1: Using random_bytes (Recommended)
$secret = bin2hex(random_bytes(32));
echo "Generated Secret Key: " . $secret . "\n";

// Method 2: Using openssl
$openssl_secret = bin2hex(openssl_random_pseudo_bytes(32));
echo "OpenSSL Generated Secret Key: " . $openssl_secret . "\n";