-- Add approval fields to loan_requests table
-- Supports partial approval and outstanding loan tracking

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Add approved_amount column (can be different from requested_amount)
ALTER TABLE loan_requests 
ADD COLUMN IF NOT EXISTS approved_amount DECIMAL(15, 2) NULL COMMENT 'Amount actually approved by admin (can be less than requested_amount)' AFTER requested_amount;

-- Add outstanding_amount column (requested - approved)
ALTER TABLE loan_requests 
ADD COLUMN IF NOT EXISTS outstanding_amount DECIMAL(15, 2) NULL COMMENT 'Outstanding amount (requested_amount - approved_amount) that can be imported to next period' AFTER approved_amount;

-- Add approved_by column
ALTER TABLE loan_requests 
ADD COLUMN IF NOT EXISTS approved_by VARCHAR(255) NULL COMMENT 'Admin username who approved the loan' AFTER outstanding_amount;

-- Add approved_at column
ALTER TABLE loan_requests 
ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL COMMENT 'When the loan was approved by admin' AFTER approved_by;

-- Add index for outstanding loans
ALTER TABLE loan_requests 
ADD INDEX IF NOT EXISTS idx_outstanding (outstanding_amount, status);

SET FOREIGN_KEY_CHECKS = 1;

