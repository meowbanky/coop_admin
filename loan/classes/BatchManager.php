<?php
/**
 * BatchManager Class
 * Handles all batch-related business logic
 */

class BatchManager {
    private $connection;
    private $database;
    
    public function __construct($connection, $database) {
        $this->connection = $connection;
        $this->database = $database;
    }
    
    /**
     * Handle incoming requests
     */
    public function handleRequest($data) {
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'create_batch':
                    return $this->createBatch($data);
                case 'generate_batch':
                    return $this->generateBatchNumber();
                default:
                    return ['success' => false, 'message' => 'Invalid action'];
            }
        }
        return ['success' => false, 'message' => 'No action specified'];
    }
    
    /**
     * Create a new batch
     */
    public function createBatch($data) {
        $batchNumber = $this->sanitizeInput($data['batch'] ?? '');
        
        if (empty($batchNumber)) {
            return ['success' => false, 'message' => 'Batch number is required'];
        }
        
        if ($this->batchExists($batchNumber)) {
            return ['success' => false, 'message' => 'Batch number already exists'];
        }
        
        try {
            $sql = "INSERT INTO tbl_batch (batch) VALUES (?)";
            $stmt = mysqli_prepare($this->connection, $sql);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . mysqli_error($this->connection));
            }
            
            mysqli_stmt_bind_param($stmt, "s", $batchNumber);
            $result = mysqli_stmt_execute($stmt);
            
            if ($result) {
                $batchId = mysqli_insert_id($this->connection);
                mysqli_stmt_close($stmt);
                return [
                    'success' => true, 
                    'message' => 'Batch created successfully',
                    'data' => ['batch_id' => $batchId]
                ];
            } else {
                mysqli_stmt_close($stmt);
                return ['success' => false, 'message' => 'Failed to create batch'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate a new batch number
     */
    public function generateBatchNumber() {
        $now = new DateTime();
        
        // Get day with ordinal suffix (1st, 2nd, 3rd, 4th, etc.)
        $day = $now->format('j');
        $daySuffix = $this->getOrdinalSuffix($day);
        
        // Get month name (January, February, etc.)
        $month = $now->format('F');
        
        // Get year in short format (25 for 2025)
        $year = $now->format('y');
        
        // Get time in 12-hour format with AM/PM (remove colon)
        $time = $now->format('giA');
        
        // Combine all parts (no special characters, only letters and numbers)
        $batchNumber = $day . $daySuffix . $month . $year . $time;
        
        return $batchNumber;
    }
    
    /**
     * Get ordinal suffix for day (st, nd, rd, th)
     */
    private function getOrdinalSuffix($day) {
        if ($day >= 11 && $day <= 13) {
            return 'th';
        }
        
        switch ($day % 10) {
            case 1:
                return 'st';
            case 2:
                return 'nd';
            case 3:
                return 'rd';
            default:
                return 'th';
        }
    }
    
    /**
     * Get all batches with transaction counts
     */
    public function getAllBatches() {
        try {
            $sql = "SELECT 
                        count(BeneficiaryCode) AS transaction_count,
                        tbl_batch.Batch AS batch_number,
                        any_value(tbl_batch.batchid) AS batch_id
                    FROM tbl_batch 
                    LEFT JOIN excel ON tbl_batch.batch = excel.Batch 
                    GROUP BY tbl_batch.Batch 
                    ORDER BY batchid DESC";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $batches = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $batches[] = $row;
            }
            
            mysqli_free_result($result);
            return $batches;
        } catch (Exception $e) {
            error_log("Error fetching batches: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if batch number already exists
     */
    private function batchExists($batchNumber) {
        try {
            $sql = "SELECT COUNT(*) FROM tbl_batch WHERE batch = ?";
            $stmt = mysqli_prepare($this->connection, $sql);
            
            if (!$stmt) {
                return false;
            }
            
            mysqli_stmt_bind_param($stmt, "s", $batchNumber);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $count);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            
            return $count > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Sanitize input data
     */
    private function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get batch statistics
     */
    public function getBatchStatistics() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_batches,
                        SUM(CASE WHEN BeneficiaryCode IS NOT NULL THEN 1 ELSE 0 END) as total_transactions
                    FROM tbl_batch 
                    LEFT JOIN excel ON tbl_batch.batch = excel.Batch";
            
            $result = mysqli_query($this->connection, $sql);
            if (!$result) {
                throw new Exception("Query failed: " . mysqli_error($this->connection));
            }
            
            $stats = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            
            return $stats ? $stats : [];
        } catch (Exception $e) {
            error_log("Error fetching statistics: " . $e->getMessage());
            return [];
        }
    }
}
?>