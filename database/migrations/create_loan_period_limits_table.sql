-- Database Schema for Loan Period Limits
-- Admin can set monthly loan goals/limits per payroll period

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table: loan_period_limits
-- Stores admin-set loan limits for each payroll period
CREATE TABLE IF NOT EXISTS loan_period_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_id INT NOT NULL COMMENT 'Payroll period ID from salary API',
    limit_amount DECIMAL(15, 2) NOT NULL COMMENT 'Maximum total loan amount allowed for this period',
    set_by VARCHAR(255) NOT NULL COMMENT 'Admin username who set this limit',
    notes TEXT NULL COMMENT 'Optional notes about this limit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_period (period_id),
    UNIQUE KEY unique_period_limit (period_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin-set loan limits per payroll period';

SET FOREIGN_KEY_CHECKS = 1;

