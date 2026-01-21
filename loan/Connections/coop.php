<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__.'/../../');
$dotenv->load();

// Fetch database credentials from environment variables
// Support both DB_USER and DB_USERNAME for compatibility
$hostname_coop = $_ENV['DB_HOST'] ?? 'localhost';
$database_coop = $_ENV['DB_NAME'] ?? '';
$username_coop = $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? '';
$password_coop = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Create a new PDO connection
    $db = new PDO("mysql:host=$hostname_coop;dbname=$database_coop;charset=utf8", $username_coop, $password_coop);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Alias $conn for compatibility if needed (some files might use it)
    $conn = $db; 
    
    // Alias $coop for backward compatibility (used in loan/home.php)
    $coop = $db;

} catch (Exception $e) {
    // Display a user-friendly error message
    die("Error connecting to the database: " . $e->getMessage());
}
?>