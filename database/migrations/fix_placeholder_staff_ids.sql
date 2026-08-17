-- Placeholder StaffID = 0 values: why they cannot be nulled, and what to do.
--
-- Supersedes null_zero_staff_ids.sql, which attempted to clear the zeros so a
-- UNIQUE index could be added to StaffID. That approach is impossible on this
-- schema. From SHOW CREATE TABLE tblemployees:
--
--     PRIMARY KEY (`CoopID`,`StaffID`) USING BTREE,
--     UNIQUE KEY `uniq_employees_coop_id` (`CoopID`),
--
-- StaffID is half of a COMPOSITE PRIMARY KEY. Every column in a primary key
-- must be NOT NULL, so StaffID can never hold NULL. This also explains how the
-- duplicate zeros arose: the pair (CoopID, StaffID) is unique, so any number of
-- members may share StaffID = 0 as long as their CoopID differs.
--
-- Three consequences:
--
--   1. Do NOT add a UNIQUE index on StaffID. Nothing in the application needs
--      one. The CoopID collision retry in api/employee.php depends only on
--      uniq_employees_coop_id, which is already in place. New duplicates are
--      blocked at the application layer by staffIdExists() in api/employee.php,
--      which now also rejects StaffID <= 0.
--
--   2. CoopID alone is unique, so keying reads and updates on CoopID alone is
--      correct. api/employee.php was changed to do this; the old
--      "WHERE CoopID = ? AND StaffID = ?" was matching the composite PK.
--
--   3. The real remedy for the zeros is to assign each affected member a
--      genuine StaffID. Steps 2 and 3 below support that.

-- ---------------------------------------------------------------------------
-- 1. Correct the column comment left behind by the abandoned migration.
--    That ALTER applied its COMMENT but could not apply NULL, so the column now
--    documents a state it cannot hold. Type and nullability below match the
--    live schema exactly — do not alter them.
-- ---------------------------------------------------------------------------
ALTER TABLE tblemployees
MODIFY COLUMN StaffID INT(11) NOT NULL
COMMENT 'Payroll/member number. Part of the composite PK with CoopID, so it cannot be NULL; 0 is a legacy placeholder meaning unassigned.';

-- ---------------------------------------------------------------------------
-- 2. Which members still carry the placeholder? These need real StaffIDs.
-- ---------------------------------------------------------------------------
SELECT CoopID, FirstName, LastName, Department, Status, EmailAddress
FROM tblemployees
WHERE StaffID = 0
ORDER BY Status, LastName, FirstName;

-- ---------------------------------------------------------------------------
-- 3. THE ACTUAL RISK. coop_member_accounts.memberid joins to
--    tblemployees.StaffID (libs/services/MemberAccountManager.php:170). While
--    several members share StaffID = 0, that join matches EVERY zero-StaffID
--    member against the same memberid = 0 rows, so those members can see each
--    other's balances in statements and member-account reports.
--
--    Anything returned here is shared accounting data that must be reassigned
--    to the correct member once real StaffIDs are issued. An empty result means
--    the placeholder members simply have no accounting rows yet, and assigning
--    StaffIDs is a clean, low-risk operation.
-- ---------------------------------------------------------------------------
SELECT periodid, account_type, COUNT(*) AS rows_affected, SUM(closing_balance) AS total_closing
FROM coop_member_accounts
WHERE memberid = 0
GROUP BY periodid, account_type
ORDER BY periodid;

-- ---------------------------------------------------------------------------
-- 4. Assigning a real StaffID, one member at a time.
--    Updating a primary-key column is permitted; verify the target is free
--    first. Run the SELECT, confirm it returns nothing, then run the UPDATE.
--    Prefer doing this through the admin Edit form, which runs the same
--    uniqueness and "greater than zero" checks automatically.
-- ---------------------------------------------------------------------------
-- SELECT CoopID, FirstName, LastName FROM tblemployees WHERE StaffID = <new_id>;
--
-- UPDATE tblemployees SET StaffID = <new_id> WHERE CoopID = '<coop_id>' AND StaffID = 0;

-- ---------------------------------------------------------------------------
-- 5. Optional cleanup. KEY `CoopID` duplicates uniq_employees_coop_id and the
--    leading column of the primary key, so it earns nothing and costs write
--    time. Safe to drop; skip if you would rather not touch indexes right now.
-- ---------------------------------------------------------------------------
-- ALTER TABLE tblemployees DROP INDEX `CoopID`;
