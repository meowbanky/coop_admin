<?php

class LoanManager {
    private $connection;
    private $database;
    
    public function __construct($connection, $database) {
        $this->connection = $connection;
        $this->database = $database;
    }
    
    /**
     * Get all payroll periods
     */
    public function getPayrollPeriods() {
        try {
            $sql = "SELECT id, PayrollPeriod, PhysicalYear, PhysicalMonth, Remarks 
                    FROM tbpayrollperiods 
                    ORDER BY id DESC";
            $result = mysqli_query($this->connection, $sql);
            
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $periods = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $periods[] = $row;
            }
            
            mysqli_free_result($result);
            return $periods;
        } catch (Exception $e) {
            error_log("Error fetching payroll periods: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Insert loan into tbl_loans
     */
    public function insertLoan($data) {
        try {
            // Sanitize input data
            $coopId = mysqli_real_escape_string($this->connection, $data['coop_id']);
            $dateOfLoanApp = mysqli_real_escape_string($this->connection, $data['date_of_loan_app']);
            $loanAmount = floatval($data['loan_amount']);
            $monthlyRepayment = intval($data['monthly_repayment']?? 0);
            $loanStatus = intval($data['loan_status'] ?? 1);
            $stationeryStatus = intval($data['stationery_status'] ?? 0);
            $loanPeriod = intval($data['loan_period']);
            $payrollPeriodId = intval($data['payroll_period_id']);
            $batchNumber = isset($data['batch_number']) ? mysqli_real_escape_string($this->connection, $data['batch_number']) : '';
            
            // Validate required fields
            if (empty($coopId) || empty($dateOfLoanApp) || $loanAmount <= 0) {
                throw new Exception("Required fields are missing or invalid");
            }
            
            // Check if member exists
            $checkMember = "SELECT CoopID FROM tblemployees WHERE CoopID = '$coopId'";
            $memberResult = mysqli_query($this->connection, $checkMember);
            
            if (mysqli_num_rows($memberResult) == 0) {
                throw new Exception("Member not found");
            }
            
            // Check if payroll period exists
            $checkPeriod = "SELECT id FROM tbpayrollperiods WHERE id = '$payrollPeriodId'";
            $periodResult = mysqli_query($this->connection, $checkPeriod);
            
            if (mysqli_num_rows($periodResult) == 0) {
                throw new Exception("Payroll period not found");
            }
            
            // Insert loan
            $sql = "INSERT INTO tbl_loans 
                    (CoopID, DateOfLoanApp, LoanAmount, MonthlyRepayment, LoanStatus, StationeryStatus, LoanPeriod) 
                    VALUES ('$coopId', '$dateOfLoanApp', $loanAmount, $monthlyRepayment, $loanStatus, $stationeryStatus, $loanPeriod)";
            
            if (!mysqli_query($this->connection, $sql)) {
                throw new Exception("Insert failed: " . mysqli_error($this->connection));
            }
            
            $loanId = mysqli_insert_id($this->connection);
            
            return [
                'success' => true,
                'message' => 'Loan inserted successfully!' . ($batchNumber ? " (Batch: $batchNumber)" : ''),
                'data' => [
                    'loan_id' => $loanId,
                    'coop_id' => $coopId,
                    'loan_amount' => $loanAmount,
                    'payroll_period_id' => $payrollPeriodId,
                    'batch_number' => $batchNumber
                ]
            ];
        } catch (Exception $e) {
            error_log("Error inserting loan: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to insert loan: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get loans by payroll period
     */
    public function getLoansByPeriod($payrollPeriodId) {
        try {
            $payrollPeriodId = mysqli_real_escape_string($this->connection, $payrollPeriodId);
            $sql = "SELECT 
                        l.loan_id,
                        l.CoopID,
                        l.DateOfLoanApp,
                        l.LoanAmount,
                        l.MonthlyRepayment,
                        l.LoanStatus,
                        l.StationeryStatus,
                        l.LoanPeriod,
                        CONCAT(e.FirstName, ' ', e.MiddleName, ' ', e.LastName) AS FullName
                    FROM tbl_loans l
                    LEFT JOIN tblemployees e ON l.CoopID = e.CoopID
                    WHERE l.LoanStatus = 1
                    ORDER BY l.DateOfLoanApp DESC";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $loans = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $loans[] = $row;
            }
            
            mysqli_free_result($result);
            return $loans;
        } catch (Exception $e) {
            error_log("Error fetching loans: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get loan statistics
     */
    public function getLoanStatistics() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_loans,
                        SUM(LoanAmount) as total_amount,
                        AVG(LoanAmount) as average_amount,
                        COUNT(CASE WHEN LoanStatus = 1 THEN 1 END) as active_loans
                    FROM tbl_loans";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $stats = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error fetching loan statistics: " . $e->getMessage());
            return [
                'total_loans' => 0,
                'total_amount' => 0,
                'average_amount' => 0,
                'active_loans' => 0
            ];
        }
    }
    
    /**
     * Get beneficiaries from excel table for a specific batch
     */
    public function getBatchBeneficiaries($batchNumber) {
        try {
            $batchNumber = mysqli_real_escape_string($this->connection, $batchNumber);
            $sql = "SELECT 
                        BeneficiaryCode,
                        BeneficiaryName,
                        Amount,
                        Bank,
                        AccountNumber,
                        Narration
                    FROM excel 
                    WHERE Batch = '$batchNumber'";
            
            error_log("Executing query: " . $sql);
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                error_log("Query failed: " . mysqli_error($this->connection));
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $beneficiaries = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $beneficiaries[] = $row;
            }
            
            error_log("Found " . count($beneficiaries) . " beneficiaries for batch: $batchNumber");
            mysqli_free_result($result);
            return $beneficiaries;
        } catch (Exception $e) {
            error_log("Error fetching batch beneficiaries: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Post account - Insert beneficiaries as loans
     */
    public function postAccount($batchNumber, $loanPeriod, $payrollPeriodId, $selectedBeneficiaries = null) {
        try {
            // Debug logging
            error_log("PostAccount called with: batchNumber=$batchNumber, loanPeriod=$loanPeriod, payrollPeriodId=$payrollPeriodId");
            error_log("Selected beneficiaries: " . json_encode($selectedBeneficiaries));
            
            // Use selected beneficiaries if provided, otherwise get all from batch
            if ($selectedBeneficiaries && is_array($selectedBeneficiaries)) {
                $beneficiaries = $selectedBeneficiaries;
                error_log("Using selected beneficiaries: " . count($beneficiaries));
            } else {
                $beneficiaries = $this->getBatchBeneficiaries($batchNumber);
                error_log("Using batch beneficiaries: " . count($beneficiaries));
            }
            
            if (empty($beneficiaries)) {
                error_log("No beneficiaries found for batch: $batchNumber");
                throw new Exception("No beneficiaries found for batch: $batchNumber");
            }
            
            $insertedCount = 0;
            $errors = [];
            $today = date('Y-m-d');
            
            // Start transaction
            mysqli_begin_transaction($this->connection);
            
            try {
                foreach ($beneficiaries as $index => $beneficiary) {
                    error_log("Processing beneficiary $index: " . json_encode($beneficiary));
                    
                    $coopId = mysqli_real_escape_string($this->connection, $beneficiary['BeneficiaryCode']);
                    $loanAmount = floatval($beneficiary['Amount']);
                    $monthlyRepayment = $loanAmount / $loanPeriod; // Calculate monthly repayment
                    
                    error_log("CoopID: $coopId, Amount: $loanAmount, MonthlyRepayment: $monthlyRepayment");
                    
                    // Check if member exists
                    $checkMember = "SELECT CoopID FROM tblemployees WHERE CoopID = '$coopId'";
                    $memberResult = mysqli_query($this->connection, $checkMember);
                    
                    if (mysqli_num_rows($memberResult) == 0) {
                        $error = "Member not found: " . $coopId;
                        error_log($error);
                        $errors[] = $error;
                        continue;
                    }
                    
                    // Check if payroll period exists
                    $checkPeriod = "SELECT id FROM tbpayrollperiods WHERE id = '$payrollPeriodId'";
                    $periodResult = mysqli_query($this->connection, $checkPeriod);
                    
                    if (mysqli_num_rows($periodResult) == 0) {
                        $error = "Payroll period not found: " . $payrollPeriodId;
                        error_log($error);
                        $errors[] = $error;
                        continue;
                    }
                    
                    // Check if loan already exists for this member and loan period (prevent duplicate loans)
                    $checkLoan = "SELECT loan_id FROM tbl_loans WHERE CoopID = '$coopId' AND LoanPeriod = '$payrollPeriodId' AND LoanStatus = 1";
                    $loanResult = mysqli_query($this->connection, $checkLoan);
                    
                    if (mysqli_num_rows($loanResult) > 0) {
                        $error = "Active loan already exists for member: $coopId with loan period: $payrollPeriodId months";
                        error_log($error);
                        $errors[] = $error;
                        continue;
                    }
                    
                    // Insert loan
                    $sql = "INSERT INTO tbl_loans 
                            (CoopID, DateOfLoanApp, LoanAmount, MonthlyRepayment, LoanStatus, StationeryStatus, LoanPeriod) 
                            VALUES ('$coopId', '$today', $loanAmount, $monthlyRepayment, 1, 0, $payrollPeriodId)";
                    
                    error_log("Executing insert: " . $sql);
                    
                    if (!mysqli_query($this->connection, $sql)) {
                        $error = "Failed to insert loan for member: " . $coopId . " - " . mysqli_error($this->connection);
                        error_log($error);
                        $errors[] = $error;
                        continue;
                    }
                    
                    $insertedCount++;
                    error_log("Successfully inserted loan for member: $coopId");
                }
                
                // Commit transaction
                mysqli_commit($this->connection);
                
                // Check if any loans were actually inserted
                if ($insertedCount == 0) {
                    $result = [
                        'success' => false,
                        'message' => "No loans were inserted. All beneficiaries were skipped due to validation errors.",
                        'data' => [
                            'batch_number' => $batchNumber,
                            'inserted_count' => $insertedCount,
                            'total_beneficiaries' => count($beneficiaries),
                            'errors' => $errors
                        ]
                    ];
                } else {
                    $result = [
                        'success' => true,
                        'message' => "Successfully posted $insertedCount loans from batch: $batchNumber",
                        'data' => [
                            'batch_number' => $batchNumber,
                            'inserted_count' => $insertedCount,
                            'total_beneficiaries' => count($beneficiaries),
                            'errors' => $errors
                        ]
                    ];
                }
                
                error_log("PostAccount result: " . json_encode($result));
                return $result;
                
            } catch (Exception $e) {
                // Rollback transaction
                mysqli_rollback($this->connection);
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Error posting account: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to post account: ' . $e->getMessage()
            ];
        }
    }
}