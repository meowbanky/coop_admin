<?php
// Database connection using environment variables
require_once __DIR__ . '/../config/EnvConfig.php';

// Load database configuration from .env
$dbConfig = EnvConfig::getDatabaseConfig();
$servername = $dbConfig['host'];
$username = $dbConfig['user'];
$password = $dbConfig['password'];
$dbname = $dbConfig['name'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>