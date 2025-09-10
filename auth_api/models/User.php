<?php
class User {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($identifier, $password) {
        try {
            // Input validation
            $identifier = trim($identifier);
            $password = trim($password);

            if (empty($identifier) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Login identifier and password are required'
                ];
            }

            // Determine the type of identifier and format if necessary
            $identifierType = $this->getIdentifierType($identifier);
            if ($identifierType === 'coopid') {
                $identifier = $this->formatCoopId($identifier);
            }

            // Build the query based on the identifier type
            $query = "SELECT 
            e.LastName, 
            e.FirstName, 
            e.CoopID, 
            u.UPassword, 
            e.MobileNumber,
            e.EmailAddress,
            e.StreetAddress,
            e.Town,
            e.State,
            n.nokfirstname AS nok_first_name,
            n.nokmiddlename AS nok_middle_name,
            n.noklastname AS nok_last_name,
            n.noktel AS nok_tel
        FROM tblemployees e
        INNER JOIN tblusers_online u ON e.CoopID = u.Username
        LEFT JOIN nok n ON e.CoopID = n.CoopID
        WHERE ";

            switch ($identifierType) {
                case 'email':
                    $query .= "LOWER(e.EmailAddress) = LOWER(:identifier)";
                    break;
                case 'phone':
                    $query .= "e.MobileNumber = :identifier";
                    break;
                default: // CoopID
                    $query .= "e.CoopID = :identifier";
            }

            $query .= " LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify password
                if (password_verify($password, $row['UPassword'])) {
                    // Check if password needs rehash
                    if (password_needs_rehash($row['UPassword'], PASSWORD_DEFAULT)) {
                        $this->updatePasswordHash($row['CoopID'], $password);
                    }

                    // Log successful login with identifier type
                    error_log("Successful login for {$row['CoopID']} using $identifierType");

                    return [
                        'success' => true,
                        'user' => [
                            'CoopID' => $row['CoopID'],
                            'FirstName' => $this->sanitizeField($row['FirstName']),
                            'LastName' => $this->sanitizeField($row['LastName']),
                            'EmailAddress' => $this->sanitizeField($row['EmailAddress']),
                            'MobileNumber' => $this->sanitizeField($row['MobileNumber']),
                            'StreetAddress' => $this->sanitizeField($row['StreetAddress']),
                            'Town' => $this->sanitizeField($row['Town']),
                            'State' => $this->sanitizeField($row['State']),
                            'nok_first_name' => $this->sanitizeField($row['nok_first_name']),
                            'nok_middle_name' => $this->sanitizeField($row['nok_middle_name']),
                            'nok_last_name' => $this->sanitizeField($row['nok_last_name']),
                            'nok_tel' => $this->sanitizeField($row['nok_tel']),
                        ]
                    ];
                }

                // Add small delay to prevent timing attacks
                usleep(random_int(5000, 10000));
            }

            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];

        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login'
            ];
        }
    }

    private function sanitizeField($value) {
        // Return empty string for null values
        if ($value === null) {
            return '';
        }
        // Convert to string and sanitize
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    private function formatCoopId($identifier) {
        // If already in correct format, return as is
        if (preg_match('/^COOP-\d{5}$/', $identifier)) {
            return $identifier;
        }

        // Remove any non-numeric characters
        $numericOnly = preg_replace('/[^0-9]/', '', $identifier);

        // Pad with leading zeros to make it 5 digits
        $paddedNumber = str_pad($numericOnly, 5, '0', STR_PAD_LEFT);

        // Return formatted CoopID
        return "COOP-" . $paddedNumber;
    }

    private function getIdentifierType($identifier) {
        // Check if it's an email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        // Remove any spaces, dashes, or other separators
        $cleanIdentifier = preg_replace('/[^0-9]/', '', $identifier);

        // Check if it's a phone number:
        // - Must be exactly 11 digits for Nigerian numbers
        // - Must start with valid Nigerian prefixes (070, 080, 081, 090, 091, etc.)
        if (preg_match('/^(0[789][01])\d{8}$/', $cleanIdentifier)) {
            return 'phone';
        }

        // If it already has COOP- prefix or is a number less than 99999, treat as CoopID
        if (preg_match('/^COOP-\d{5}$/', $identifier) ||
            (is_numeric($cleanIdentifier) && $cleanIdentifier <= 99999)) {
            return 'coopid';
        }

        // For ambiguous cases, check length
        if (strlen($cleanIdentifier) >= 10) {
            return 'phone';
        }

        // Default to CoopID for shorter numbers
        return 'coopid';
    }

    private function updatePasswordHash($userId, $password) {
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE tblusers_online SET UPassword = :password WHERE Username = :userId";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $newHash);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Password rehash error: " . $e->getMessage());
        }
    }
}