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

-- 1. Audit: confirm nothing still depends on the column.
--    Expect a non-zero count here — that is the exposure being removed.
SELECT COUNT(*) AS accounts_with_cleartext_password
FROM tblusers_online
WHERE PlainPassword IS NOT NULL AND PlainPassword != '';

-- 2. Drop the cleartext password column.
ALTER TABLE tblusers_online
DROP COLUMN PlainPassword;

-- 3. Indexes backing the OTP throttle in auth_api/utils/OtpThrottle.php, which
--    counts recent rows per email address on every send.
ALTER TABLE tbl_signup_otp
ADD INDEX idx_signup_otp_email_created (email, created_at);

ALTER TABLE tbl_password_resets
ADD INDEX idx_password_resets_email_created (email, created_at);
