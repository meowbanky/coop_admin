<?php

class EnvConfig {
    private static $config = null;
    
    /**
     * Load configuration from .env file
     */
    public static function load() {
        if (self::$config === null) {
            self::$config = [];
            
            $envFile = __DIR__ . '/../.env';
            
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($lines as $lineNum => $line) {
                    // Trim the line
                    $line = trim($line);
                    
                    // Skip empty lines and comments
                    if (empty($line) || strpos($line, '#') === 0) {
                        continue;
                    }
                    
                    // Parse key=value pairs
                    if (strpos($line, '=') !== false) {
                        // Split on first = only (in case value contains =)
                        $parts = explode('=', $line, 2);
                        
                        if (count($parts) === 2) {
                            $key = trim($parts[0]);
                            $value = trim($parts[1]);
                            
                            // Skip if key is empty
                            if (empty($key)) {
                                continue;
                            }
                            
                            // Handle quoted values (both single and double quotes)
                            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                                $value = substr($value, 1, -1);
                            }
                            
                            // Store the value (even if empty, as empty string is valid)
                            self::$config[$key] = $value;
                        }
                    }
                }
            }
        }
        
        return self::$config;
    }
    
    /**
     * Get a configuration value
     */
    public static function get($key, $default = null) {
        $config = self::load();
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    /**
     * Get OpenAI API key
     */
    public static function getOpenAIKey() {
        return self::get('OPENAI_API_KEY');
    }
    
    /**
     * Check if OpenAI key is configured
     */
    public static function hasOpenAIKey() {
        $key = self::getOpenAIKey();
        return !empty($key) && $key !== 'your_openai_api_key_here';
    }
    
    /**
     * Get database configuration from .env file
     * Throws exception if required values are missing
     */
    public static function getDatabaseConfig() {
        $envFile = __DIR__ . '/../.env';
        
        // Check if .env file exists
        if (!file_exists($envFile)) {
            throw new \Exception('.env file not found at: ' . $envFile . '. Please create the .env file with database configuration.');
        }
        
        $host = self::get('DB_HOST');
        $name = self::get('DB_NAME');
        $user = self::get('DB_USER');
        $password = self::get('DB_PASSWORD');
        
        // Collect missing keys for better error message
        $missing = [];
        if (empty($host)) {
            $missing[] = 'DB_HOST';
        }
        if (empty($name)) {
            $missing[] = 'DB_NAME';
        }
        if (empty($user)) {
            $missing[] = 'DB_USER';
        }
        if ($password === null || $password === '') {
            $missing[] = 'DB_PASSWORD';
        }
        
        // Throw detailed error if any keys are missing
        if (!empty($missing)) {
            $missingList = implode(', ', $missing);
            throw new \Exception('Missing required database configuration in .env file: ' . $missingList . '. Please ensure these keys are set in: ' . $envFile);
        }
        
        return [
            'host' => $host,
            'name' => $name,
            'user' => $user,
            'password' => $password
        ];
    }
    
    /**
     * Get file upload configuration
     */
    public static function getUploadConfig() {
        return [
            'max_size' => self::get('MAX_FILE_SIZE', '10MB'),
            'allowed_types' => explode(',', self::get('ALLOWED_FILE_TYPES', 'pdf,xlsx,xls,jpg,jpeg,png'))
        ];
    }
    
    /**
     * Get Google Maps API key
     */
    public static function getGoogleMapsApiKey() {
        return self::get('GOOGLE_MAPS_API_KEY', '');
    }
    
    /**
     * Get API Secret for authentication
     */
    public static function getAPISecret() {
        return self::get('API_SECRET', '');
    }
} 