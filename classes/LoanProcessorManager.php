<?php
/**
 * Loan Processor Manager
 * Handles business logic for loan processing operations
 */

class LoanProcessorManager {
    private $connection;
    private $database;
    
    public function __construct($connection, $database) {
        $this->connection = $connection;
        $this->database = $database;
        
        // Check connection
        if (!$this->connection) {
            throw new Exception('Database connection is null');
        }
        
        // Ensure database is selected
        if (!mysqli_select_db($this->connection, $this->database)) {
            throw new Exception('Database selection failed: ' . mysqli_error($this->connection));
        }
        
        // Log successful initialization
        error_log("LoanProcessorManager initialized successfully with database: " . $this->database);
    }
    
    /**
     * Get all employees for autocomplete
     */
    public function getAllEmployees() {
        $sql = "SELECT 
                    CoopID, 
                    CONCAT(FirstName, ' ', MiddleName, ' ', LastName) AS FullName,
                    FirstName,
                    MiddleName,
                    LastName
                FROM tblemployees 
                WHERE Status = 'Active' 
                ORDER BY FirstName, LastName";
        
        $result = mysqli_query($this->connection, $sql);
        $employees = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $employees[] = [
                    'coop_id' => $row['CoopID'],
                    'full_name' => trim($row['FullName']),
                    'first_name' => $row['FirstName'],
                    'middle_name' => $row['MiddleName'],
                    'last_name' => $row['LastName']
                ];
            }
        }
        
        return $employees;
    }
    
    /**
     * Search employees by name or ID
     */
    public function searchEmployees($searchTerm) {
        if (empty($searchTerm)) {
            return [];
        }
        
        $searchTerm = mysqli_real_escape_string($this->connection, $searchTerm);
        
        $sql = "SELECT 
                    CoopID, 
                    CONCAT(FirstName, ' ', MiddleName, ' ', LastName) AS FullName,
                    FirstName,
                    MiddleName,
                    LastName
                FROM tblemployees 
                WHERE (CoopID LIKE '%$searchTerm%' 
                    OR FirstName LIKE '%$searchTerm%' 
                    OR LastName LIKE '%$searchTerm%' 
                    OR MiddleName LIKE '%$searchTerm%')
                    AND Status = 'Active' 
                ORDER BY FirstName, LastName
                LIMIT 10";
        
        $result = mysqli_query($this->connection, $sql);
        $employees = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $employees[] = [
                    'coop_id' => $row['CoopID'],
                    'full_name' => trim($row['FullName']),
                    'first_name' => $row['FirstName'],
                    'middle_name' => $row['MiddleName'],
                    'last_name' => $row['LastName']
                ];
            }
        }
        
        return $employees;
    }
    
    /**
     * Get employee details including bank information
     */
    public function getEmployeeDetails($coopId) {
        $coopId = mysqli_real_escape_string($this->connection, $coopId);
        
        // Get employee basic info
        $sql = "SELECT 
                    e.CoopID,
                    CONCAT(e.FirstName, ' ', e.MiddleName, ' ', e.LastName) AS FullName,
                    e.FirstName,
                    e.MiddleName,
                    e.LastName,
                    e.Department,
                    e.JobPosition as Position,
                    e.HireDate as DateOfEmployment,
                    e.Status,
                    a.Bank,
                    a.AccountNo,
                    bc.BankCode
                FROM tblemployees e
                LEFT JOIN tblaccountno a ON a.COOPNO = e.CoopID
                LEFT JOIN tblbankcode bc ON bc.bank = a.Bank
                WHERE e.CoopID = '$coopId' AND e.Status = 'Active'";
        
        $result = mysqli_query($this->connection, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        
        return null;
    }
    
    /**
     * Get payroll periods
     */
    public function getPayrollPeriods() {
        $sql = "SELECT * FROM tbpayrollperiods ORDER BY id DESC";
        $result = mysqli_query($this->connection, $sql);
        $periods = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $periods[] = [
                    'id' => $row['id'],
                    'payroll_period' => $row['PayrollPeriod'],
                    'start_date' => $row['StartDate'] ?? null,
                    'end_date' => $row['EndDate'] ?? null
                ];
            }
        }
        
        return $periods;
    }
    
    /**
     * Calculate loan details for an employee
     */
    public function calculateLoanDetails($coopId, $periodId) {
        $coopId = mysqli_real_escape_string($this->connection, $coopId);
        $periodId = mysqli_real_escape_string($this->connection, $periodId);
        
        // Get employee's shares and savings from master transaction table
        $sql = "SELECT 
                    IFNULL(SUM(sharesAmount), 0) as total_shares,
                    IFNULL(SUM(savingsAmount), 0) as total_savings,
                    IFNULL(SUM(sharesAmount + savingsAmount), 0) as total_shares_savings
                FROM tbl_mastertransact 
                WHERE COOPID = '$coopId'";
        
        $result = mysqli_query($this->connection, $sql);
        $sharesData = mysqli_fetch_assoc($result);
        $totalShares = $sharesData['total_shares'] ?? 0;
        $totalSavings = $sharesData['total_savings'] ?? 0;
        $totalSharesSavings = $sharesData['total_shares_savings'] ?? 0;
        
        // Calculate maximum loan obtainable (typically 3x shares)
        $maxLoanObtainable = $totalShares * 3;
        
        // Get current loan balance (total loans - total repayments)
        $loanBalanceSql = "SELECT 
                            (SELECT IFNULL(SUM(LoanAmount), 0) 
                             FROM tbl_loans 
                             WHERE CoopID = '$coopId') as total_loans,
                            (SELECT IFNULL(SUM(loanRepayment), 0) 
                             FROM tbl_mastertransact 
                             WHERE COOPID = '$coopId') as total_repayments,
                            (SELECT COUNT(*) 
                             FROM tbl_loans 
                             WHERE CoopID = '$coopId') as loan_count,
                            (SELECT COUNT(*) 
                             FROM tbl_mastertransact 
                             WHERE COOPID = '$coopId' AND loanRepayment > 0) as repayment_count";
        
        $loanBalanceResult = mysqli_query($this->connection, $loanBalanceSql);
        $loanBalanceData = mysqli_fetch_assoc($loanBalanceResult);
        
        $totalLoans = $loanBalanceData['total_loans'] ?? 0;
        $totalRepayments = $loanBalanceData['total_repayments'] ?? 0;
        $loanCount = $loanBalanceData['loan_count'] ?? 0;
        $repaymentCount = $loanBalanceData['repayment_count'] ?? 0;
        $currentLoanBalance = $totalLoans - $totalRepayments;
        
        // Alternative calculation: If repayments exceed loans, set balance to 0
        // This handles cases where old loans were deleted but repayments remain
        if ($currentLoanBalance < 0) {
            error_log("WARNING: Negative loan balance detected for $coopId. Setting to 0.");
            $currentLoanBalance = 0;
        }
        
        // Available loan amount (max loan - current balance)
        $availableLoan = $maxLoanObtainable - $currentLoanBalance;
        
        // Debug logging
        error_log("Loan Balance Debug for $coopId:");
        error_log("Total Loans: $totalLoans (from $loanCount loans)");
        error_log("Total Repayments: $totalRepayments (from $repaymentCount transactions)");
        error_log("Current Balance: $currentLoanBalance");
        error_log("Available Loan: $availableLoan");
        
        return [
            'coop_id' => $coopId,
            'total_shares' => $totalShares,
            'total_savings' => $totalSavings,
            'total_shares_savings' => $totalSharesSavings,
            'max_loan_obtainable' => $maxLoanObtainable,
            'total_loans' => $totalLoans,
            'total_repayments' => $totalRepayments,
            'current_loan_balance' => $currentLoanBalance,
            'available_loan' => max(0, $availableLoan),
            'period_id' => $periodId,
            'debug_info' => [
                'loan_count' => $loanCount,
                'repayment_count' => $repaymentCount,
                'calculation' => "$totalLoans - $totalRepayments = $currentLoanBalance"
            ]
        ];
    }
    
    /**
     * Update loan information
     */
    public function updateLoan($data) {
        $coopId = mysqli_real_escape_string($this->connection, $data['coop_id']);
        $loanAmount = floatval($data['loan_amount']);
        $periodId = mysqli_real_escape_string($this->connection, $data['period_id']);
        $batch = $data['batch'] ?? $_SESSION['Batch'] ?? '';
        $insertedBy = $_SESSION['complete_name'] ?? 'System';
        
        // Validate loan amount (can be disabled by setting $validateLoanLimit = false)
        $validateLoanLimit = false; // Set to true to enable loan limit validation
        
        if ($validateLoanLimit) {
            $calculation = $this->calculateLoanDetails($coopId, $periodId);
            if ($loanAmount > $calculation['available_loan']) {
                return [
                    'success' => false,
                    'message' => 'Loan amount exceeds available loan limit. Available: ₦' . number_format($calculation['available_loan'], 2)
                ];
            }
        }
        
        // Calculate monthly repayment (assuming 12 months)
        $monthlyRepayment = $loanAmount / 12;
        
        // Start transaction
        mysqli_begin_transaction($this->connection);
        
        try {
            // Update existing loan in tbl_loanapproval
            $updateSql = "UPDATE tbl_loanapproval 
                         SET LoanAmount = $loanAmount, 
                             MonthlyRepayment = $monthlyRepayment,
                             batch = '$batch',
                             insertedBy = '$insertedBy'
                         WHERE coopID = '$coopId' AND period = $periodId";
            
            if (!mysqli_query($this->connection, $updateSql)) {
                throw new Exception('Failed to update tbl_loanapproval: ' . mysqli_error($this->connection));
            }
            
            $affectedRows = mysqli_affected_rows($this->connection);
            
            if ($affectedRows === 0) {
                // If no existing loan found, create a new one
                $today = date('Y-m-d');
                $insertSql = "INSERT INTO tbl_loanapproval 
                             (coopID, period, approvalDate, LoanAmount, StationeryPayment, MonthlyRepayment, insertedBy, batch) 
                             VALUES ('$coopId', $periodId, '$today', $loanAmount, 0.00, $monthlyRepayment, '$insertedBy', '$batch')";
                
                if (!mysqli_query($this->connection, $insertSql)) {
                    throw new Exception('Failed to insert into tbl_loanapproval: ' . mysqli_error($this->connection));
                }
                
                $approvalId = mysqli_insert_id($this->connection);
                $message = 'New loan created successfully';
            } else {
                $message = 'Loan updated successfully';
                $approvalId = null; // We don't have the ID for updates
            }
            
            // Commit transaction
            mysqli_commit($this->connection);
            
            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'approval_id' => $approvalId,
                    'loan_amount' => $loanAmount,
                    'monthly_repayment' => $monthlyRepayment,
                    'affected_rows' => $affectedRows
                ]
            ];
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($this->connection);
            error_log("Update loan error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update loan: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get loan list for an employee
     */
    public function getLoanList($coopId, $periodId = null) {
        $coopId = mysqli_real_escape_string($this->connection, $coopId);
        
        $sql = "SELECT 
                    l.loan_id,
                    l.CoopID,
                    l.DateOfLoanApp,
                    l.LoanAmount,
                    l.MonthlyRepayment,
                    l.LoanStatus,
                    l.LoanPeriod,
                    p.PayrollPeriod,
                    la.approvalDate,
                    la.insertedBy,
                    la.batch,
                    'All Loans' as StatusText
                FROM tbl_loans l
                LEFT JOIN tbpayrollperiods p ON p.id = l.LoanPeriod
                LEFT JOIN tbl_loanapproval la ON la.coopID = l.CoopID AND la.period = l.LoanPeriod
                WHERE l.CoopID = '$coopId'";
        
        if ($periodId) {
            $periodId = mysqli_real_escape_string($this->connection, $periodId);
            $sql .= " AND l.LoanPeriod = '$periodId'";
        }
        
        $sql .= " ORDER BY l.DateOfLoanApp DESC";
        
        $result = mysqli_query($this->connection, $sql);
        $loans = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $loans[] = [
                    'loan_id' => $row['loan_id'],
                    'coop_id' => $row['CoopID'],
                    'date_of_loan_app' => $row['DateOfLoanApp'],
                    'loan_amount' => number_format($row['LoanAmount'], 2),
                    'monthly_repayment' => number_format($row['MonthlyRepayment'], 2),
                    'loan_status' => $row['LoanStatus'],
                    'status_text' => $row['StatusText'],
                    'payroll_period' => $row['PayrollPeriod'] ?? 'N/A',
                    'loan_period' => $row['LoanPeriod'],
                    'approval_date' => $row['approvalDate'] ?? 'N/A',
                    'inserted_by' => $row['insertedBy'] ?? 'N/A',
                    'batch' => $row['batch'] ?? 'N/A'
                ];
            }
        }
        
        return $loans;
    }
    
    /**
     * Get loan approval data for an employee
     */
    public function getLoanApprovalData($periodId) {
        $periodId = mysqli_real_escape_string($this->connection, $periodId);
        
        $sql = "SELECT 
                    la.id,
                    la.coopID,
                    la.period,
                    la.approvalDate,
                    la.LoanAmount,
                    la.StationeryPayment,
                    la.MonthlyRepayment,
                    la.insertedBy,
                    la.batch,
                    p.PayrollPeriod
                FROM tbl_loanapproval la
                LEFT JOIN tbpayrollperiods p ON p.id = la.period
                WHERE la.period = '$periodId'
                ORDER BY la.approvalDate DESC";
        
        $result = mysqli_query($this->connection, $sql);
        $approvals = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $approvals[] = [
                    'approval_id' => $row['id'],
                    'coop_id' => $row['coopID'],
                    'period' => $row['period'],
                    'approval_date' => $row['approvalDate'],
                    'loan_amount' => number_format($row['LoanAmount'], 2),
                    'stationery_payment' => number_format($row['StationeryPayment'], 2),
                    'monthly_repayment' => number_format($row['MonthlyRepayment'], 2),
                    'inserted_by' => $row['insertedBy'],
                    'batch' => $row['batch'],
                    'payroll_period' => $row['PayrollPeriod'] ?? 'N/A'
                ];
            }
        }
        
        return $approvals;
    }
    
    /**
     * Get current loan balance for an employee
     */
    public function getCurrentLoanBalance($coopId) {
        $coopId = mysqli_real_escape_string($this->connection, $coopId);
        
        $sql = "SELECT 
                    (SELECT IFNULL(SUM(LoanAmount), 0) 
                     FROM tbl_loans 
                     WHERE CoopID = '$coopId') as total_loans,
                    (SELECT IFNULL(SUM(loanRepayment), 0) 
                     FROM tbl_mastertransact 
                     WHERE COOPID = '$coopId') as total_repayments";
        
        $result = mysqli_query($this->connection, $sql);
        
        if ($result) {
            $data = mysqli_fetch_assoc($result);
            $totalLoans = $data['total_loans'] ?? 0;
            $totalRepayments = $data['total_repayments'] ?? 0;
            $currentBalance = $totalLoans - $totalRepayments;
            
            // If repayments exceed loans, set balance to 0
            if ($currentBalance < 0) {
                error_log("WARNING: Negative loan balance detected for $coopId in getCurrentLoanBalance. Setting to 0.");
                $currentBalance = 0;
            }
            
            return [
                'total_loans' => $totalLoans,
                'total_repayments' => $totalRepayments,
                'current_balance' => $currentBalance
            ];
        }
        
        return [
            'total_loans' => 0,
            'total_repayments' => 0,
            'current_balance' => 0
        ];
    }
    
    /**
     * Get all loans for a specific period from loan approval table
     */
    public function getCurrentPeriodLoans($periodId) {
        $periodId = mysqli_real_escape_string($this->connection, $periodId);
        
        $sql = "SELECT 
                    la.id as loan_approval_id,
                    la.coopID,
                    la.LoanAmount,
                    la.MonthlyRepayment,
                    la.approvalDate,
                    la.batch,
                    CONCAT(COALESCE(e.FirstName, ''), ' ', COALESCE(e.MiddleName, ''), ' ', COALESCE(e.LastName, '')) as CompleteName
                FROM tbl_loanapproval la
                LEFT JOIN tblemployees e ON la.coopID = e.CoopID
                WHERE la.period = '$periodId'
                ORDER BY la.approvalDate DESC, la.coopID ASC";
        
        error_log("Current Period Loans SQL: " . $sql);
        $result = mysqli_query($this->connection, $sql);
        
        if (!$result) {
            error_log("Current Period Loans Query Error: " . mysqli_error($this->connection));
        }
        
        if ($result) {
            $loans = [];
            $totalLoanAmount = 0;
            $totalMonthlyRepayment = 0;
            
            while ($row = mysqli_fetch_assoc($result)) {
                $loanAmount = floatval($row['LoanAmount']);
                $monthlyRepayment = floatval($row['MonthlyRepayment']);
                
                $loans[] = [
                    'loan_approval_id' => $row['loan_approval_id'],
                    'coop_id' => $row['coopID'],
                    'name' => $row['CompleteName'] ?? 'Unknown',
                    'loan_amount' => $loanAmount,
                    'monthly_repayment' => $monthlyRepayment,
                    'approval_date' => $row['approvalDate'],
                    'batch' => $row['batch']
                ];
                
                $totalLoanAmount += $loanAmount;
                $totalMonthlyRepayment += $monthlyRepayment;
            }
            
            $result_data = [
                'loans' => $loans,
                'total_loan_amount' => $totalLoanAmount,
                'total_monthly_repayment' => $totalMonthlyRepayment,
                'count' => count($loans)
            ];
            
            error_log("Current Period Loans Result: " . json_encode($result_data));
            return $result_data;
        }
        
        return [
            'loans' => [],
            'total_loan_amount' => 0,
            'total_monthly_repayment' => 0,
            'count' => 0
        ];
    }
    
    /**
     * Get loan data for display
     */
    public function getLoanData() {
        return [
            'employees' => $this->getAllEmployees(),
            'payroll_periods' => $this->getPayrollPeriods()
        ];
    }
    
    /**
     * Delete a loan from loan approval table
     */
    public function deleteLoan($loanApprovalId) {
        $loanApprovalId = mysqli_real_escape_string($this->connection, $loanApprovalId);
        
        // Start transaction
        mysqli_begin_transaction($this->connection);
        
        try {
            // Delete from tbl_loanapproval
            $approvalSql = "DELETE FROM tbl_loanapproval WHERE id = '$loanApprovalId'";
            if (!mysqli_query($this->connection, $approvalSql)) {
                throw new Exception('Failed to delete from tbl_loanapproval: ' . mysqli_error($this->connection));
            }
            
            $approvalAffected = mysqli_affected_rows($this->connection);
            
            // Commit transaction
            mysqli_commit($this->connection);
            
            if ($approvalAffected > 0) {
                return [
                    'success' => true,
                    'message' => 'Loan deleted successfully',
                    'data' => [
                        'approvals_deleted' => $approvalAffected
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No loan found with the specified ID'
                ];
            }
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($this->connection);
            error_log("Delete loan error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete loan: ' . $e->getMessage()
            ];
        }
    }
}
?>