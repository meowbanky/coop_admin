<?php
/**
 * ValidationHelper Class
 * Handles all validation logic
 */

class ValidationHelper {
    
    /**
     * Validate batch number
     */
    public function validateBatchNumber($batchNumber) {
        $errors = [];
        
        if (empty($batchNumber)) {
            $errors[] = 'Batch number is required';
        }
        
        if (!preg_match('/^[A-Za-z0-9]+$/', $batchNumber)) {
            $errors[] = 'Batch number can only contain letters and numbers';
        }
        
        if (strlen($batchNumber) < 3) {
            $errors[] = 'Batch number must be at least 3 characters long';
        }
        
        if (strlen($batchNumber) > 50) {
            $errors[] = 'Batch number cannot exceed 50 characters';
        }
        
        return $errors;
    }
    
    /**
     * Validate email
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate required fields
     */
    public function validateRequired($data, $requiredFields) {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = ucfirst($field) . ' is required';
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize string input
     */
    public function sanitizeString($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize numeric input
     */
    public function sanitizeNumeric($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Validate numeric input
     */
    public function validateNumeric($input, $min = null, $max = null) {
        $errors = [];
        
        if (!is_numeric($input)) {
            $errors[] = 'Value must be a number';
            return $errors;
        }
        
        if ($min !== null && $input < $min) {
            $errors[] = "Value must be at least {$min}";
        }
        
        if ($max !== null && $input > $max) {
            $errors[] = "Value cannot exceed {$max}";
        }
        
        return $errors;
    }
}
?>
