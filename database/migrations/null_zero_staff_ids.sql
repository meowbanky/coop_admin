-- Clear placeholder StaffID values so the unique index in
-- add_unique_constraints_to_employees.sql can be applied.
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
--
-- Run steps 1-3 and read the output before running step 4.

-- 1. How many members are affected, and who are they?
SELECT CoopID, FirstName, LastName, Department, Status
FROM tblemployees
WHERE StaffID = 0
ORDER BY LastName, FirstName;

-- 2. Are there accounting rows currently keyed to the placeholder? Anything
--    returned here is data that was being shared across those members and needs
--    to be reassigned to the correct member by hand.
SELECT periodid, account_type, COUNT(*) AS rows_affected
FROM coop_member_accounts
WHERE memberid = 0
GROUP BY periodid, account_type;

-- 3. Any other duplicate StaffIDs besides 0? These must be resolved separately;
--    this migration only clears zeros.
SELECT StaffID, COUNT(*) AS occurrences
FROM tblemployees
WHERE StaffID IS NOT NULL AND StaffID != 0
GROUP BY StaffID
HAVING COUNT(*) > 1;

-- 4. Apply. The column must accept NULL first; adjust the type below if your
--    StaffID column is not INT (check with: SHOW COLUMNS FROM tblemployees LIKE 'StaffID';).
ALTER TABLE tblemployees
MODIFY COLUMN StaffID INT NULL COMMENT 'Payroll/member number; NULL when not yet assigned';

UPDATE tblemployees
SET StaffID = NULL
WHERE StaffID = 0;

-- 5. Now the unique index can be created. MySQL permits multiple NULLs in a
--    UNIQUE index, so every cleared member coexists.
ALTER TABLE tblemployees
ADD UNIQUE INDEX uniq_employees_staff_id (StaffID);
