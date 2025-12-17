<?php
function initSession() {
    // Get the configured session save path
    $sessionPath = ini_get('session.save_path');
    
    // If path is set and doesn't exist or isn't writable, use fallback
    if (!empty($sessionPath) && (!is_dir($sessionPath) || !is_writable($sessionPath))) {
        // Try to create the directory
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0755, true);
        }
        
        // If still not writable, use fallback directory
        if (!is_writable($sessionPath)) {
            // Use a local sessions directory
            $fallbackPath = __DIR__ . '/../sessions';
            if (!is_dir($fallbackPath)) {
                @mkdir($fallbackPath, 0755, true);
            }
            
            // Only use fallback if it's writable
            if (is_writable($fallbackPath)) {
                ini_set('session.save_path', $fallbackPath);
            } else {
                // Last resort: use system temp directory
                ini_set('session.save_path', sys_get_temp_dir());
            }
        }
    }
    
    // Start the session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
?>