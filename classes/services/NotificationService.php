<?php
namespace App\Services;

class NotificationService {
    private $db;
    private $oneSignalConfig;
    private $smsConfig;

    public function __construct($db) {
        $this->db = $db;
        $this->oneSignalConfig = [
            'appId' => 'aeef154f-9807-4dff-b7a6-d215ac0c1281',
            'apiKey' => 'os_v2_app_v3xrkt4ya5g77n5g2ik2ydasqhzzj6vsgq2edvnl26mbtdef3vzdr7xt6rk7gxrkd2sdbozy37ssbciza5xxwuhcpckzjhhogtmeiza'
        ];
        $this->smsConfig = [
            'sender' => 'OOUTHCOOP',
            'apiKey' => 'TLg1GY6Gwcnii12H3EY0LWg4tCFQgcsOg4NVLpdQqm413h32QFJR0VxN4q08jT',
            'endpoint' => 'https://v3.api.termii.com/api/sms/send'

        ];
    }

    public function sendTransactionNotification($memberId, $periodId) {
        try {
            // Get transaction details
            $transactionData = $this->getTransactionDetails($memberId, $periodId);
//            error_log('transatino data '.json_encode($transactionData));
            if (!$transactionData) {
                throw new \Exception("No transaction data found");
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
            $this->logNotification($memberId, $message);

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
        WHERE tblemployees.CoopID = '" . mysqli_real_escape_string($this->db, $memberId) . "' 
        AND tbl_mastertransact.TransactionPeriod = " . (int)$periodId . "
        GROUP BY tbl_mastertransact.COOPID, tbpayrollperiods.id, tblemployees.LastName, tblemployees.FirstName, tblemployees.MiddleName, tblemployees.MobileNumber
        ORDER BY tbpayrollperiods.id DESC LIMIT 1";

        $result = mysqli_query($this->db, $query);
        if (!$result) {
            throw new \Exception("Database query failed: " . mysqli_error($this->db));
        }

        return mysqli_fetch_assoc($result);
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
            throw new \Exception("Phone number is required");
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

    private function logNotification($memberId, $message) {
        $memberId = mysqli_real_escape_string($this->db, $memberId);
        $message = mysqli_real_escape_string($this->db, $message);

        $query = "INSERT INTO notifications 
                  (coop_id, message, created_at, status) 
                  VALUES 
                  ('$memberId', '$message', NOW(), 'unread')";

        return mysqli_query($this->db, $query);
    }
}