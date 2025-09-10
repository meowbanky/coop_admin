<?php
/**
 * ResponseHandler Class
 * Handles all response formatting and output
 */

class ResponseHandler {
    
    /**
     * Handle API responses
     */
    public function handleResponse($response) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->sendJsonResponse($response);
        } else {
            $this->handleFormResponse($response);
        }
    }
    
    /**
     * Send JSON response for AJAX requests
     */
    private function sendJsonResponse($response) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Handle form submission responses
     */
    private function handleFormResponse($response) {
        if ($response['success']) {
            $_SESSION['success_message'] = $response['message'];
        } else {
            $_SESSION['error_message'] = $response['message'];
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    /**
     * Get and clear session messages
     */
    public function getSessionMessage() {
        $message = null;
        
        if (isset($_SESSION['success_message'])) {
            $message = [
                'type' => 'success',
                'text' => $_SESSION['success_message']
            ];
            unset($_SESSION['success_message']);
        } elseif (isset($_SESSION['error_message'])) {
            $message = [
                'type' => 'error',
                'text' => $_SESSION['error_message']
            ];
            unset($_SESSION['error_message']);
        }
        
        return $message;
    }
    
    /**
     * Format error messages
     */
    public function formatErrors($errors) {
        if (is_array($errors)) {
            return implode('<br>', $errors);
        }
        return $errors;
    }
    
    /**
     * Create standardized response
     */
    public function createResponse($success, $message, $data = null) {
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }
}
?>
