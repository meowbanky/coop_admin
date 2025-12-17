-- Add period_id column to loan_requests table
-- This prevents multiple loan requests for the same period

-- Check if column exists before adding
SET @dbname = DATABASE();
SET @tablename = "loan_requests";
SET @columnname = "period_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column already exists.'",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT NULL COMMENT 'Payroll period ID from remote salary API' AFTER deduction_id")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index for period_id lookups (check if exists first)
SET @indexname = "idx_period_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  "SELECT 'Index already exists.'",
  CONCAT("ALTER TABLE ", @tablename, " ADD INDEX ", @indexname, " (period_id)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Note: Unique constraint for preventing multiple requests per period is handled in application logic
-- MySQL doesn't support filtered unique constraints, so we check in PHP code instead

