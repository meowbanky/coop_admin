<?php
/**
 * Rate limits OTP issuance so a single address cannot be used to flood the
 * mail queue or brute-force the OTP space by requesting fresh codes.
 */
class OtpThrottle
{
    const MAX_PER_WINDOW = 3;
    const WINDOW_MINUTES = 15;
    const MIN_SECONDS_BETWEEN_REQUESTS = 60;

    // Table is interpolated into SQL, so it must come from this list only.
    private static $allowedTables = ['tbl_signup_otp', 'tbl_password_resets'];

    private $db;
    private $table;

    public function __construct($db, $table)
    {
        if (!in_array($table, self::$allowedTables, true)) {
            throw new InvalidArgumentException('Unknown OTP table: ' . $table);
        }

        $this->db = $db;
        $this->table = $table;
    }

    /**
     * Throws when the address has requested too many codes, or requested one
     * too recently. Counts against created_at so expired codes still count.
     */
    public function assertCanSend($email)
    {
        $sql = "SELECT COUNT(*) AS recent_count,
                       TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS seconds_since_last
                FROM {$this->table}
                WHERE email = :email
                  AND created_at > (NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return;
        }

        $secondsSinceLast = $row['seconds_since_last'];

        if ($secondsSinceLast !== null && (int) $secondsSinceLast < self::MIN_SECONDS_BETWEEN_REQUESTS) {
            $wait = self::MIN_SECONDS_BETWEEN_REQUESTS - (int) $secondsSinceLast;
            throw new Exception('Please wait ' . $wait . ' seconds before requesting another code');
        }

        if ((int) $row['recent_count'] >= self::MAX_PER_WINDOW) {
            throw new Exception(
                'Too many verification codes requested. Please try again in '
                . self::WINDOW_MINUTES . ' minutes'
            );
        }
    }

    /**
     * Six-digit code from a cryptographically secure source.
     */
    public static function generateCode()
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
