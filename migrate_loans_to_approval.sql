/*
 * Migration Script: Insert loans from tbl_loans to tbl_loanapproval
 * Only inserts loans where LoanAmount is an integer (no decimal part)
 * 
 * Date: 31/10/2025
 */

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Insert loans with integer amounts only
INSERT INTO tbl_loanapproval (coopID, period, LoanAmount, approvalDate)
SELECT 
    CoopID,
    LoanPeriod,
    LoanAmount,
    DateOfLoanApp
FROM tbl_loans
WHERE LoanAmount = FLOOR(LoanAmount)  -- Only integer amounts (no decimals)
  AND LoanAmount IS NOT NULL
  AND CoopID IS NOT NULL
  AND LoanPeriod IS NOT NULL
  AND LoanAmount > 0;

SET FOREIGN_KEY_CHECKS = 1;

-- Check results
SELECT COUNT(*) as 'Total Inserted Loans' FROM tbl_loanapproval WHERE approvalDate >= CURDATE() - INTERVAL 1 MINUTE;

