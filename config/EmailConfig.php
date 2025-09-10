<?php

require 'vendor/autoload.php'; // Load Composer's autoloader

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
class EmailConfig {
    public static function getSmtpHost() {
        return $_ENV['SMTP_HOST'];
    }

    public static function getSmtpUsername() {
        return $_ENV['SMTP_USERNAME'];
    }

    public static function getSmtpPassword() {
        return $_ENV['SMTP_PASSWORD'];
    }

    public static function getSmtpPort() {
        return $_ENV['SMTP_PORT'];
    }

    public static function getSmtpFrom() {
        return $_ENV['SMTP_FROM'];
    }
}