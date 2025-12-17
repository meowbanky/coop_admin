-- Database Schema for Loan Request Workflow (Auto-detecting CoopID type)
-- This version automatically detects the CoopID data type and creates matching columns

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Get the CoopID column definition from tblemployees
SET @coopid_type = (
    SELECT COLUMN_TYPE 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblemployees'
    AND COLUMN_NAME = 'CoopID'
    LIMIT 1
);

-- If CoopID type not found, use default VARCHAR(50)
SET @coopid_type = IFNULL(@coopid_type, 'VARCHAR(50)');

-- Table: loan_requests
-- Stores loan request information
SET @sql = CONCAT('
CREATE TABLE IF NOT EXISTS loan_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_coop_id ', @coopid_type, ' NOT NULL COMMENT ''CoopID of the member requesting the loan'',
    requested_amount DECIMAL(15, 2) NOT NULL COMMENT ''Amount requested'',
    monthly_repayment DECIMAL(15, 2) NOT NULL COMMENT ''Monthly repayment amount (10% of requested)'',
    payslip_file_path VARCHAR(500) NULL COMMENT ''Path to uploaded payslip file'',
    status ENUM(
        ''draft'',
        ''pending_guarantors'',
        ''partially_guaranteed'',
        ''fully_guaranteed'',
        ''rejected'',
        ''submitted'',
        ''approved'',
        ''disbursed'',
        ''cancelled''
    ) DEFAULT ''draft'' COMMENT ''Current status of the loan request'',
    staff_id INT NULL COMMENT ''Staff ID linked to coop_id'',
    deduction_id INT NULL COMMENT ''Deduction ID for coop'',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL COMMENT ''When the request was submitted to admin'',
    INDEX idx_requester (requester_coop_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT=''Loan requests from members''
');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Table: guarantor_requests
-- Stores guarantor request information
SET @sql = CONCAT('
CREATE TABLE IF NOT EXISTS guarantor_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_request_id INT NOT NULL COMMENT ''Reference to loan_requests table'',
    guarantor_coop_id ', @coopid_type, ' NOT NULL COMMENT ''CoopID of the guarantor'',
    requester_name VARCHAR(255) NOT NULL COMMENT ''Name of the member making the request'',
    requested_amount DECIMAL(15, 2) NOT NULL COMMENT ''Loan amount requested'',
    monthly_repayment DECIMAL(15, 2) NOT NULL COMMENT ''Monthly repayment amount'',
    status ENUM(''pending'', ''approved'', ''rejected'') DEFAULT ''pending'' COMMENT ''Status of guarantor response'',
    response_date TIMESTAMP NULL COMMENT ''When guarantor responded'',
    response_notes TEXT NULL COMMENT ''Optional notes from guarantor'',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_loan_request (loan_request_id),
    INDEX idx_guarantor (guarantor_coop_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_loan_guarantor (loan_request_id, guarantor_coop_id),
    FOREIGN KEY (loan_request_id) REFERENCES loan_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT=''Guarantor requests for loan applications''
');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign keys to tblemployees
-- Check if foreign key already exists before adding
SET @fk_check = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loan_requests'
    AND CONSTRAINT_NAME = 'fk_loan_requests_requester'
);

SET @sql = IF(@fk_check = 0,
    'ALTER TABLE loan_requests 
     ADD CONSTRAINT fk_loan_requests_requester 
     FOREIGN KEY (requester_coop_id) 
     REFERENCES tblemployees(CoopID) 
     ON DELETE CASCADE 
     ON UPDATE CASCADE',
    'SELECT "Foreign key fk_loan_requests_requester already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_check = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'guarantor_requests'
    AND CONSTRAINT_NAME = 'fk_guarantor_requests_guarantor'
);

SET @sql = IF(@fk_check = 0,
    'ALTER TABLE guarantor_requests 
     ADD CONSTRAINT fk_guarantor_requests_guarantor 
     FOREIGN KEY (guarantor_coop_id) 
     REFERENCES tblemployees(CoopID) 
     ON DELETE CASCADE 
     ON UPDATE CASCADE',
    'SELECT "Foreign key fk_guarantor_requests_guarantor already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- Display what CoopID type was detected
SELECT CONCAT('CoopID type detected: ', @coopid_type) AS info;

