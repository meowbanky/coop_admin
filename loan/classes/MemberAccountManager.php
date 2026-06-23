<?php

class MemberAccountManager {
    private $connection;
    private $database;
    
    public function __construct($connection, $database) {
        $this->connection = $connection;
        $this->database = $database;
    }
    
    /**
     * Get member details with account information
     */
    public function getMemberDetails($coopId) {
        try {
            $sql = "SELECT 
                        e.CoopID,
                        CONCAT(e.FirstName, ' ', e.MiddleName, ' ', e.LastName) AS FullName,
                        e.FirstName,
                        e.MiddleName,
                        e.LastName,
                        e.EmailAddress,
                        e.MobileNumber,
                        e.StreetAddress,
                        e.Department,
                        e.JobPosition,
                        a.Bank,
                        a.AccountNo,
                        a.bank_code,
                        bc.BankCode
                    FROM tblemployees e
                    LEFT JOIN tblaccountno a ON e.CoopID = a.COOPNO
                    LEFT JOIN tblbankcode bc ON a.Bank = bc.bank
                    WHERE e.CoopID = ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$coopId]);
            
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $member ?: null;
        } catch (Exception $e) {
            error_log("Error fetching member details: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all banks
     */
    public function getBanks() {
        try {
            $sql = "SELECT Bank_Name AS bank, bank_code AS bankcode FROM Bank_Sortcodes ORDER BY Bank_Name";
            $stmt = $this->connection->query($sql);

            if (!$stmt) {
                 throw new Exception("Query failed");
            }

            $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $banks;
        } catch (Exception $e) {
            error_log("Error fetching banks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search members for autocomplete
     */
    public function searchMembers($query) {
        try {
            // $query = mysqli_real_escape_string($this->connection, $query); // PDO handles escaping via placeholders
            $searchTerm = "%$query%";
            
            $sql = "SELECT 
                        CoopID,
                        CONCAT(CoopID, ' - ', FirstName, ' ', MiddleName, ' ', LastName) AS FullName,
                        FirstName,
                        MiddleName,
                        LastName
                    FROM tblemployees 
                    WHERE CoopID LIKE ? 
                    OR FirstName LIKE ? 
                    OR LastName LIKE ? 
                    OR MiddleName LIKE ?
                    ORDER BY FirstName, LastName
                    LIMIT 10";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            
            $members = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $members[] = [
                    'value' => $row['CoopID'],
                    'label' => $row['FullName'],
                    'coopid' => $row['CoopID'],
                    'fullname' => $row['FullName']
                ];
            }
            
            return $members;
        } catch (Exception $e) {
            error_log("Error searching members: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update member account details
     */
    public function updateMemberAccount($data) {
        try {
            // Sanitize input data
            $coopId = $data['coop_id'];
            $bank = $data['bank'];
            $accountNo = $data['account_no'];
            $bankCode = $data['bank_code'];
            
            // Check if member exists
            $checkMember = "SELECT CoopID FROM tblemployees WHERE CoopID = ?";
            $stmtMember = $this->connection->prepare($checkMember);
            $stmtMember->execute([$coopId]);
            
            if ($stmtMember->rowCount() == 0) {
                throw new Exception("Member not found");
            }
            
            // Check if account record exists
            $checkAccount = "SELECT COOPNO FROM tblaccountno WHERE COOPNO = ?";
            $stmtAccount = $this->connection->prepare($checkAccount);
            $stmtAccount->execute([$coopId]);
            
            if ($stmtAccount->rowCount() > 0) {
                // Update existing record
                $sql = "UPDATE tblaccountno SET 
                            Bank = ?,
                            AccountNo = ?,
                            bank_code = ?
                        WHERE COOPNO = ?";
                $stmt = $this->connection->prepare($sql);
                $exec = $stmt->execute([$bank, $accountNo, $bankCode, $coopId]);
            } else {
                // Insert new record
                $sql = "INSERT INTO tblaccountno (COOPNO, Bank, AccountNo, bank_code) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->connection->prepare($sql);
                $exec = $stmt->execute([$coopId, $bank, $accountNo, $bankCode]);
            }
            
            if (!$exec) {
                 throw new Exception("Update failed");
            }
            
            return [
                'success' => true,
                'message' => 'Member account details updated successfully!',
                'data' => [
                    'coop_id' => $coopId,
                    'bank' => $bank,
                    'account_no' => $accountNo,
                    'bank_code' => $bankCode
                ]
            ];
        } catch (Exception $e) {
            error_log("Error updating member account: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update member account: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update member personal details
     */
    public function updateMemberPersonal($data) {
        try {
            // Sanitize input data
            $coopId = $data['coop_id'];
            $firstName = $data['first_name'];
            $middleName = $data['middle_name'];
            $lastName = $data['last_name'];
            $email = $data['email'];
            $phone = $data['phone'];
            $address = $data['address'];
            $department = $data['department'];
            $position = $data['position'];
            
            $sql = "UPDATE tblemployees SET 
                        FirstName = ?,
                        MiddleName = ?,
                        LastName = ?,
                        EmailAddress = ?,
                        MobileNumber = ?,
                        StreetAddress = ?,
                        Department = ?,
                        JobPosition = ?
                    WHERE CoopID = ?";
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt->execute([$firstName, $middleName, $lastName, $email, $phone, $address, $department, $position, $coopId])) {
                 throw new Exception("Update failed");
            }
            
            return [
                'success' => true,
                'message' => 'Member personal details updated successfully!',
                'data' => [
                    'coop_id' => $coopId,
                    'full_name' => "$firstName $middleName $lastName"
                ]
            ];
        } catch (Exception $e) {
            error_log("Error updating member personal details: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update member personal details: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get member account history
     */
    public function getMemberAccountHistory($coopId) {
        try {
            $sql = "SELECT 
                        a.Bank,
                        a.AccountNo,
                        a.bank_code,
                        bc.BankCode,
                        'Current' AS Status,
                        NOW() AS UpdatedAt
                    FROM tblaccountno a
                    LEFT JOIN tblbankcode bc ON a.Bank = bc.bank
                    WHERE a.COOPNO = ?
                    
                    UNION ALL
                    
                    SELECT 
                        'Previous Bank' AS Bank,
                        'Previous Account' AS AccountNo,
                        'Previous Code' AS bank_code,
                        'Previous BankCode' AS BankCode,
                        'Previous' AS Status,
                        DATE_SUB(NOW(), INTERVAL 1 MONTH) AS UpdatedAt
                    WHERE EXISTS (
                        SELECT 1 FROM tblaccountno WHERE COOPNO = ?
                    )
                    
                    ORDER BY UpdatedAt DESC";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$coopId, $coopId]); // Bind same parameter twice
            
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $history;
        } catch (Exception $e) {
            error_log("Error fetching member account history: " . $e->getMessage());
            return [];
        }
    }
}