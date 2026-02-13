<?php
namespace App\Services;

class NotificationService {
    private $db;
    private $oneSignalConfig;
    private $smsConfig;

    public function __construct($db) {
        $this->db = $db;
        $this->oneSignalConfig = \EnvConfig::getOneSignalConfig();
        $this->smsConfig = \EnvConfig::getSmsConfig();
    }

    public function sendTransactionNotification($memberId, $periodId) {
        try {
            // Get transaction details
            $transactionData = $this->getTransactionDetails($memberId, $periodId);
            if (!$transactionData) {
               // throw new \Exception("No transaction data found");
               return false;
            }

            // Format message
            $message = $this->formatTransactionMessage($transactionData);

            // Send notifications
            $smsResult = $this->sendSMS($transactionData['MobileNumber'], $message);
            error_log("SMS Response: " . json_encode($smsResult));
            error_log("Mobile Number: " . json_encode($transactionData['MobileNumber']));

            if (!empty($transactionData['onesignal_id'])) {
                $this->sendPushNotification(
                    $transactionData['onesignal_id'],
                    "Transaction Update",
                    $message
                );
            }

            // Log notification
            $this->logNotification($memberId, $message, "Transaction Update");

            return true;
        } catch (\Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }

    private function getTransactionDetails($memberId, $periodId) {
        $query = "SELECT 
    tbl_mastertransact.COOPID,concat(LEFT(tbpayrollperiods.PhysicalMonth, 3),'-',tbpayrollperiods.PhysicalYear) as `month`,
    tbpayrollperiods.id,
    CONCAT(tblemployees.LastName, ' , ', tblemployees.FirstName, ' ', IFNULL(tblemployees.MiddleName, '')) AS namess,
    tblemployees.MobileNumber,
    SUM(tbl_mastertransact.entryFee) as EntryFee,
    SUM(tbl_mastertransact.savingsAmount) as savingsAmount,
    SUM(tbl_mastertransact.sharesAmount) as sharesAmount,
    SUM(tbl_mastertransact.InterestPaid) as InterestPaid,
    SUM(tbl_mastertransact.DevLevy) as DevLevy,
    SUM(tbl_mastertransact.loan) as loan,
    SUM(tbl_mastertransact.loanRepayment) as loanRepayment,
    SUM(tbl_mastertransact.Commodity) as Commodity,
    SUM(tbl_mastertransact.CommodityRepayment) as CommodityRepayment,
    (
        SELECT 
            SUM(m2.loan) - SUM(m2.loanRepayment)
        FROM tbl_mastertransact m2
        WHERE m2.COOPID = tbl_mastertransact.COOPID
        AND m2.TransactionPeriod <= tbpayrollperiods.id
    ) as loanBalance,
    (
        SELECT 
            SUM(m2.savingsAmount)
        FROM tbl_mastertransact m2
        WHERE m2.COOPID = tbl_mastertransact.COOPID
        AND m2.TransactionPeriod <= tbpayrollperiods.id
    ) as savingsBalance,
    (
        SELECT 
            SUM(m2.sharesAmount)
        FROM tbl_mastertransact m2
        WHERE m2.COOPID = tbl_mastertransact.COOPID
        AND m2.TransactionPeriod <= tbpayrollperiods.id
    ) as sharesBalance,
    (
        SELECT 
            SUM(m2.Commodity) - SUM(m2.CommodityRepayment)
        FROM tbl_mastertransact m2
        WHERE m2.COOPID = tbl_mastertransact.COOPID
        AND m2.TransactionPeriod <= tbpayrollperiods.id
    ) as commodityBalance,
    SUM(tbl_mastertransact.savingsAmount + 
        tbl_mastertransact.sharesAmount + 
        tbl_mastertransact.InterestPaid + 
        tbl_mastertransact.DevLevy + 
        tbl_mastertransact.Stationery + 
        tbl_mastertransact.EntryFee + tbl_mastertransact.CommodityRepayment
        + tbl_mastertransact.loanRepayment)  as total
        FROM tbl_mastertransact 
        INNER JOIN tblemployees ON tbl_mastertransact.COOPID = tblemployees.CoopID
        LEFT JOIN tbpayrollperiods ON tbl_mastertransact.TransactionPeriod = tbpayrollperiods.id 
        WHERE tblemployees.CoopID = :memberId 
        AND tbl_mastertransact.TransactionPeriod = :periodId
        GROUP BY tbl_mastertransact.COOPID, tbpayrollperiods.id, tblemployees.LastName, tblemployees.FirstName, tblemployees.MiddleName, tblemployees.MobileNumber
        ORDER BY tbpayrollperiods.id DESC LIMIT 1";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':memberId' => $memberId,
                ':periodId' => (int)$periodId
            ]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            throw new \Exception("Database query failed: " . $e->getMessage());
        }
    }

    private function formatTransactionMessage($data) {
        return sprintf(
            "CONTR.: %s\n" .
            "SAVINGS: %s\n" .
            "SAVINGS BAL: %s\n" .
            "SHARES: %s\n" .
            "SHARES BAL: %s\n" .
            "INT PAID: %s\n" .
            "LOAN: %s\n" .
            "LOAN REPAY: %s\n" .
            "LOAN BAL: %s\n" .
            "COMDTY REPAY: %s\n" .
            "COMDTY BAL: %s\n" .
            "AS AT: %s ENDING\n" .
            "Download our mobile app here: %s",
            number_format(floatval($data['total']), 2, '.', ','),
            number_format(floatval($data['savingsAmount']), 2, '.', ','),
            number_format(floatval($data['savingsBalance']), 2, '.', ','),
            number_format(floatval($data['sharesAmount']), 2, '.', ','),
            number_format(floatval($data['sharesBalance']), 2, '.', ','),
            number_format(floatval($data['InterestPaid']), 2, '.', ','),
            number_format(floatval($data['loan']), 2, '.', ','),
            number_format(floatval($data['loanRepayment']), 2, '.', ','),
            number_format(floatval($data['loanBalance']), 2, '.', ','),
            number_format(floatval($data['CommodityRepayment']), 2, '.', ','),
            number_format(floatval($data['commodityBalance']), 2, '.', ','),
            $data['month'],
            "https://emmaggi.com/coop_admin/download.html"
        );
    }

    private function sendSMS($phone, $message) {
        if (empty($phone)) {
            // throw new \Exception("Phone number is required");
             return false;
        }

        $phone = $this->formatPhoneNumber($phone);

        $data = [
            "api_key" => $this->smsConfig['apiKey'],
            "to" => $phone,  // Single phone number, not array
            "from" => $this->smsConfig['sender'],
            "sms" => $message,
            "type" => "plain",
            "channel" => "generic"
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->smsConfig['endpoint'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Curl error: $error");
        }

        curl_close($ch);

        $responseData = json_decode($response, true);
//        error_log("Full SMS API Response: " . $response);

        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : $response;
            throw new \Exception("SMS API Error ($httpCode): $errorMessage");
        }

        return $responseData;
    }

    private function sendPushNotification($playerId, $title, $message) {
        if (empty($playerId)) {
            return false;
        }

        $fields = [
            'app_id' => $this->oneSignalConfig['appId'],
            'include_player_ids' => [$playerId],
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'priority' => 10
        ];

        $ch = curl_init('https://onesignal.com/api/v1/notifications');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . $this->oneSignalConfig['apiKey']
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($fields),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("OneSignal API Error: $response");
        }

        return json_decode($response, true);
    }

    private function formatPhoneNumber($phone) {
        $phone = trim($phone);
        if (substr($phone, 0, 1) === '0') {
            return '234' . substr($phone, 1);
        } elseif (substr($phone, 0, 1) === '+') {
            return substr($phone, 1);
        }
        return $phone;
    }

    private function logNotification($memberId, $message, $title = 'Transaction Alert') {
        $query = "INSERT INTO notifications 
                  (memberid, message, created_at, status, title) 
                  VALUES 
                  (:memberId, :message, NOW(), 'unread', :title)";
        
        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':memberId' => $memberId,
                ':message' => $message,
                ':title' => $title
            ]);
        } catch (\PDOException $e) {
            error_log("Log Notification Error: " . $e->getMessage());
            return false;
        }
    }
    public function getSMSBalance() {
        $apiKey = $this->smsConfig['apiKey'];
        
        if (empty($apiKey)) {
            error_log("Termii Balance Error: API Key is empty.");
            return 0;
        }

        $url = "https://v3.api.termii.com/api/get-balance?api_key=" . urlencode(trim($apiKey));
        
        return $this->executeCurlRequest($url, [], 'GET');
    }

    public function getSMSInbox() {
        $apiKey = $this->smsConfig['apiKey'];
        
        if (empty($apiKey)) {
            error_log("Termii Inbox Error: API Key is empty.");
            return [];
        }

        $url = "https://v3.api.termii.com/api/sms/inbox?api_key=" . urlencode(trim($apiKey));

        // executeCurlRequest expects array for POST, but we can adapt it or just use simple GET here?
        // Let's modify executeCurlRequest to handle GET if data is empty or add method param.
        // Or just implement simple GET here since executeCurlRequest in source was designed for POST (json payload).
        // Actually looking at source executeCurlRequest, it forces POST.
        // Let's create a flexible executeCurlRequest or just copy the logic.
        // To keep it simple and robust, check source getSMSInbox logic -> it used simple CURL GET.
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) return [];
        
        $data = json_decode($response, true);
        return is_array($data) ? $data : [];
    }

    public function sendBulkSMS(array $phoneNumbers, $message, $channel = 'generic') {
        if (empty($phoneNumbers)) {
            throw new \Exception("Phone numbers are required");
        }

        // Re-index array to be safe JSON
        // Using existing formatPhoneNumber
        $formattedNumbers = array_values(array_map([$this, 'formatPhoneNumber'], $phoneNumbers));

        $data = [
            "api_key" => $this->smsConfig['apiKey'],
            "to" => $formattedNumbers,
            "from" => $this->smsConfig['sender'],
            "sms" => $message,
            "type" => "plain",
            "channel" => $channel
        ];

        $url = "https://v3.api.termii.com/api/sms/send/bulk";

        return $this->executeCurlRequest($url, $data);
    }

    public function calculateTransactionCost($message, $recipientCount) {
        $costPerPage = 5.0;
        
        $isSpecial = false;
        if (preg_match('/[\^\{\}\\\\\[\~\]\|\€\”]/u', $message)) {
            $isSpecial = true;
        }

        $len = mb_strlen($message, 'UTF-8');
        $pages = 1;
        
        if ($isSpecial) {
             if ($len > 70) {
                 $pages = ceil($len / 67);
             }
        } else {
            if ($len > 160) {
                $pages = ceil($len / 153);
            }
        }
        
        return $pages * $recipientCount * $costPerPage;
    }

    private function executeCurlRequest($url, $data, $method = 'POST') {
        $ch = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false, // For robustness
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ]
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Curl error: $error");
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($method === 'GET') {
            // For balance check, etc.
             if (isset($responseData['balance'])) return $responseData['balance']; // Special case for getSMSBalance reuse? 
             // Actually getSMSBalance logic above calls this? 
             // Wait, I implemented getSMSBalance separately above to handle GET.
             // But sendBulkSMS calls this.
             return $responseData;
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
             $errorMessage = isset($responseData['message']) ? $responseData['message'] : $response;
             throw new \Exception("SMS API Error ($httpCode): $errorMessage");
        }

        return $responseData;
    }
}