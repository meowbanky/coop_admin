-- Clear placeholder StaffID values so a unique index can be applied.
--
-- IF YOU LANDED HERE FROM "1062 - Duplicate entry '0' for key
-- 'uniq_employees_staff_id'" AFTER ALREADY RUNNING THIS FILE:
-- the UPDATE in step 4 did not take. When StaffID is declared NOT NULL and the
-- server is not in strict mode, MySQL silently coerces SET StaffID = NULL back
-- to 0 — the UPDATE reports "rows affected" but every row is still 0, so the
-- index still collides. Step 0 below shows whether that is what happened, and
-- step 4 now runs under STRICT_ALL_TABLES so the failure is loud instead.
--
-- WHY THIS IS SAFE — AND WHY IT MATTERS
--
-- StaffID is the payroll/member key: coop_member_accounts.memberid joins to
-- tblemployees.StaffID (see libs/services/MemberAccountManager.php). With
-- several members sharing StaffID = 0, that join currently matches EVERY
-- zero-StaffID member against the same memberid = 0 rows, so those members can
-- see each other's balances. NULL does not join to anything, so this change
-- replaces cross-matched data with no data, which is the safer of the two.
--
-- Members left with a NULL StaffID will not appear in member statements,
-- member-account reports, or salary validation until a real StaffID is
-- assigned. They are NOT deleted and all other fields are untouched.
--
-- Reversible: every affected row held exactly 0, so
--   UPDATE tblemployees SET StaffID = 0 WHERE StaffID IS NULL;
-- restores the previous state, provided no new StaffIDs were assigned first.

-- ---------------------------------------------------------------------------
-- 0. DIAGNOSE FIRST. Run these four and read the output before anything else.
-- ---------------------------------------------------------------------------

-- 0a. Column definition. Note IS_NULLABLE and DATA_TYPE — the rest of this file
--     assumes an integer type. If DATA_TYPE is varchar/char, STOP and use the
--     string-safe UPDATE in step 4b instead of 4a.
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tblemployees'
  AND COLUMN_NAME = 'StaffID';

-- 0b. Current state. If zero_count is still > 0 after you ran step 4, the
--     coercion described at the top is exactly what happened.
SELECT
    SUM(StaffID = 0)     AS zero_count,
    SUM(StaffID IS NULL) AS null_count,
    COUNT(*)             AS total_rows
FROM tblemployees;

-- 0c. Is the server in strict mode? If STRICT_ALL_TABLES / STRICT_TRANS_TABLES
--     is absent, NULL-into-NOT NULL is coerced silently.
SELECT @@SESSION.sql_mode AS session_sql_mode;

-- 0d. Did a previous attempt leave the index behind? If this returns a row,
--     drop it with step 1 before retrying.
SHOW INDEXES FROM tblemployees WHERE Key_name = 'uniq_employees_staff_id';

-- ---------------------------------------------------------------------------
-- 1. Only if step 0d returned a row.
-- ---------------------------------------------------------------------------
-- ALTER TABLE tblemployees DROP INDEX uniq_employees_staff_id;

-- ---------------------------------------------------------------------------
-- 2. Who is affected, and is their accounting data entangled?
-- ---------------------------------------------------------------------------

-- 2a. The members about to be cleared.
SELECT CoopID, FirstName, LastName, Department, Status
FROM tblemployees
WHERE StaffID = 0
ORDER BY LastName, FirstName;

-- 2b. Accounting rows keyed to the placeholder. Anything returned here was
--     being shared across those members and must be reassigned by hand.
SELECT periodid, account_type, COUNT(*) AS rows_affected
FROM coop_member_accounts
WHERE memberid = 0
GROUP BY periodid, account_type;

-- 2c. Duplicates other than 0. This migration only clears zeros; anything here
--     will still collide and must be resolved separately.
SELECT StaffID, COUNT(*) AS occurrences
FROM tblemployees
WHERE StaffID IS NOT NULL AND StaffID != 0
GROUP BY StaffID
HAVING COUNT(*) > 1;

-- ---------------------------------------------------------------------------
-- 3. Make the column nullable. Match COLUMN_TYPE from step 0a exactly — if it
--    reported int(11) unsigned, write "INT UNSIGNED NULL" here.
-- ---------------------------------------------------------------------------
ALTER TABLE tblemployees
MODIFY COLUMN StaffID INT NULL COMMENT 'Payroll/member number; NULL when not yet assigned';

-- Confirm it took. IS_NULLABLE must now read YES. If it still reads NO, do not
-- continue — the ALTER did not apply and step 4 will silently write zeros again.
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tblemployees'
  AND COLUMN_NAME = 'StaffID';

-- ---------------------------------------------------------------------------
-- 4. Clear the placeholders. Strict mode turns a silent coercion into an error.
-- ---------------------------------------------------------------------------
SET SESSION sql_mode = 'STRICT_ALL_TABLES';

-- 4a. Integer StaffID (the normal case).
UPDATE tblemployees
SET StaffID = NULL
WHERE StaffID = 0;

-- 4b. ONLY if step 0a reported a string type. Do NOT use "WHERE StaffID = 0" on
--     a text column: MySQL coerces the column to a number for that comparison,
--     so 'ABC123' also equals 0 and would be cleared too.
-- UPDATE tblemployees
-- SET StaffID = NULL
-- WHERE TRIM(StaffID) IN ('0', '');

-- Verify before indexing. zero_count MUST be 0. If it is not, stop and re-read
-- step 0a — the column is still NOT NULL.
SELECT
    SUM(StaffID = 0)     AS zero_count,
    SUM(StaffID IS NULL) AS null_count
FROM tblemployees;

-- ---------------------------------------------------------------------------
-- 5. Add the index. MySQL permits multiple NULLs in a UNIQUE index, so every
--    cleared member coexists. Run only once zero_count above reads 0.
-- ---------------------------------------------------------------------------
ALTER TABLE tblemployees
ADD UNIQUE INDEX uniq_employees_staff_id (StaffID);
