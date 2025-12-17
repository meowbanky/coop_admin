<?php
class JWTHandler {
    private $secret;
    private $expiry;

    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $this->secret = $config['jwt']['secret'];
        $this->expiry = $config['jwt']['expiry'];
    }

    public function generateToken($userId) {
        $payload = [
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + $this->expiry
        ];

        return $this->encodeToken($payload);
    }

    public function validateToken($token) {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return false;
            }

            list($header, $payload, $signature) = $parts;

            // Verify signature
            $valid = hash_equals(
                $this->base64UrlEncode(
                    hash_hmac('sha256', $header . "." . $payload, $this->secret, true)
                ),
                $signature
            );

            if (!$valid) {
                return false;
            }

            // Decode the payload
            $payload = json_decode($this->base64UrlDecode($payload), true);

            // Check expiration
            if (!$payload || !isset($payload['exp'])) {
                return false;
            }

            if ($payload['exp'] < time()) {
                return false; // Token is expired
            }

            return $payload; // Return the decoded payload

        } catch (Exception $e) {
            return false;
        }
    }

    public function getPayload($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            return json_decode($this->base64UrlDecode($parts[1]), true);
        } catch (Exception $e) {
            return null;
        }
    }

    private function encodeToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256',
            $base64Header . "." . $base64Payload,
            $this->secret,
            true
        );

        $base64Signature = $this->base64UrlEncode($signature);

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}