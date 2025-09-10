<?php

class BeneficiaryManager {
    private $connection;
    private $database;
    
    public function __construct($connection, $database) {
        $this->connection = $connection;
        $this->database = $database;
    }
    
    /**
     * Get all employees for autocomplete
     */
    public function getEmployees() {
        try {
            $sql = "SELECT CONCAT(tblemployees.CoopID, ' - ', tblemployees.lastname, ' ', tblemployees.firstname, ' ', tblemployees.middlename) AS coopname, 
                           tblemployees.coopid, tblemployees.bank, tblemployees.AccountNo, tblemployees.BankCode 
                    FROM tblemployees";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $employees[] = $row;
            }
            
            mysqli_free_result($result);
            return $employees;
        } catch (Exception $e) {
            error_log("Error fetching employees: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get beneficiaries for a specific batch
     */
    public function getBeneficiaries($batch) {
        try {
            $batch = mysqli_real_escape_string($this->connection, $batch);
            $sql = "SELECT CONCAT(excel.Narration, ' ', excel.PaymentRefID) AS PaymentReference, 
                           excel.BeneficiaryName, excel.AccountNumber, excel.AccountType, 
                           excel.CBNCode, excel.IsCashCard, excel.Narration, excel.Amount, 
                           excel.EMailAddress, excel.NGN, excel.BeneficiaryCode, excel.batch, excel.Bank 
                    FROM excel 
                    WHERE batch='$batch' 
                    ORDER BY PaymentRefID DESC";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $beneficiaries = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $beneficiaries[] = $row;
            }
            
            mysqli_free_result($result);
            return $beneficiaries;
        } catch (Exception $e) {
            error_log("Error fetching beneficiaries: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get batch total amount
     */
    public function getBatchTotal($batch) {
        try {
            $batch = mysqli_real_escape_string($this->connection, $batch);
            $sql = "SELECT SUM(excel.Amount) AS Sum FROM excel WHERE Batch='$batch'";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            
            return $row['Sum'] ?? 0;
        } catch (Exception $e) {
            error_log("Error fetching batch total: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Add new beneficiary
     */
    public function addBeneficiary($data) {
        try {
            // Sanitize input data with null checks
            $beneficiaryCode = isset($data['txtCoopid']) ? mysqli_real_escape_string($this->connection, $data['txtCoopid']) : '';
            $beneficiaryName = isset($data['CoopName']) ? mysqli_real_escape_string($this->connection, $data['CoopName']) : '';
            $accountNumber = isset($data['txtBankAccountNo']) ? mysqli_real_escape_string($this->connection, $data['txtBankAccountNo']) : '';
            $cbnCode = isset($data['txtbankcode']) ? mysqli_real_escape_string($this->connection, $data['txtbankcode']) : '';
            $narration = isset($data['txNarration']) ? mysqli_real_escape_string($this->connection, $data['txNarration']) : '';
            $amount = isset($data['txtAmount']) ? floatval(str_replace(',', '', $data['txtAmount'])) : 0;
            $batch = isset($data['Batch']) ? mysqli_real_escape_string($this->connection, $data['Batch']) : '';
            $bank = isset($data['txtBankName']) ? mysqli_real_escape_string($this->connection, $data['txtBankName']) : '';
            
            // Check if beneficiary already exists in this batch
            $checkSql = "SELECT COUNT(*) as count FROM excel WHERE BeneficiaryCode='$beneficiaryCode' AND Batch='$batch'";
            $checkResult = mysqli_query($this->connection, $checkSql);
            $checkRow = mysqli_fetch_assoc($checkResult);
            
            if ($checkRow['count'] > 0) {
                // Update existing record
                $sql = "UPDATE excel SET 
                            BeneficiaryName='$beneficiaryName', 
                            AccountNumber='$accountNumber', 
                            CBNCode='$cbnCode', 
                            Bank='$bank', 
                            Narration='$narration', 
                            Amount=$amount 
                        WHERE BeneficiaryCode='$beneficiaryCode' AND Batch='$batch'";
            } else {
                // Insert new record
                $sql = "INSERT INTO excel (BeneficiaryCode, BeneficiaryName, AccountNumber, CBNCode, Narration, Amount, Batch, Bank) 
                        VALUES ('$beneficiaryCode', '$beneficiaryName', '$accountNumber', '$cbnCode', '$narration', $amount, '$batch', '$bank')";
            }
            
            if (!mysqli_query($this->connection, $sql)) {
                throw new Exception("Insert failed: " . mysqli_error($this->connection));
            }
            
            return [
                'success' => true,
                'message' => 'Beneficiary added successfully!',
                'data' => [
                    'beneficiary_code' => $beneficiaryCode,
                    'beneficiary_name' => $beneficiaryName,
                    'amount' => $amount
                ]
            ];
        } catch (Exception $e) {
            error_log("Error adding beneficiary: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to add beneficiary: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update beneficiary
     */
    public function updateBeneficiary($data) {
        try {
            // Sanitize input data with null checks
            $beneficiaryCode = isset($data['txtCoopid']) ? mysqli_real_escape_string($this->connection, $data['txtCoopid']) : '';
            $beneficiaryName = isset($data['CoopName']) ? mysqli_real_escape_string($this->connection, $data['CoopName']) : '';
            $accountNumber = isset($data['txtBankAccountNo']) ? mysqli_real_escape_string($this->connection, $data['txtBankAccountNo']) : '';
            $cbnCode = isset($data['txtbankcode']) ? mysqli_real_escape_string($this->connection, $data['txtbankcode']) : '';
            $narration = isset($data['txNarration']) ? mysqli_real_escape_string($this->connection, $data['txNarration']) : '';
            $amount = isset($data['txtAmount']) ? floatval(str_replace(',', '', $data['txtAmount'])) : 0;
            $batch = isset($data['Batch']) ? mysqli_real_escape_string($this->connection, $data['Batch']) : '';
            $bank = isset($data['txtBankName']) ? mysqli_real_escape_string($this->connection, $data['txtBankName']) : '';
            
            $sql = "UPDATE excel SET 
                        BeneficiaryName='$beneficiaryName', 
                        AccountNumber='$accountNumber', 
                        CBNCode='$cbnCode', 
                        Bank='$bank', 
                        Narration='$narration', 
                        Amount=$amount, 
                        Batch='$batch' 
                    WHERE BeneficiaryCode='$beneficiaryCode'";
            
            if (!mysqli_query($this->connection, $sql)) {
                throw new Exception("Update failed: " . mysqli_error($this->connection));
            }
            
            return [
                'success' => true,
                'message' => 'Beneficiary updated successfully!',
                'data' => [
                    'beneficiary_code' => $beneficiaryCode,
                    'beneficiary_name' => $beneficiaryName,
                    'amount' => $amount
                ]
            ];
        } catch (Exception $e) {
            error_log("Error updating beneficiary: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update beneficiary: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete beneficiary
     */
    public function deleteBeneficiary($beneficiaryCode, $batch) {
        try {
            $beneficiaryCode = mysqli_real_escape_string($this->connection, $beneficiaryCode);
            $batch = mysqli_real_escape_string($this->connection, $batch);
            
            $sql = "DELETE FROM excel WHERE BeneficiaryCode='$beneficiaryCode' AND Batch='$batch'";
            
            if (!mysqli_query($this->connection, $sql)) {
                throw new Exception("Delete failed: " . mysqli_error($this->connection));
            }
            
            return [
                'success' => true,
                'message' => 'Beneficiary deleted successfully!'
            ];
        } catch (Exception $e) {
            error_log("Error deleting beneficiary: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete beneficiary: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update beneficiary amount only
     */
    public function updateBeneficiaryAmount($beneficiaryCode, $amount, $batch) {
        try {
            $beneficiaryCode = mysqli_real_escape_string($this->connection, $beneficiaryCode);
            $amount = floatval(str_replace(',', '', $amount));
            $batch = mysqli_real_escape_string($this->connection, $batch);
            
            $sql = "UPDATE excel SET Amount=$amount WHERE BeneficiaryCode='$beneficiaryCode' AND Batch='$batch'";
            
            if (!mysqli_query($this->connection, $sql)) {
                throw new Exception("Update failed: " . mysqli_error($this->connection));
            }
            
            return [
                'success' => true,
                'message' => 'Beneficiary amount updated successfully!',
                'data' => [
                    'beneficiary_code' => $beneficiaryCode,
                    'amount' => $amount
                ]
            ];
        } catch (Exception $e) {
            error_log("Error updating beneficiary amount: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update beneficiary amount: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Search employees for autocomplete - using exact same logic as search.php
     */
    public function searchEmployees($query) {
        try {
            $searchTerm = mysqli_real_escape_string($this->connection, $query);
            
            // Exact same query as search.php
            $sql = "SELECT
                        tblemployees.CoopID, 
                        CONCAT(tblemployees.FirstName,' , ',tblemployees.MiddleName,' ',tblemployees.LastName) AS name, 
                        IFNULL(tblaccountno.Bank,'') AS Bank, 
                        IFNULL(tblaccountno.AccountNo,'') AS AccountNo, 
                        IFNULL(tblbankcode.BankCode,'') AS BankCode
                    FROM
                        tblemployees
                        LEFT JOIN tblaccountno ON tblaccountno.COOPNO = tblemployees.CoopID
                        LEFT JOIN tblbankcode ON tblaccountno.Bank = tblbankcode.bank
                    WHERE CoopID LIKE '%$searchTerm%' 
                    OR lastname LIKE '%$searchTerm%' 
                    OR firstname LIKE '%$searchTerm%' 
                    OR middlename LIKE '%$searchTerm%' 
                    LIMIT 10";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            // Exact same data structure as search.php
            $suggestions = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data = [];
                $data['id'] = $row['CoopID'];
                $data['coopname'] = $row['name'];
                $data['label'] = $row['name'] . ' - ' . $row['CoopID'];
                $data['value'] = $row['CoopID'];
                $data['bank'] = $row['Bank'];
                $data['AccountNo'] = $row['AccountNo'];
                $data['BankCode'] = $row['BankCode'];
                $suggestions[] = $data;
            }
            
            mysqli_free_result($result);
            return $suggestions;
        } catch (Exception $e) {
            error_log("Error searching employees: " . $e->getMessage());
            return [];
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
     * Get bank code by bank name
     */
    public function getBankCode($bankName) {
        try {
            $bankName = mysqli_real_escape_string($this->connection, $bankName);
            $sql = "SELECT bankcode FROM tblbankcode WHERE bank = '$bankName' LIMIT 1";
            $result = mysqli_query($this->connection, $sql);
            
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            
            return $row ? $row['bankcode'] : '';
        } catch (Exception $e) {
            error_log("Error fetching bank code: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Get member bank details only
     */
    public function getMemberBankDetails($coopId) {
        try {
            $coopId = mysqli_real_escape_string($this->connection, $coopId);
            $sql = "SELECT 
                        e.CoopID,
                        CONCAT(e.FirstName, ' ', e.MiddleName, ' ', e.LastName) AS FullName,
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
            error_log("Error fetching member bank details: " . $e->getMessage());
            return null;
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
}
