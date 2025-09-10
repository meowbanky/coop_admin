<?php
/**
 * Loan Validator
 * Handles validation for loan processing operations
 */

class LoanValidator {
    
    /**
     * Validate loan update data
     */
    public function validateLoanUpdate($data) {
        $errors = [];
        
        // Required fields
        if (empty($data['coop_id'])) {
            $errors[] = 'Employee ID is required';
        }
        
        if (empty($data['loan_amount'])) {
            $errors[] = 'Loan amount is required';
        } elseif (!is_numeric($data['loan_amount']) || $data['loan_amount'] <= 0) {
            $errors[] = 'Loan amount must be a positive number';
        }
        
        if (empty($data['period_id'])) {
            $errors[] = 'Payroll period is required';
        } elseif (!is_numeric($data['period_id']) || $data['period_id'] <= 0) {
            $errors[] = 'Invalid payroll period selected';
        }
        
        // Validate loan amount range
        if (!empty($data['loan_amount']) && is_numeric($data['loan_amount'])) {
            $amount = floatval($data['loan_amount']);
            if ($amount < 1000) {
                $errors[] = 'Minimum loan amount is ₦1,000';
            }
            if ($amount > 10000000) {
                $errors[] = 'Maximum loan amount is ₦10,000,000';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate employee search
     */
    public function validateEmployeeSearch($searchTerm) {
        $errors = [];
        
        if (empty($searchTerm)) {
            $errors[] = 'Search term is required';
        } elseif (strlen($searchTerm) < 2) {
            $errors[] = 'Search term must be at least 2 characters';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate period selection
     */
    public function validatePeriodSelection($periodId) {
        $errors = [];
        
        if (empty($periodId)) {
            $errors[] = 'Payroll period is required';
        } elseif (!is_numeric($periodId) || $periodId <= 0) {
            $errors[] = 'Invalid payroll period selected';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
