<?php
// api/admin/loan-limits.php
// Admin API for managing loan period limits

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../../config/Database.php';
    
    // Start session for admin authentication
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is admin
    if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] !== 'Admin') {
        throw new Exception('Admin access required', 403);
    }

    $database = new Database();
    $db = $database->getConnection();
    $adminUsername = $_SESSION['complete_name'] ?? 'Admin';

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            getLoanLimits($db);
            break;
        case 'POST':
            createLoanLimit($db, $adminUsername);
            break;
        case 'PUT':
            updateLoanLimit($db, $adminUsername);
            break;
        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Loan limits API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $status_code = $e->getCode();
    if (!is_int($status_code) || $status_code < 100 || $status_code > 599) {
        $status_code = 400;
    }

    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getLoanLimits($db) {
    $periodId = isset($_GET['period_id']) ? intval($_GET['period_id']) : null;

    if ($periodId) {
        // Get specific period limit
        $query = "SELECT 
            id, period_id, limit_amount, set_by, notes, created_at, updated_at
            FROM loan_period_limits 
            WHERE period_id = :period_id
            LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([':period_id' => $periodId]);
        $limit = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$limit) {
            echo json_encode([
                'success' => true,
                'data' => null,
                'message' => 'No limit set for this period'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => intval($limit['id']),
                'period_id' => intval($limit['period_id']),
                'limit_amount' => floatval($limit['limit_amount']),
                'set_by' => $limit['set_by'],
                'notes' => $limit['notes'],
                'created_at' => $limit['created_at'],
                'updated_at' => $limit['updated_at']
            ]
        ]);
    } else {
        // Get all limits
        $query = "SELECT 
            lpl.id, lpl.period_id, lpl.limit_amount, lpl.set_by, lpl.notes, 
            lpl.created_at, lpl.updated_at,
            pp.PayrollPeriod as period_name
            FROM loan_period_limits lpl
            LEFT JOIN tbpayrollperiods pp ON pp.id = lpl.period_id
            ORDER BY lpl.period_id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        // Fetch all periods from salary API to create a lookup map
        $periodMap = [];
        try {
            $apiClientPath = __DIR__ . '/../../../classes/OOUTHSalaryAPIClient.php';
            if (!file_exists($apiClientPath)) {
                $apiClientPath = __DIR__ . '/../../../../classes/OOUTHSalaryAPIClient.php';
            }
            
            if (file_exists($apiClientPath)) {
                require_once $apiClientPath;
                $apiClient = new OOUTHSalaryAPIClient();
                $periodsResult = $apiClient->getPeriods(1, 1000);
                
                if ($periodsResult && isset($periodsResult['success']) && $periodsResult['success'] && isset($periodsResult['data'])) {
                    foreach ($periodsResult['data'] as $period) {
                        $periodId = $period['period_id'] ?? $period['id'] ?? null;
                        if ($periodId) {
                            $description = $period['description'] ?? $period['name'] ?? '';
                            $year = $period['year'] ?? '';
                            $displayName = trim($description . ' ' . $year);
                            if (!empty($displayName)) {
                                $periodMap[intval($periodId)] = $displayName;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching periods from salary API: " . $e->getMessage());
        }

        $limits = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $periodId = intval($row['period_id']);
            
            // Try to get period name from salary API first, then database, then fallback
            $periodName = null;
            if (isset($periodMap[$periodId])) {
                $periodName = $periodMap[$periodId];
            } elseif (!empty($row['period_name'])) {
                $periodName = $row['period_name'];
            } elseif (isset($apiClient)) {
                // Last resort: try to fetch from API directly
                try {
                    $periodResult = $apiClient->getPeriod($periodId);
                    if ($periodResult && isset($periodResult['success']) && $periodResult['success'] && isset($periodResult['data'])) {
                        $periodData = $periodResult['data'];
                        $description = $periodData['description'] ?? $periodData['name'] ?? '';
                        $year = $periodData['year'] ?? '';
                        $periodName = trim($description . ' ' . $year);
                        if (!empty($periodName)) {
                            $periodMap[$periodId] = $periodName; // Cache it
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error fetching period {$periodId} from salary API: " . $e->getMessage());
                }
            }
            
            // Final fallback
            if (empty($periodName)) {
                $periodName = 'Period ' . $periodId;
            }
            
            $limits[] = [
                'id' => intval($row['id']),
                'period_id' => $periodId,
                'limit_amount' => floatval($row['limit_amount']),
                'set_by' => $row['set_by'],
                'notes' => $row['notes'],
                'period_name' => $periodName,
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $limits
        ]);
    }
}

function createLoanLimit($db, $adminUsername) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    $periodId = isset($input['period_id']) ? intval($input['period_id']) : null;
    $limitAmount = isset($input['limit_amount']) ? floatval($input['limit_amount']) : null;
    $notes = isset($input['notes']) ? trim($input['notes']) : null;

    if (!$periodId || !$limitAmount || $limitAmount <= 0) {
        throw new Exception('period_id and limit_amount (must be > 0) are required', 400);
    }

    // Check if limit already exists for this period
    $checkQuery = "SELECT id FROM loan_period_limits WHERE period_id = :period_id LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':period_id' => $periodId]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Limit already exists for this period. Use PUT to update.', 400);
    }

    // Insert new limit
    $insertQuery = "INSERT INTO loan_period_limits 
        (period_id, limit_amount, set_by, notes)
        VALUES (:period_id, :limit_amount, :set_by, :notes)";
    
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([
        ':period_id' => $periodId,
        ':limit_amount' => $limitAmount,
        ':set_by' => $adminUsername,
        ':notes' => $notes
    ]);

    $limitId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Loan limit created successfully',
        'data' => [
            'id' => $limitId,
            'period_id' => $periodId,
            'limit_amount' => $limitAmount
        ]
    ]);
}

function updateLoanLimit($db, $adminUsername) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON data', 400);
    }

    $id = isset($input['id']) ? intval($input['id']) : null;
    $periodId = isset($input['period_id']) ? intval($input['period_id']) : null;
    $limitAmount = isset($input['limit_amount']) ? floatval($input['limit_amount']) : null;
    $notes = isset($input['notes']) ? trim($input['notes']) : null;

    if (!$id && !$periodId) {
        throw new Exception('id or period_id is required', 400);
    }

    if ($limitAmount !== null && $limitAmount <= 0) {
        throw new Exception('limit_amount must be greater than 0', 400);
    }

    // Find the limit to update
    if ($id) {
        $findQuery = "SELECT id FROM loan_period_limits WHERE id = :id LIMIT 1";
        $findStmt = $db->prepare($findQuery);
        $findStmt->execute([':id' => $id]);
        $limit = $findStmt->fetch();
        
        if (!$limit) {
            throw new Exception('Limit not found', 404);
        }
        $updateId = $id;
    } else {
        $findQuery = "SELECT id FROM loan_period_limits WHERE period_id = :period_id LIMIT 1";
        $findStmt = $db->prepare($findQuery);
        $findStmt->execute([':period_id' => $periodId]);
        $limit = $findStmt->fetch();
        
        if (!$limit) {
            throw new Exception('Limit not found for this period', 404);
        }
        $updateId = $limit['id'];
    }

    // Build update query dynamically
    $updateFields = [];
    $params = [':id' => $updateId];

    if ($limitAmount !== null) {
        $updateFields[] = "limit_amount = :limit_amount";
        $params[':limit_amount'] = $limitAmount;
    }

    if ($notes !== null) {
        $updateFields[] = "notes = :notes";
        $params[':notes'] = $notes;
    }

    $updateFields[] = "set_by = :set_by";
    $params[':set_by'] = $adminUsername;

    if (empty($updateFields)) {
        throw new Exception('No fields to update', 400);
    }

    $updateQuery = "UPDATE loan_period_limits 
        SET " . implode(', ', $updateFields) . "
        WHERE id = :id";
    
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Loan limit updated successfully'
    ]);
}