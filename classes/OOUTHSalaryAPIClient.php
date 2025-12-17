<?php
/**
 * OOUTH Salary API Client
 * Handles authentication and data fetching from OOUTH Salary API
 */

require_once(__DIR__ . '/../config/api_config.php');

class OOUTHSalaryAPIClient {
    
    private $baseUrl;
    private $apiKey;
    private $apiSecret;
    private $jwtToken = null;
    private $tokenExpiry = null;
    private $timeout;
    private $debug;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->baseUrl = OOUTH_API_BASE_URL;
        $this->apiKey = OOUTH_API_KEY;
        $this->apiSecret = OOUTH_API_SECRET;
        $this->timeout = OOUTH_API_TIMEOUT;
        $this->debug = OOUTH_API_DEBUG;
    }
    
    /**
     * Authenticate and get JWT token
     * Following OOUTH Salary API Signature Generation Guide
     * 
     * Signature Formula:
     * Step 1: Signature String = API_KEY + TIMESTAMP (no spaces!)
     * Step 2: HMAC Signature = hash_hmac('sha256', Signature String, API_SECRET)
     * 
     * @return bool Success status
     */
    public function authenticate() {
        try {
            // Step 1: Get current Unix timestamp (seconds, not milliseconds)
            $timestamp = time();
            
            // Step 2: Build signature string (API Key + Timestamp, NO spaces)
            $signatureString = $this->apiKey . $timestamp;
            
            // Step 3: Calculate HMAC-SHA256 signature
            $signature = hash_hmac('sha256', $signatureString, $this->apiSecret);
            
            // if ($this->debug) {
                error_log("OOUTH API: ===== Authentication Attempt =====");
                error_log("OOUTH API: API Key: " . $this->apiKey);
                error_log("OOUTH API: Timestamp: " . $timestamp);
                error_log("OOUTH API: Signature String: " . $signatureString);
                error_log("OOUTH API: Generated Signature: " . $signature);
                error_log("OOUTH API: Secret Length: " . strlen($this->apiSecret) . " chars");
            // }
            
            // Step 4: Make request with required headers
            // FIXED: Pass the SAME timestamp and signature we just calculated
            $response = $this->request('POST', '/auth/token', null, [
                'X-Timestamp' => $timestamp,      // ✅ Use the timestamp we calculated
                'X-Signature' => $signature       // ✅ Use the signature we calculated
            ]);
            
            if ($response && isset($response['success']) && $response['success']) {
                $this->jwtToken = $response['data']['access_token'];
                $this->tokenExpiry = time() + ($response['data']['expires_in'] ?? 900);
                
                if ($this->debug) {
                    error_log("OOUTH API: ✅ Authentication successful!");
                    error_log("OOUTH API: Token expires in: " . ($response['data']['expires_in'] ?? 900) . " seconds");
                }
                
                return true;
            }
            
            if ($this->debug) {
                error_log("OOUTH API: ❌ Authentication failed");
                error_log("OOUTH API: Response: " . json_encode($response));
                
                // Provide troubleshooting hints
                if (isset($response['error']['code'])) {
                    $errorCode = $response['error']['code'];
                    error_log("OOUTH API: Error Code: " . $errorCode);
                    
                    switch ($errorCode) {
                        case 'INVALID_SIGNATURE':
                            error_log("OOUTH API: TIP: Check that API secret is correct (should be 64 chars)");
                            error_log("OOUTH API: TIP: Verify signature string has no spaces: apiKey + timestamp");
                            break;
                        case 'INVALID_API_KEY':
                            error_log("OOUTH API: TIP: Verify API key is correct");
                            break;
                        case 'INVALID_TIMESTAMP':
                            error_log("OOUTH API: TIP: Check system clock - should be ±5 minutes from server time");
                            error_log("OOUTH API: TIP: Verify timestamp and signature are generated together");
                            break;
                    }
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("OOUTH API: ❌ Authentication error - " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Check if token is valid and refresh if needed
     * @return bool
     */
    private function ensureAuthenticated() {
        // Check if token exists and is not expired (with 1 minute buffer)
        if ($this->jwtToken && $this->tokenExpiry && time() < ($this->tokenExpiry - 60)) {
            return true;
        }
        
        // Token expired or doesn't exist, re-authenticate
        return $this->authenticate();
    }
    
    /**
     * Get all payroll periods
     * @param int $page Page number
     * @param int $limit Records per page
     * @return array|null
     */
    public function getPeriods($page = 1, $limit = 100) {
        if (!$this->ensureAuthenticated()) {
            return null;
        }
        
        return $this->request('GET', "/payroll/periods?page={$page}&limit={$limit}");
    }
    
    /**
     * Get active payroll period
     * @return array|null
     */
    public function getActivePeriod() {
        if (!$this->ensureAuthenticated()) {
            return null;
        }
        
        return $this->request('GET', '/payroll/periods/active');
    }
    
    /**
     * Get specific period by ID
     * @param int $periodId
     * @return array|null
     */
    public function getPeriod($periodId) {
        if (!$this->ensureAuthenticated()) {
            return null;
        }
        
        return $this->request('GET', "/payroll/periods/{$periodId}");
    }
    
    /**
     * Get deduction data
     * @param int $deductionId
     * @param int|null $periodId
     * @return array|null
     */
    public function getDeductions($deductionId, $periodId = null) {
        if (!$this->ensureAuthenticated()) {
            return null;
        }
        
        $url = "/payroll/deductions/{$deductionId}";
        if ($periodId !== null) {
            $url .= "?period={$periodId}";
        }
        
        return $this->request('GET', $url);
    }
    
    /**
     * Get allowance data
     * @param int $allowanceId
     * @param int|null $periodId
     * @return array|null
     */
    public function getAllowances($allowanceId, $periodId = null) {
        if (!$this->ensureAuthenticated()) {
            return null;
        }
        
        $url = "/payroll/allowances/{$allowanceId}";
        if ($periodId !== null) {
            $url .= "?period={$periodId}";
        }
        
        return $this->request('GET', $url);
    }
    
    /**
     * Make HTTP request to API
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $body Request body
     * @param array $extraHeaders Extra headers
     * @return array|null
     */
    private function request($method, $endpoint, $body = null, $extraHeaders = []) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-Key: ' . $this->apiKey
        ];
        
        if ($this->jwtToken) {
            $headers[] = 'Authorization: Bearer ' . $this->jwtToken;
        }
        
        // Add extra headers (this is where X-Timestamp and X-Signature come in)
        foreach ($extraHeaders as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }
        
        if ($this->debug) {
            error_log("OOUTH API: Request URL - {$url}");
            error_log("OOUTH API: Request Headers - " . print_r($headers, true));
            if ($body) {
                error_log("OOUTH API: Request Body - " . json_encode($body));
            }
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  // ✅ Enable SSL verification (production)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);     // ✅ Verify hostname
        
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            if ($this->debug) {
                error_log("OOUTH API: cURL error - {$error}");
            }
            return null;
        }
        
        if ($this->debug) {
            error_log("OOUTH API: {$method} {$endpoint} - HTTP {$httpCode}");
            error_log("OOUTH API: Response - " . substr($response, 0, 500)); // Log first 500 chars
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode >= 400) {
            if ($this->debug) {
                error_log("OOUTH API: Error response - " . json_encode($data));
            }
        }
        
        return $data;
    }
    
    /**
     * Get resource data based on configured resource type
     * @param int|null $periodId
     * @return array|null
     */
    public function getResourceData($periodId = null) {
        $resourceType = OOUTH_RESOURCE_TYPE;
        $resourceId = OOUTH_RESOURCE_ID;
        
        if ($resourceType === 'deduction') {
            return $this->getDeductions($resourceId, $periodId);
        } elseif ($resourceType === 'allowance') {
            return $this->getAllowances($resourceId, $periodId);
        }
        
        return null;
    }
    
    /**
     * Get JWT token (for external use)
     * @return string|null
     */
    public function getToken() {
        if ($this->ensureAuthenticated()) {
            return $this->jwtToken;
        }
        return null;
    }
    
    /**
     * Get staff deduction data from remote API
     * @param int $staffId
     * @param int $periodId
     * @param int|null $deductionId Optional - if null, uses the deduction ID from API config
     * @return array|null
     */
    public function getStaffDeduction($staffId, $periodId, $deductionId = null) {
        if (!$this->ensureAuthenticated()) {
            return null;
        }
        
        // If deductionId is not provided, use the one from API config (the one the API key has access to)
        if ($deductionId === null) {
            $deductionId = defined('OOUTH_RESOURCE_ID') ? intval(OOUTH_RESOURCE_ID) : null;
        }
        
        // Build query parameters
        $params = [
            'staffid' => $staffId,
            'period' => $periodId
        ];
        
        // Only add deductionid if provided (some endpoints might not require it)
        if ($deductionId !== null) {
            $params['deductionid'] = $deductionId;
        }
        
        $endpoint = "/payroll/staff-deduction?" . http_build_query($params);
        
        return $this->request('GET', $endpoint);
    }
    
    /**
     * Get last error message
     * @return string|null
     */
    public function getLastError() {
        // You can implement error tracking here
        return null;
    }
}