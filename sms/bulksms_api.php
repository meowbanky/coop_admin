<?php
header('Content-Type: application/json');
require_once('Connections/hms.php');
require_once('NotificationService.php');
use class\services\NotificationService;

$response = ['status' => 'error', 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'fetch_all_contacts') {
            // Fetch all active numbers
            $query = "SELECT MobilePhone FROM tbl_personalinfo WHERE Status = 'Active' AND MobilePhone IS NOT NULL AND MobilePhone != ''";
            $stmt = $conn->query($query);
            $numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Clean numbers (basic trim)
            $cleanNumbers = array_map('trim', $numbers);
            $cleanNumbers = array_filter($cleanNumbers); // Remove empty

            $response['status'] = 'success';
            $response['data'] = array_values($cleanNumbers);
            $response['count'] = count($cleanNumbers);
        }

        elseif ($action === 'get_balance') {
            $notificationService = new NotificationService($conn);
            $balance = $notificationService->getSMSBalance();
            
            $response['status'] = 'success';
            $response['data'] = [
                'balance' => $balance,
                'currency' => 'NGN' // Assumed based on context
            ];
        }

        elseif ($action === 'search_members') {
            $term = $_POST['term'] ?? '';
            if (strlen($term) < 3) throw new Exception("Search term must be at least 3 characters.");

            // Search by Name or ID
            $query = "SELECT patientid, CONCAT(Fname, ' ', Lname) as fullname, MobilePhone 
                      FROM tbl_personalinfo 
                      WHERE (Fname LIKE :term OR Lname LIKE :term OR patientid LIKE :term) 
                      AND Status = 'Active' AND MobilePhone IS NOT NULL AND MobilePhone != ''
                      LIMIT 20";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([':term' => "%$term%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['status'] = 'success';
            $response['data'] = $results;
        }

        elseif ($action === 'send_bulk_sms') {
            $recipientsStr = $_POST['recipients'] ?? '';
            $message = $_POST['message'] ?? '';

            if (empty($recipientsStr) || empty($message)) {
                throw new Exception("Recipients and Message are required.");
            }

            // Parse numbers (comma separated)
            $recipients = explode(',', $recipientsStr);
            $recipients = array_map('trim', $recipients);
            $recipients = array_filter($recipients);
            
            $recipientCount = count($recipients);
            if ($recipientCount === 0) {
                 throw new Exception("No valid recipients found.");
            }

            $notificationService = new NotificationService($conn);
            
            // --- BALANCE CHECK ---
            $currentBalance = $notificationService->getSMSBalance();
            $estimatedCost = $notificationService->calculateTransactionCost($message, $recipientCount);
            
            if ($currentBalance < $estimatedCost) {
                throw new Exception("Insufficient SMS Balance. Required: NGN " . number_format($estimatedCost, 2) . ", Available: NGN " . number_format($currentBalance, 2));
            }

            // Chunk recipients into batches of 100 (Termii API limit)
            $chunks = array_chunk($recipients, 100);
            $totalSubmitted = 0;
            $batchResults = [];
            $hasErrors = false;

            foreach ($chunks as $index => $chunk) {
                try {
                    $result = $notificationService->sendBulkSMS($chunk, $message);
                    $totalSubmitted += count($chunk);
                    $batchResults[] = [
                        'batch' => $index + 1,
                        'status' => 'success',
                        'count' => count($chunk),
                        'response' => $result,
                        'debug_numbers' => $result['debug_numbers'] ?? []
                    ];
                } catch (Exception $e) {
                    $hasErrors = true;
                    $batchResults[] = [
                        'batch' => $index + 1,
                        'status' => 'error',
                        'count' => count($chunk),
                        'message' => $e->getMessage()
                    ];
                }
                
                // valid "nice" pause between batches
                if (count($chunks) > 1) usleep(200000); // 0.2s pause
            }

            $response['status'] = $hasErrors && $totalSubmitted == 0 ? 'error' : 'success';
            $response['message'] = "Processed " . count($recipients) . " recipients. Submitted: $totalSubmitted.";
            $response['data'] = [
                'total_processed' => count($recipients),
                'total_submitted' => $totalSubmitted,
                'batches' => $batchResults
            ];
        }

        else {
            throw new Exception("Invalid Action");
        }

    } else {
        throw new Exception("Invalid Request Method");
    }

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
