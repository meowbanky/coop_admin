<?php
// Load environment variables
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    // Load the .env file
    $envPath = __DIR__ . '/../../';
    $envFile = $envPath . '.env';
    
    if (file_exists($envFile)) {
        $dotenv = Dotenv::createImmutable($envPath);
        $dotenv->load();
    }
} catch (\Exception $e) {
    error_log("Error loading .env file: " . $e->getMessage());
}

// Get database configuration from environment variables
// Support both DB_AUTH_* and DB_* for compatibility
$dbHost = $_ENV['DB_AUTH_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_AUTH_NAME'] ?? $_ENV['DB_NAME'] ?? '';
$dbUser = $_ENV['DB_AUTH_USER'] ?? $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? '';
$dbPassword = $_ENV['DB_AUTH_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '';

// Get JWT configuration from environment variables
$jwtSecret = $_ENV['JWT_SECRET'] ?? '76acd9e37db202bf33e2641eec29a9de81aff48ce8dea5de05263f6e886123c0';
$jwtExpiry = $_ENV['JWT_EXPIRY'] ?? 3600;

return [
    'database' => [
        'host' => $dbHost,
        'username' => $dbUser,
        'password' => $dbPassword,
        'dbname' => $dbName
    ],
    'jwt' => [
        'secret' => $jwtSecret,
        'expiry' => (int)$jwtExpiry // 1 hour in seconds
    ]
];
