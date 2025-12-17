-- Database Schema for Loan Request Workflow (Simplified Version)
-- This version creates tables without foreign keys to avoid constraint errors
-- Foreign keys can be added manually after verifying the tblemployees.CoopID data type

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table: loan_requests
-- Stores loan request information
CREATE TABLE IF NOT EXISTS loan_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_coop_id VARCHAR(255) NOT NULL COMMENT 'CoopID of the member requesting the loan',
    requested_amount DECIMAL(15, 2) NOT NULL COMMENT 'Amount requested',
    monthly_repayment DECIMAL(15, 2) NOT NULL COMMENT 'Monthly repayment amount (10% of requested)',
    payslip_file_path VARCHAR(500) NULL COMMENT 'Path to uploaded payslip file',
    status ENUM(
        'draft',
        'pending_guarantors',
        'partially_guaranteed',
        'fully_guaranteed',
        'rejected',
        'submitted',
        'approved',
        'disbursed',
        'cancelled'
    ) DEFAULT 'draft' COMMENT 'Current status of the loan request',
    staff_id INT NULL COMMENT 'Staff ID linked to coop_id',
    deduction_id INT NULL COMMENT 'Deduction ID for coop',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL COMMENT 'When the request was submitted to admin',
    INDEX idx_requester (requester_coop_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Loan requests from members';

-- Table: guarantor_requests
-- Stores guarantor request information
CREATE TABLE IF NOT EXISTS guarantor_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_request_id INT NOT NULL COMMENT 'Reference to loan_requests table',
    guarantor_coop_id VARCHAR(255) NOT NULL COMMENT 'CoopID of the guarantor',
    requester_name VARCHAR(255) NOT NULL COMMENT 'Name of the member making the request',
    requested_amount DECIMAL(15, 2) NOT NULL COMMENT 'Loan amount requested',
    monthly_repayment DECIMAL(15, 2) NOT NULL COMMENT 'Monthly repayment amount',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT 'Status of guarantor response',
    response_date TIMESTAMP NULL COMMENT 'When guarantor responded',
    response_notes TEXT NULL COMMENT 'Optional notes from guarantor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_loan_request (loan_request_id),
    INDEX idx_guarantor (guarantor_coop_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_loan_guarantor (loan_request_id, guarantor_coop_id),
    FOREIGN KEY (loan_request_id) REFERENCES loan_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Guarantor requests for loan applications';

SET FOREIGN_KEY_CHECKS = 1;

-- To add foreign keys to tblemployees (CoopID is VARCHAR(255)), run:
--
-- ALTER TABLE loan_requests 
--   ADD CONSTRAINT fk_loan_requests_requester 
--   FOREIGN KEY (requester_coop_id) 
--   REFERENCES tblemployees(CoopID) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;
--
-- ALTER TABLE guarantor_requests 
--   ADD CONSTRAINT fk_guarantor_requests_guarantor 
--   FOREIGN KEY (guarantor_coop_id) 
--   REFERENCES tblemployees(CoopID) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;
--
-- Note: The columns are already VARCHAR(255) to match tblemployees.CopID, so you can add foreign keys directly:
-- ALTER TABLE loan_requests 
--   ADD CONSTRAINT fk_loan_requests_requester 
--   FOREIGN KEY (requester_coop_id) 
--   REFERENCES tblemployees(CoopID) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;
--
-- ALTER TABLE guarantor_requests 
--   ADD CONSTRAINT fk_guarantor_requests_guarantor 
--   FOREIGN KEY (guarantor_coop_id) 
--   REFERENCES tblemployees(CoopID) 
--   ON DELETE CASCADE 
--   ON UPDATE CASCADE;

