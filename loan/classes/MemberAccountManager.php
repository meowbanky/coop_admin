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
            $coopId = mysqli_real_escape_string($this->connection, $coopId);
            $sql = "SELECT 
                        e.CoopID,
                        CONCAT(e.FirstName, ' ', e.MiddleName, ' ', e.LastName) AS FullName,
                        e.FirstName,
                        e.MiddleName,
                        e.LastName,
                        a.Bank,
                        a.AccountNo,
                        a.bank_code,
                        bc.BankCode
                    FROM tblemployees e
                    LEFT JOIN tblaccountno a ON e.CoopID = a.COOPNO
                    LEFT JOIN tblbankcode bc ON a.Bank = bc.bank
                    WHERE e.CoopID = '$coopId'";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $member = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            
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
            $sql = "SELECT bank, bankcode FROM tblbankcode ORDER BY bank";
            $result = mysqli_query($this->connection, $sql);
            
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $banks = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $banks[] = $row;
            }
            
            mysqli_free_result($result);
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
            $query = mysqli_real_escape_string($this->connection, $query);
            $sql = "SELECT 
                        CoopID,
                        CONCAT(CoopID, ' - ', FirstName, ' ', MiddleName, ' ', LastName) AS FullName,
                        FirstName,
                        MiddleName,
                        LastName
                    FROM tblemployees 
                    WHERE CoopID LIKE '%$query%' 
                    OR FirstName LIKE '%$query%' 
                    OR LastName LIKE '%$query%' 
                    OR MiddleName LIKE '%$query%'
                    ORDER BY FirstName, LastName
                    LIMIT 10";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $members = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $members[] = [
                    'value' => $row['CoopID'],
                    'label' => $row['FullName'],
                    'coopid' => $row['CoopID'],
                    'fullname' => $row['FullName']
                ];
            }
            
            mysqli_free_result($result);
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
            $coopId = mysqli_real_escape_string($this->connection, $data['coop_id']);
            $bank = mysqli_real_escape_string($this->connection, $data['bank']);
            $accountNo = mysqli_real_escape_string($this->connection, $data['account_no']);
            $bankCode = mysqli_real_escape_string($this->connection, $data['bank_code']);
            
            // Check if member exists
            $checkMember = "SELECT CoopID FROM tblemployees WHERE CoopID = '$coopId'";
            $memberResult = mysqli_query($this->connection, $checkMember);
            
            if (mysqli_num_rows($memberResult) == 0) {
                throw new Exception("Member not found");
            }
            
            // Check if account record exists
            $checkAccount = "SELECT COOPNO FROM tblaccountno WHERE COOPNO = '$coopId'";
            $accountResult = mysqli_query($this->connection, $checkAccount);
            
            if (mysqli_num_rows($accountResult) > 0) {
                // Update existing record
                $sql = "UPDATE tblaccountno SET 
                            Bank = '$bank',
                            AccountNo = '$accountNo',
                            bank_code = '$bankCode'
                        WHERE COOPNO = '$coopId'";
            } else {
                // Insert new record
                $sql = "INSERT INTO tblaccountno (COOPNO, Bank, AccountNo, bank_code) 
                        VALUES ('$coopId', '$bank', '$accountNo', '$bankCode')";
            }
            
            if (!mysqli_query($this->connection, $sql)) {
                throw new Exception("Update failed: " . mysqli_error($this->connection));
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
            $coopId = mysqli_real_escape_string($this->connection, $data['coop_id']);
            $firstName = mysqli_real_escape_string($this->connection, $data['first_name']);
            $middleName = mysqli_real_escape_string($this->connection, $data['middle_name']);
            $lastName = mysqli_real_escape_string($this->connection, $data['last_name']);
            $email = mysqli_real_escape_string($this->connection, $data['email']);
            $phone = mysqli_real_escape_string($this->connection, $data['phone']);
            $address = mysqli_real_escape_string($this->connection, $data['address']);
            $department = mysqli_real_escape_string($this->connection, $data['department']);
            $position = mysqli_real_escape_string($this->connection, $data['position']);
            
            $sql = "UPDATE tblemployees SET 
                        FirstName = '$firstName',
                        MiddleName = '$middleName',
                        LastName = '$lastName',
                        EmailAddress = '$email',
                        PhoneNumber = '$phone',
                        Address = '$address',
                        Department = '$department',
                        Position = '$position'
                    WHERE CoopID = '$coopId'";
            
            if (!mysqli_query($this->connection, $sql)) {
                throw new Exception("Update failed: " . mysqli_error($this->connection));
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
            $coopId = mysqli_real_escape_string($this->connection, $coopId);
            $sql = "SELECT 
                        a.Bank,
                        a.AccountNo,
                        a.bank_code,
                        bc.BankCode,
                        'Current' AS Status,
                        NOW() AS UpdatedAt
                    FROM tblaccountno a
                    LEFT JOIN tblbankcode bc ON a.Bank = bc.bank
                    WHERE a.COOPNO = '$coopId'
                    
                    UNION ALL
                    
                    SELECT 
                        'Previous Bank' AS Bank,
                        'Previous Account' AS AccountNo,
                        'Previous Code' AS bank_code,
                        'Previous BankCode' AS BankCode,
                        'Previous' AS Status,
                        DATE_SUB(NOW(), INTERVAL 1 MONTH) AS UpdatedAt
                    WHERE EXISTS (
                        SELECT 1 FROM tblaccountno WHERE COOPNO = '$coopId'
                    )
                    
                    ORDER BY UpdatedAt DESC";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $history = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $history[] = $row;
            }
            
            mysqli_free_result($result);
            return $history;
        } catch (Exception $e) {
            error_log("Error fetching member account history: " . $e->getMessage());
            return [];
        }
    }
}