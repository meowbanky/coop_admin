-- Check the data type of CoopID in tblemployees table
-- Run this first to determine the correct data type for foreign keys

SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH,
    CHARACTER_SET_NAME,
    COLLATION_NAME,
    COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'tblemployees'
AND COLUMN_NAME = 'CoopID';

-- Also check if CoopID is a primary key or has an indexx
SHOW INDEXES FROM tblemployees WHERE Column_name = 'CoopID';

