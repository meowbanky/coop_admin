-- Remove cleartext password storage and support OTP rate limiting.
--
-- Application code no longer writes tblusers_online.PlainPassword; the only
-- reader was update.php, which emailed the password back to the member and has
-- been changed to send the username only. Authentication has always verified
-- against UPassword (see auth_api/models/User.php), so dropping the column does
-- not affect login.
--
-- Run the audit first, then apply. Order matters: deploy the PHP changes BEFORE
-- dropping the column, otherwise in-flight writes will fail.

-- 1. Audit. This form is safe to re-run: querying the column directly would
--    fail with "1054 - Unknown column" once step 2 has already dropped it.
--    Returns 1 while the column still exists, 0 once it is gone.
SELECT COUNT(*) AS plainpassword_column_still_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tblusers_online'
  AND COLUMN_NAME = 'PlainPassword';

--    Only if the above returned 1, run this to size the exposure being removed:
-- SELECT COUNT(*) AS accounts_with_cleartext_password
-- FROM tblusers_online
-- WHERE PlainPassword IS NOT NULL AND PlainPassword != '';

-- 2. Drop the cleartext password column.
--    IF EXISTS makes a re-run a no-op instead of "1091 - Can't DROP ...".
ALTER TABLE tblusers_online
DROP COLUMN IF EXISTS PlainPassword;

-- 3. Indexes backing the OTP throttle in auth_api/utils/OtpThrottle.php, which
--    counts recent rows per email address on every send.
--    IF NOT EXISTS makes a re-run a no-op instead of
--    "1061 - Duplicate key name".
ALTER TABLE tbl_signup_otp
ADD INDEX IF NOT EXISTS idx_signup_otp_email_created (email, created_at);

ALTER TABLE tbl_password_resets
ADD INDEX IF NOT EXISTS idx_password_resets_email_created (email, created_at);
