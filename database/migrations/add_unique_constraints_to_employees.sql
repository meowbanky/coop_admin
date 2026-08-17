-- Enforce member identity uniqueness at the database level.
--
-- Application code checks these before inserting, but without the constraints
-- two concurrent admin submissions can still both pass the check and insert
-- duplicates. The CoopID retry loop in api/employee.php relies on the unique
-- index raising a duplicate-key error to detect a collision.
--
-- IMPORTANT: run the duplicate audits below FIRST. The ALTERs will fail if any
-- duplicates already exist; clean them up before applying.

-- 1. Audit: existing duplicate CoopIDs
SELECT CoopID, COUNT(*) AS occurrences
FROM tblemployees
GROUP BY CoopID
HAVING COUNT(*) > 1;

-- 2. Audit: existing duplicate StaffIDs
SELECT StaffID, COUNT(*) AS occurrences
FROM tblemployees
GROUP BY StaffID
HAVING COUNT(*) > 1;

-- 3. Audit: existing duplicate email addresses (blanks excluded — the mobile
--    signup flow treats a blank EmailAddress as "not yet registered")
SELECT EmailAddress, COUNT(*) AS occurrences
FROM tblemployees
WHERE EmailAddress IS NOT NULL AND EmailAddress != ''
GROUP BY EmailAddress
HAVING COUNT(*) > 1;

-- 4. Apply the constraints once the audits above return no rows.
--    APPLIED — uniq_employees_coop_id is live. This is the index the CoopID
--    collision retry in api/employee.php depends on.
ALTER TABLE tblemployees
ADD UNIQUE INDEX uniq_employees_coop_id (CoopID);

-- NOT APPLICABLE: a UNIQUE index on StaffID. StaffID is half of the composite
-- PRIMARY KEY (CoopID, StaffID), so duplicate StaffID values are legal by design
-- and the placeholder zeros cannot be nulled out of the way. Attempting it gives
-- "1062 - Duplicate entry '0' for key 'uniq_employees_staff_id'". StaffID
-- uniqueness is enforced in api/employee.php instead — see
-- fix_placeholder_staff_ids.sql for the full explanation and the remedy.

-- Email is intentionally a plain index, not unique: historical records may share
-- a blank value, and MySQL treats '' as a real duplicate (unlike NULL).
-- Uniqueness for non-blank emails is enforced in api/employee.php.
ALTER TABLE tblemployees
ADD INDEX idx_employees_email (EmailAddress);
