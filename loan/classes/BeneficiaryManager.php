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
            
            $stmt = $this->connection->query($sql);
            if (!$stmt) {
                throw new Exception("Query failed");
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $sql = "SELECT CONCAT(excel.Narration, ' ', excel.PaymentRefID) AS PaymentReference, 
                           excel.BeneficiaryName, excel.AccountNumber, excel.AccountType, 
                           excel.CBNCode, excel.IsCashCard, excel.Narration, excel.Amount, 
                           excel.EMailAddress, excel.NGN, excel.BeneficiaryCode, excel.batch, excel.Bank 
                    FROM excel 
                    WHERE batch = :batch 
                    ORDER BY PaymentRefID DESC";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(['batch' => $batch]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $sql = "SELECT SUM(excel.Amount) AS Sum FROM excel WHERE Batch = :batch";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(['batch' => $batch]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
            // Sanitize input data
            $beneficiaryCode = $data['txtCoopid'] ?? '';
            $beneficiaryName = $data['CoopName'] ?? '';
            $accountNumber = $data['txtBankAccountNo'] ?? '';
            $cbnCode = $data['txtbankcode'] ?? '';
            $narration = $data['txNarration'] ?? '';
            $amount = isset($data['txtAmount']) ? floatval(str_replace(',', '', $data['txtAmount'])) : 0;
            $batch = $data['Batch'] ?? '';
            $bank = $data['txtBankName'] ?? '';
            
            // Check if beneficiary already exists in this batch
            $checkSql = "SELECT COUNT(*) as count FROM excel WHERE BeneficiaryCode = :code AND Batch = :batch";
            $stmtCheck = $this->connection->prepare($checkSql);
            $stmtCheck->execute(['code' => $beneficiaryCode, 'batch' => $batch]);
            $count = $stmtCheck->fetchColumn();
            
            if ($count > 0) {
                // Update existing record
                $sql = "UPDATE excel SET 
                            BeneficiaryName = :name, 
                            AccountNumber = :acc, 
                            CBNCode = :cbn, 
                            Bank = :bank, 
                            Narration = :narration, 
                            Amount = :amount 
                        WHERE BeneficiaryCode = :code AND Batch = :batch";
                $params = [
                    'name' => $beneficiaryName,
                    'acc' => $accountNumber,
                    'cbn' => $cbnCode,
                    'bank' => $bank,
                    'narration' => $narration,
                    'amount' => $amount,
                    'code' => $beneficiaryCode,
                    'batch' => $batch
                ];
            } else {
                // Insert new record
                $sql = "INSERT INTO excel (BeneficiaryCode, BeneficiaryName, AccountNumber, CBNCode, Narration, Amount, Batch, Bank) 
                        VALUES (:code, :name, :acc, :cbn, :narration, :amount, :batch, :bank)";
                $params = [
                    'code' => $beneficiaryCode,
                    'name' => $beneficiaryName,
                    'acc' => $accountNumber,
                    'cbn' => $cbnCode,
                    'narration' => $narration,
                    'amount' => $amount,
                    'batch' => $batch,
                    'bank' => $bank
                ];
            }
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt->execute($params)) {
                throw new Exception("Database operation failed");
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
            // Sanitize input data
            $beneficiaryCode = $data['txtCoopid'] ?? '';
            $beneficiaryName = $data['CoopName'] ?? '';
            $accountNumber = $data['txtBankAccountNo'] ?? '';
            $cbnCode = $data['txtbankcode'] ?? '';
            $narration = $data['txNarration'] ?? '';
            $amount = isset($data['txtAmount']) ? floatval(str_replace(',', '', $data['txtAmount'])) : 0;
            $batch = $data['Batch'] ?? '';
            $bank = $data['txtBankName'] ?? '';
            
            $sql = "UPDATE excel SET 
                        BeneficiaryName = :name, 
                        AccountNumber = :acc, 
                        CBNCode = :cbn, 
                        Bank = :bank, 
                        Narration = :narration, 
                        Amount = :amount, 
                        Batch = :batch 
                    WHERE BeneficiaryCode = :code";
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt->execute([
                'name' => $beneficiaryName,
                'acc' => $accountNumber,
                'cbn' => $cbnCode,
                'bank' => $bank,
                'narration' => $narration,
                'amount' => $amount,
                'batch' => $batch,
                'code' => $beneficiaryCode
            ])) {
                throw new Exception("Update failed");
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
            $sql = "DELETE FROM excel WHERE BeneficiaryCode = :code AND Batch = :batch";
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt->execute(['code' => $beneficiaryCode, 'batch' => $batch])) {
                throw new Exception("Delete failed");
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
            $amountVal = floatval(str_replace(',', '', $amount));
            
            $sql = "UPDATE excel SET Amount = :amount WHERE BeneficiaryCode = :code AND Batch = :batch";
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt->execute(['amount' => $amountVal, 'code' => $beneficiaryCode, 'batch' => $batch])) {
                throw new Exception("Update failed");
            }
            
            return [
                'success' => true,
                'message' => 'Beneficiary amount updated successfully!',
                'data' => [
                    'beneficiary_code' => $beneficiaryCode,
                    'amount' => $amountVal
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
     * Search employees for autocomplete
     */
    public function searchEmployees($query) {
        try {
            $searchTerm = "%$query%";
            
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
                    WHERE CoopID LIKE :term 
                    OR lastname LIKE :term 
                    OR firstname LIKE :term 
                    OR middlename LIKE :term 
                    LIMIT 10";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(['term' => $searchTerm]);
            
            $suggestions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
            $stmt = $this->connection->query($sql);
            
            if (!$stmt) {
                throw new Exception("Query failed");
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $sql = "SELECT bankcode FROM tblbankcode WHERE bank = :bank LIMIT 1";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(['bank' => $bankName]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
                    WHERE e.CoopID = :id";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(['id' => $coopId]);
            
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
            $coopId = $data['coop_id'] ?? '';
            $bank = $data['bank'] ?? '';
            $accountNo = $data['account_no'] ?? '';
            $bankCode = $data['bank_code'] ?? '';
            
            // Check if member exists
            $checkMember = "SELECT CoopID FROM tblemployees WHERE CoopID = :id";
            $stmtCheck = $this->connection->prepare($checkMember);
            $stmtCheck->execute(['id' => $coopId]);
            
            if ($stmtCheck->rowCount() == 0) {
                throw new Exception("Member not found");
            }
            
            // Check if account record exists
            $checkAccount = "SELECT COOPNO FROM tblaccountno WHERE COOPNO = :id";
            $stmtAccount = $this->connection->prepare($checkAccount);
            $stmtAccount->execute(['id' => $coopId]);
            
            if ($stmtAccount->rowCount() > 0) {
                // Update existing record
                $sql = "UPDATE tblaccountno SET 
                            Bank = :bank,
                            AccountNo = :acc,
                            bank_code = :bc
                        WHERE COOPNO = :id";
                $params = [
                    'bank' => $bank,
                    'acc' => $accountNo,
                    'bc' => $bankCode,
                    'id' => $coopId
                ];
            } else {
                // Insert new record
                $sql = "INSERT INTO tblaccountno (COOPNO, Bank, AccountNo, bank_code) 
                        VALUES (:id, :bank, :acc, :bc)";
                $params = [
                    'id' => $coopId,
                    'bank' => $bank,
                    'acc' => $accountNo,
                    'bc' => $bankCode
                ];
            }
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt->execute($params)) {
                throw new Exception("Database operation failed");
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
?>
