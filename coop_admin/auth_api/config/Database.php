<?php
// config/Database.php

require __DIR__. '/../../vendor/autoload.php'; // Load Composer's autoloader

use Dotenv\Dotenv;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        try {
            // Load the .env file
            $envPath = __DIR__.'/../../';
            $envFile = $envPath . '.env';
            
            if (!file_exists($envFile)) {
                throw new Exception('.env file not found at: ' . $envFile);
            }
            
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->load();
        } catch (\Dotenv\Exception\InvalidFileException $e) {
            error_log("Invalid .env file: " . $e->getMessage());
            throw new Exception('Invalid .env file format: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log("Error loading .env file: " . $e->getMessage());
            throw new Exception('Failed to load environment configuration: ' . $e->getMessage());
        }

        // Set database credentials from .env
        // Support both DB_USER and DB_USERNAME for compatibility
        $this->host = $_ENV['DB_HOST'] ?? '';
        $this->db_name = $_ENV['DB_NAME'] ?? '';
        $this->username = $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? '';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        
        // Validate required values
        if (empty($this->host) || empty($this->db_name) || empty($this->username)) {
            $missing = [];
            if (empty($this->host)) $missing[] = 'DB_HOST';
            if (empty($this->db_name)) $missing[] = 'DB_NAME';
            if (empty($this->username)) $missing[] = 'DB_USER/DB_USERNAME';
            throw new Exception('Missing required database configuration in .env: ' . implode(', ', $missing));
        }
    }

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
}