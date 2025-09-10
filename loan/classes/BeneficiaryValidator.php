<?php

class BeneficiaryValidator {
    
    /**
     * Validate beneficiary data
     */
    public function validateBeneficiaryData($data) {
        $errors = [];
        
        // Required fields validation
        if (empty($data['CoopName'])) {
            $errors[] = 'Beneficiary name is required';
        }
        
        if (empty($data['txtCoopid'])) {
            $errors[] = 'Cooperative ID is required';
        }
        
        if (empty($data['txtBankName'])) {
            $errors[] = 'Bank name is required';
        }
        
        if (empty($data['txtBankAccountNo'])) {
            $errors[] = 'Account number is required';
        }
        
        if (empty($data['txtbankcode'])) {
            $errors[] = 'Bank code is required';
        }
        
        if (empty($data['txtAmount'])) {
            $errors[] = 'Amount is required';
        }
        
        if (empty($data['txNarration'])) {
            $errors[] = 'Narration is required';
        }
        
        // Amount validation
        if (!empty($data['txtAmount'])) {
            $amount = str_replace(',', '', $data['txtAmount']);
            if (!is_numeric($amount) || $amount <= 0) {
                $errors[] = 'Amount must be a valid positive number';
            }
        }
        
        // Account number validation (should be numeric)
        if (!empty($data['txtBankAccountNo'])) {
            if (!is_numeric($data['txtBankAccountNo'])) {
                $errors[] = 'Account number must contain only numbers';
            }
        }
        
        // Bank code validation (should be numeric)
        if (!empty($data['txtbankcode'])) {
            if (!is_numeric($data['txtbankcode'])) {
                $errors[] = 'Bank code must contain only numbers';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Format amount for display
     */
    public function formatAmount($amount) {
        return number_format($amount, 2, '.', ',');
    }
    
    /**
     * Parse amount from formatted string
     */
    public function parseAmount($amount) {
        return floatval(str_replace(',', '', $amount));
    }
}
