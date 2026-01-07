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
        
        // Ensure database is selected - PDO handles this via DSN, so we typically don't need to select db again
        // But for compatibility if checking, we can try a simple query
        try {
            // $this->connection->query("USE " . $this->database); // Optional if already in DSN
        } catch (PDOException $e) {
             throw new Exception('Database selection failed: ' . $e->getMessage());
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
        
        $stmt = $this->connection->query($sql);
        $employees = [];
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $employees[] = [
                    'coop_id' => $row['CoopID'],
                    'full_name' => trim($row['FullName'] ?? ''),
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
        
        // $searchTerm = mysqli_real_escape_string($this->connection, $searchTerm); // PDO prepares statements instead
        
        $sql = "SELECT 
                    CoopID, 
                    CONCAT(FirstName, ' ', MiddleName, ' ', LastName) AS FullName,
                    FirstName,
                    MiddleName,
                    LastName
                FROM tblemployees 
                WHERE (CoopID LIKE ? 
                    OR FirstName LIKE ? 
                    OR LastName LIKE ? 
                    OR MiddleName LIKE ?)
                    AND Status = 'Active' 
                ORDER BY FirstName, LastName
                LIMIT 10";
        
        $stmt = $this->connection->prepare($sql);
        $term = "%$searchTerm%";
        $stmt->execute([$term, $term, $term, $term]);
        
        $employees = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $employees[] = [
                'coop_id' => $row['CoopID'],
                'full_name' => trim($row['FullName'] ?? ''),
                'first_name' => $row['FirstName'],
                'middle_name' => $row['MiddleName'],
                'last_name' => $row['LastName']
            ];
        }
        
        return $employees;
    }
    
    /**
     * Get employee details including bank information
     */
    public function getEmployeeDetails($coopId) {
        // $coopId = mysqli_real_escape_string($this->connection, $coopId);
        
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
                WHERE e.CoopID = ? AND e.Status = 'Active'";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$coopId]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }
    
    /**
     * Get payroll periods
     */
    public function getPayrollPeriods() {
        $sql = "SELECT * FROM tbpayrollperiods ORDER BY id DESC";
        $stmt = $this->connection->query($sql);
        $periods = [];
        
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        // $coopId = mysqli_real_escape_string($this->connection, $coopId);
        // $periodId = mysqli_real_escape_string($this->connection, $periodId);
        
        // Get employee's shares and savings from master transaction table
        $sql = "SELECT 
                    IFNULL(SUM(sharesAmount), 0) as total_shares,
                    IFNULL(SUM(savingsAmount), 0) as total_savings,
                    IFNULL(SUM(sharesAmount + savingsAmount), 0) as total_shares_savings
                FROM tbl_mastertransact 
                WHERE COOPID = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$coopId]);
        $sharesData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalShares = $sharesData['total_shares'] ?? 0;
        $totalSavings = $sharesData['total_savings'] ?? 0;
        $totalSharesSavings = $sharesData['total_shares_savings'] ?? 0;
        
        // Calculate maximum loan obtainable (typically 3x shares)
        $maxLoanObtainable = $totalShares * 3;
        
        // Get current loan balance (total loans - total repayments)
        $loanBalanceSql = "SELECT 
                            (SELECT IFNULL(SUM(LoanAmount), 0) 
                             FROM tbl_loans 
                             WHERE CoopID = ?) as total_loans,
                            (SELECT IFNULL(SUM(loanRepayment), 0) 
                             FROM tbl_mastertransact 
                             WHERE COOPID = ?) as total_repayments,
                            (SELECT COUNT(*) 
                             FROM tbl_loans 
                             WHERE CoopID = ?) as loan_count,
                            (SELECT COUNT(*) 
                             FROM tbl_mastertransact 
                             WHERE COOPID = ? AND loanRepayment > 0) as repayment_count";
        
        $stmtBalance = $this->connection->prepare($loanBalanceSql);
        $stmtBalance->execute([$coopId, $coopId, $coopId, $coopId]);
        $loanBalanceData = $stmtBalance->fetch(PDO::FETCH_ASSOC);
        
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
        $coopId = $data['coop_id'];
        $loanAmount = floatval($data['loan_amount']);
        $periodId = $data['period_id'];
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
        $this->connection->beginTransaction();
        
        try {
            // Update existing loan in tbl_loanapproval
            $updateSql = "UPDATE tbl_loanapproval 
                         SET LoanAmount = ?, 
                             MonthlyRepayment = ?,
                             batch = ?,
                             insertedBy = ?
                         WHERE coopID = ? AND period = ?";
            
            $stmtUpdate = $this->connection->prepare($updateSql);
            $stmtUpdate->execute([$loanAmount, $monthlyRepayment, $batch, $insertedBy, $coopId, $periodId]);
            $affectedRows = $stmtUpdate->rowCount();
            
            if ($affectedRows === 0) {
                // If no existing loan found, create a new one
                $today = date('Y-m-d');
                $insertSql = "INSERT INTO tbl_loanapproval 
                             (coopID, period, approvalDate, LoanAmount, StationeryPayment, MonthlyRepayment, insertedBy, batch) 
                             VALUES (?, ?, ?, ?, 0.00, ?, ?, ?)";
                
                $stmtInsert = $this->connection->prepare($insertSql);
                $stmtInsert->execute([$coopId, $periodId, $today, $loanAmount, $monthlyRepayment, $insertedBy, $batch]);
                
                $approvalId = $this->connection->lastInsertId();
                $message = 'New loan created successfully';
            } else {
                $message = 'Loan updated successfully';
                $approvalId = null; // We don't have the ID for updates
            }
            
            // Commit transaction
            $this->connection->commit();
            
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
            $this->connection->rollBack();
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
                WHERE l.CoopID = ?";
        
        $params = [$coopId];
        
        if ($periodId) {
            $sql .= " AND l.LoanPeriod = ?";
            $params[] = $periodId;
        }
        
        $sql .= " ORDER BY l.DateOfLoanApp DESC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $loans = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        
        return $loans;
    }
    
    /**
     * Get loan approval data for an employee
     */
    public function getLoanApprovalData($periodId) {
        
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
                WHERE la.period = ?
                ORDER BY la.approvalDate DESC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$periodId]);
        
        $approvals = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        
        return $approvals;
    }
    
    /**
     * Get current loan balance for an employee
     */
    public function getCurrentLoanBalance($coopId) {
        
        $sql = "SELECT 
                    (SELECT IFNULL(SUM(LoanAmount), 0) 
                     FROM tbl_loans 
                     WHERE CoopID = ?) as total_loans,
                    (SELECT IFNULL(SUM(loanRepayment), 0) 
                     FROM tbl_mastertransact 
                     WHERE COOPID = ?) as total_repayments";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$coopId, $coopId]);
        
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
                WHERE la.period = ?
                ORDER BY la.approvalDate DESC, la.coopID ASC";
        
        // error_log("Current Period Loans SQL: " . $sql);
        $stmt = $this->connection->prepare($sql);
        
        try {
            $stmt->execute([$periodId]);
            
            $loans = [];
            $totalLoanAmount = 0;
            $totalMonthlyRepayment = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
            
            // error_log("Current Period Loans Result: " . json_encode($result_data));
            return $result_data;
            
        } catch (PDOException $e) {
            error_log("Current Period Loans Query Error: " . $e->getMessage());
            
            return [
                'loans' => [],
                'total_loan_amount' => 0,
                'total_monthly_repayment' => 0,
                'count' => 0
            ];
        }
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
        
        // Start transaction
        $this->connection->beginTransaction();
        
        try {
            // Delete from tbl_loanapproval
            $approvalSql = "DELETE FROM tbl_loanapproval WHERE id = ?";
            $stmt = $this->connection->prepare($approvalSql);
            $stmt->execute([$loanApprovalId]);
            
            $approvalAffected = $stmt->rowCount();
            
            // Commit transaction
            $this->connection->commit();
            
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
            $this->connection->rollBack();
            error_log("Delete loan error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete loan: ' . $e->getMessage()
            ];
        }
    }
}
?>