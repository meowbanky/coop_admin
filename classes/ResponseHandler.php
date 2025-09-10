<?php
/**
 * Response Handler
 * Handles standardized API responses and session messages
 */

class ResponseHandler {
    
    /**
     * Create a standardized success response
     */
    public function success($message, $data = null) {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }
    
    /**
     * Create a standardized error response
     */
    public function error($message, $code = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($code !== null) {
            $response['code'] = $code;
        }
        
        return $response;
    }
    
    /**
     * Set session message for display
     */
    public function setSessionMessage($message, $type = 'success') {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    
    /**
     * Get and clear session message
     */
    public function getSessionMessage() {
        if (isset($_SESSION['message'])) {
            $message = [
                'text' => $_SESSION['message'],
                'type' => $_SESSION['message_type'] ?? 'success'
            ];
            
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            
            return $message;
        }
        
        return null;
    }
    
    /**
     * Send JSON response and exit
     */
    public function sendJsonResponse($response, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    /**
     * Validate required fields
     */
    public function validateRequiredFields($data, $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            return $this->error('Missing required fields: ' . implode(', ', $missing));
        }
        
        return $this->success('Validation passed');
    }
}
?>
