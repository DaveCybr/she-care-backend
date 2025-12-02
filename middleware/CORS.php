<?php
/**
 * CORS Middleware
 * Handles Cross-Origin Resource Sharing for React frontend
 */
class CORSMiddleware {
    
    public static function handle() {
        $config = require __DIR__ . '/../config/config.php';
        $allowedOrigin = $config['cors']['origin'];
        
        // Allow from any origin for development
        // For production, specify exact origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $origin = $_SERVER['HTTP_ORIGIN'];
            
            // Check if origin is allowed
            if ($origin === $allowedOrigin || $allowedOrigin === '*') {
                header("Access-Control-Allow-Origin: {$origin}");
            }
        } else {
            header("Access-Control-Allow-Origin: {$allowedOrigin}");
        }
        
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
        header("Access-Control-Max-Age: 3600");
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
?>