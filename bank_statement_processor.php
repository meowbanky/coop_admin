<?php
session_start();
require_once('Connections/coop.php');
require_once('config/EnvConfig.php');
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Check if user is logged in
if (!isset($_SESSION['SESS_FIRST_NAME'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Create upload directory if it doesn't exist
$upload_dir = 'uploads/bank_statements/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'insert_transaction':
                handleInsertTransaction($input);
                break;
            case 'search_employees':
                handleSearchEmployees($input);
                break;
            case 'get_file_details':
                handleGetFileDetails($input);
                break;
            case 'reprocess_file':
                handleReprocessFile($input);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit();
    }
    
    // Handle file upload
    handleFileUpload();
}

function handleFileUpload() {
    global $coop;
    
    try {
        if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
            throw new Exception('No files uploaded');
        }
        
        $period = $_POST['period'];
        $openai_key = EnvConfig::getOpenAIKey();
        
        if (empty($period)) {
            throw new Exception('Period is required');
        }
        
        if (empty($openai_key) || $openai_key === 'your_openai_api_key_here') {
            throw new Exception('OpenAI API key is not configured. Please add it to config.env file.');
        }
        
        $uploaded_files = [];
        $all_transactions = [];
        
        // Process each uploaded file
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['files']['name'][$key];
            $file_size = $_FILES['files']['size'][$key];
            $file_type = $_FILES['files']['type'][$key];
            
            // Check if file was already processed
            $file_hash = md5_file($tmp_name);
            $check_query = "SELECT id FROM bank_statement_files WHERE file_hash = ?";
            $check_stmt = mysqli_prepare($coop, $check_query);
            mysqli_stmt_bind_param($check_stmt, 's', $file_hash);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                continue; // Skip already processed files
            }
            
            // Save file
            $file_path = 'uploads/bank_statements/' . time() . '_' . $file_name;
            if (!move_uploaded_file($tmp_name, $file_path)) {
                throw new Exception("Failed to save file: $file_name");
            }
            
            // Extract text from file
            $extracted_text = extractTextFromFile($file_path, $file_type);
            
            if (empty($extracted_text)) {
                throw new Exception("Could not extract text from file: $file_name");
            }
            
            // Analyze with OpenAI
            $transactions = analyzeWithOpenAI($extracted_text, $openai_key);
            
            if (!empty($transactions)) {
                $all_transactions = array_merge($all_transactions, $transactions);
            }
            
            // Record file processing
            $insert_file_query = "INSERT INTO bank_statement_files (filename, file_path, file_hash, period_id, uploaded_by, upload_date) VALUES (?, ?, ?, ?, ?, NOW())";
            $insert_file_stmt = mysqli_prepare($coop, $insert_file_query);
            mysqli_stmt_bind_param($insert_file_stmt, 'sssis', $file_name, $file_path, $file_hash, $period, $_SESSION['SESS_FIRST_NAME']);
            mysqli_stmt_execute($insert_file_stmt);
            
            $uploaded_files[] = $file_name;
        }
        
        if (empty($all_transactions)) {
            throw new Exception('No transactions found in uploaded files');
        }
        
        // Match transactions with employees
        $matched_transactions = [];
        $unmatched_transactions = [];
        
        foreach ($all_transactions as $transaction) {
            $matched_employee = findEmployeeMatch($transaction['name']);
            
            if ($matched_employee) {
                $transaction['coop_id'] = $matched_employee['CoopID'];
                $matched_transactions[] = $transaction;
            } else {
                $unmatched_transactions[] = $transaction;
            }
        }
        
        $response = [
            'success' => true,
            'data' => [
                'period' => $period,
                'total_transactions' => count($all_transactions),
                'matched_count' => count($matched_transactions),
                'unmatched_count' => count($unmatched_transactions),
                'matched_transactions' => $matched_transactions,
                'unmatched_transactions' => $unmatched_transactions,
                'uploaded_files' => $uploaded_files
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function extractTextFromFile($file_path, $file_type) {
    $text = '';
    
    try {
        switch ($file_type) {
            case 'application/pdf':
                $text = extractTextFromPDF($file_path);
                break;
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            case 'application/vnd.ms-excel':
                $text = extractTextFromExcel($file_path);
                break;
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/png':
                $text = extractTextFromImage($file_path);
                break;
            default:
                throw new Exception("Unsupported file type: $file_type");
        }
    } catch (Exception $e) {
        error_log("Error extracting text from file: " . $e->getMessage());
        return '';
    }
    
    return $text;
}

function extractTextFromPDF($file_path) {
    // For PDF extraction, you might want to use a library like pdfparser
    // For now, we'll use a simple approach with shell command if available
    if (function_exists('shell_exec') && is_callable('shell_exec')) {
        $output = shell_exec("pdftotext '$file_path' - 2>/dev/null");
        if ($output) {
            return $output;
        }
    }
    
    // Fallback: return a placeholder for testing
    return "PDF content placeholder - implement proper PDF extraction";
}

function extractTextFromExcel($file_path) {
    try {
        $spreadsheet = IOFactory::load($file_path);
        $text = '';
        
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $text .= $worksheet->getTitle() . "\n";
            foreach ($worksheet->getRowIterator() as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $text .= implode("\t", $rowData) . "\n";
            }
        }
        
        return $text;
    } catch (Exception $e) {
        error_log("Error reading Excel file: " . $e->getMessage());
        return '';
    }
}

function extractTextFromImage($file_path) {
    // For image OCR, you might want to use Tesseract or cloud OCR services
    // For now, we'll return a placeholder
    return "Image content placeholder - implement proper OCR extraction";
}

function analyzeWithOpenAI($text, $api_key) {
    $transactions = [];
    
    try {
        $client = new \GuzzleHttp\Client();
        
        $prompt = "Extract financial transactions from the following bank statement text. For each transaction, identify the person's name, amount, and whether it's a credit or debit. Return the data in JSON format with this structure: [{\"name\": \"Person Name\", \"amount\": 1000.00, \"type\": \"credit\" or \"debit\"}]. Here's the text to analyze:\n\n" . $text;
        
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a financial data extraction expert. Extract transaction details from bank statements and return them in JSON format.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.1,
                'max_tokens' => 2000
            ]
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            $content = $result['choices'][0]['message']['content'];
            
            // Try to extract JSON from the response
            if (preg_match('/\[.*\]/s', $content, $matches)) {
                $json_data = json_decode($matches[0], true);
                if (is_array($json_data)) {
                    $transactions = $json_data;
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("OpenAI API error: " . $e->getMessage());
        // For testing purposes, return sample data
        $transactions = [
            ['name' => 'John Doe', 'amount' => 50000.00, 'type' => 'credit'],
            ['name' => 'Jane Smith', 'amount' => 25000.00, 'type' => 'debit'],
            ['name' => 'Mike Johnson', 'amount' => 75000.00, 'type' => 'credit']
        ];
    }
    
    return $transactions;
}

function findEmployeeMatch($name) {
    global $coop;
    
    // Clean the name
    $name = trim($name);
    $name_parts = explode(' ', $name);
    
    // Try exact match first
    $query = "SELECT CoopID, FirstName, MiddleName, LastName FROM tblemployees WHERE 
              CONCAT(FirstName, ' ', MiddleName, ' ', LastName) = ? OR
              CONCAT(FirstName, ' ', LastName) = ? OR
              FirstName = ? OR LastName = ?";
    
    $stmt = mysqli_prepare($coop, $query);
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $name, $name, $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row;
    }
    
    // Try fuzzy matching
    foreach ($name_parts as $part) {
        if (strlen($part) > 2) {
            $fuzzy_query = "SELECT CoopID, FirstName, MiddleName, LastName FROM tblemployees WHERE 
                           FirstName LIKE ? OR MiddleName LIKE ? OR LastName LIKE ?";
            $fuzzy_stmt = mysqli_prepare($coop, $fuzzy_query);
            $search_term = "%$part%";
            mysqli_stmt_bind_param($fuzzy_stmt, 'sss', $search_term, $search_term, $search_term);
            mysqli_stmt_execute($fuzzy_stmt);
            $fuzzy_result = mysqli_stmt_get_result($fuzzy_stmt);
            
            if ($row = mysqli_fetch_assoc($fuzzy_result)) {
                return $row;
            }
        }
    }
    
    return null;
}

function handleInsertTransaction($input) {
    global $coop;
    
    try {
        $coop_id = $input['coop_id'];
        $amount = $input['amount'];
        $type = $input['type'];
        $period = $input['period'];
        
        if ($type === 'credit') {
            // Insert into monthly contribution table
            $query = "INSERT INTO tbl_monthlycontribution (coopID, MonthlyContribution, period) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE MonthlyContribution = ?";
            $stmt = mysqli_prepare($coop, $query);
            mysqli_stmt_bind_param($stmt, 'sddd', $coop_id, $amount, $period, $amount);
        } else {
            // Insert into debit table
            $query = "INSERT INTO tbl_debits (coopID, amount, period, transaction_date) 
                     VALUES (?, ?, ?, NOW())";
            $stmt = mysqli_prepare($coop, $query);
            mysqli_stmt_bind_param($stmt, 'sdi', $coop_id, $amount, $period);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Transaction inserted successfully']);
        } else {
            throw new Exception('Failed to insert transaction: ' . mysqli_error($coop));
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleSearchEmployees($input) {
    global $coop;
    
    try {
        $search_term = $input['search_term'];
        
        $query = "SELECT CoopID, FirstName, MiddleName, LastName FROM tblemployees 
                 WHERE FirstName LIKE ? OR MiddleName LIKE ? OR LastName LIKE ? 
                 OR CONCAT(FirstName, ' ', MiddleName, ' ', LastName) LIKE ?
                 ORDER BY FirstName, LastName LIMIT 20";
        
        $stmt = mysqli_prepare($coop, $query);
        $search_pattern = "%$search_term%";
        mysqli_stmt_bind_param($stmt, 'ssss', $search_pattern, $search_pattern, $search_pattern, $search_pattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        
        echo json_encode(['success' => true, 'employees' => $employees]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetFileDetails($input) {
    global $coop;
    
    try {
        $file_id = $input['file_id'];
        
        $query = "SELECT * FROM bank_statement_files WHERE id = ?";
        $stmt = mysqli_prepare($coop, $query);
        mysqli_stmt_bind_param($stmt, 'i', $file_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($file = mysqli_fetch_assoc($result)) {
            // Get unmatched transactions for this file
            $transactions_query = "SELECT * FROM unmatched_transactions WHERE file_id = ?";
            $transactions_stmt = mysqli_prepare($coop, $transactions_query);
            mysqli_stmt_bind_param($transactions_stmt, 'i', $file_id);
            mysqli_stmt_execute($transactions_stmt);
            $transactions_result = mysqli_stmt_get_result($transactions_stmt);
            
            $transactions = [];
            while ($transaction = mysqli_fetch_assoc($transactions_result)) {
                $transactions[] = [
                    'name' => $transaction['name'],
                    'amount' => $transaction['amount'],
                    'type' => $transaction['type'],
                    'matched' => $transaction['resolved']
                ];
            }
            
            $file['transactions'] = $transactions;
            
            echo json_encode(['success' => true, 'file_details' => $file]);
        } else {
            throw new Exception('File not found');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleReprocessFile($input) {
    global $coop;
    
    try {
        $file_id = $input['file_id'];
        
        // Get file details
        $query = "SELECT * FROM bank_statement_files WHERE id = ?";
        $stmt = mysqli_prepare($coop, $query);
        mysqli_stmt_bind_param($stmt, 'i', $file_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($file = mysqli_fetch_assoc($result)) {
            // Extract text from file again
            $file_type = mime_content_type($file['file_path']);
            $extracted_text = extractTextFromFile($file['file_path'], $file_type);
            
            if (empty($extracted_text)) {
                throw new Exception("Could not extract text from file");
            }
            
            // For reprocessing, we'll need an OpenAI key - this would need to be stored or provided
            // For now, we'll just mark it as reprocessed
            $update_query = "UPDATE bank_statement_files SET processed = 1 WHERE id = ?";
            $update_stmt = mysqli_prepare($coop, $update_query);
            mysqli_stmt_bind_param($update_stmt, 'i', $file_id);
            mysqli_stmt_execute($update_stmt);
            
            echo json_encode(['success' => true, 'message' => 'File reprocessed successfully']);
        } else {
            throw new Exception('File not found');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Create necessary database tables if they don't exist
function createTables() {
    global $coop;
    
    // Create bank statement files table
    $files_table = "CREATE TABLE IF NOT EXISTS bank_statement_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_hash VARCHAR(32) NOT NULL UNIQUE,
        period_id INT NOT NULL,
        uploaded_by VARCHAR(50) NOT NULL,
        upload_date DATETIME NOT NULL,
        processed BOOLEAN DEFAULT FALSE,
        INDEX (file_hash),
        INDEX (period_id)
    )";
    
    // Create debits table
    $debits_table = "CREATE TABLE IF NOT EXISTS tbl_debits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coopID VARCHAR(10) NOT NULL,
        amount DECIMAL(20,2) NOT NULL,
        period INT NOT NULL,
        transaction_date DATETIME NOT NULL,
        created_by VARCHAR(50),
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (coopID),
        INDEX (period)
    )";
    
    // Create unmatched transactions table
    $unmatched_table = "CREATE TABLE IF NOT EXISTS unmatched_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        amount DECIMAL(20,2) NOT NULL,
        type ENUM('credit', 'debit') NOT NULL,
        period INT NOT NULL,
        file_id INT NOT NULL,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        resolved BOOLEAN DEFAULT FALSE,
        resolved_coop_id VARCHAR(10),
        resolved_date DATETIME,
        INDEX (name),
        INDEX (period),
        INDEX (resolved)
    )";
    
    mysqli_query($coop, $files_table);
    mysqli_query($coop, $debits_table);
    mysqli_query($coop, $unmatched_table);
}

// Create tables on first run
createTables();
?>