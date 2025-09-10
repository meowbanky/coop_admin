<?php class EmailAccountManager {
    private $cpanel_username;
    private $cpanel_password;
    private $cpanel_domain;
    private $cpanel_url;
    private $security_token;

    public function __construct($cpanel_username, $cpanel_password, $domain = 'oouth.com') {
        $this->cpanel_url = 'https://server.weblagos.com';
        $this->cpanel_username = $cpanel_username;
        $this->cpanel_password = $cpanel_password;
        $this->cpanel_domain = $domain;
        $this->security_token = $this->getSecurityToken();
    }

    private function getSecurityToken() {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->cpanel_url . '/login/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($this->cpanel_username . ':' . $this->cpanel_password)
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 200) {
            throw new Exception('Failed to access cPanel login page');
        }

        if (preg_match('/name="security_token"\s+value="([^"]+)"/', $response, $matches)) {
            return $matches[1];
        }

        throw new Exception('Could not retrieve security token');
    }

    private function makeApiRequest($action, $params = []) {
        $curl = curl_init();

        $url = "{$this->cpanel_url}/execute/Email/{$action}?security_token=" . urlencode($this->security_token);
        $params['domain'] = $this->cpanel_domain;

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($this->cpanel_username . ':' . $this->cpanel_password),
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_VERBOSE => true
        ]);

        $verbose = fopen('php://temp', 'w+');
        curl_setopt($curl, CURLOPT_STDERR, $verbose);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            error_log("cURL Error: " . $error);
            error_log("Verbose Log: " . $verboseLog);
            curl_close($curl);
            throw new Exception("API Request Failed: " . $error);
        }

        curl_close($curl);

        return [
            'success' => $httpCode === 200,
            'data' => json_decode($response, true),
            'http_code' => $httpCode,
            'raw_response' => $response
        ];
    }

    public function listEmailAccounts() {
        return $this->makeApiRequest('list_pops');
    }

    public function createEmailAccount($email_user, $email_password, $quota = 250) {
        // Validate email username
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $email_user)) {
            throw new Exception('Invalid email username format');
        }

        // Validate password strength
        if (strlen($email_password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        return $this->makeApiRequest('add_pop', [
            'email' => $email_user,
            'password' => $email_password,
            'quota' => $quota
        ]);
    }

    public function deleteEmailAccount($email) {
        return $this->makeApiRequest('delete_pop', [
            'email' => $email
        ]);
    }

    public function changePassword($email, $new_password) {
        if (strlen($new_password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        return $this->makeApiRequest('passwd_pop', [
            'email' => $email,
            'password' => $new_password
        ]);
    }

    public function getQuota($email) {
        return $this->makeApiRequest('get_disk_usage', [
            'user' => $email
        ]);
    }
}
?>