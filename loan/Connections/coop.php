<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__.'/../../');
$dotenv->load();

// Fetch database credentials from environment variables
$hostname_coop = $_ENV['DB_HOST'];
$database_coop = $_ENV['DB_NAME'];
$username_coop = $_ENV['DB_USERNAME'];
$password_coop = $_ENV['DB_PASSWORD'];

try {
    // Create a new MySQLi connection
    $coop = new mysqli($hostname_coop, $username_coop, $password_coop, $database_coop);
    $db = new PDO("mysql:host=$hostname_coop;dbname=$database_coop;charset=utf8", $username_coop, $password_coop);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check for connection errors
    if ($coop->connect_error) {
        throw new Exception("Database connection failed: " . $coop->connect_error);
    }
} catch (Exception $e) {
    // Display a user-friendly error message
    die("Error connecting to the database. Please try again later.");
}
?>