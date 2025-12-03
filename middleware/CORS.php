<?php
// shecare-api/middleware/CORS.php

/**
 * CORS Middleware
 * Handles Cross-Origin Resource Sharing for React frontend
 */
class CORSMiddleware
{
    public static function handle()
    {
        // IMPORTANT: Set headers BEFORE any output
        if (headers_sent($file, $line)) {
            error_log("⚠️ Headers already sent in {$file} on line {$line}");
            return;
        }

        // Load config
        $config = require __DIR__ . '/../config/config.php';
        $configOrigin = $config['cors']['origin'] ?? 'http://localhost:8080';

        // Get origin from request
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Allowed origins (tambahkan variasi yang mungkin)
        $allowedOrigins = [
            $configOrigin, // Dari .env
            'http://localhost:8080',
            'http://localhost:3000',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:3000',
        ];

        // Remove duplicates
        $allowedOrigins = array_unique($allowedOrigins);

        // Determine which origin to allow
        if (!empty($requestOrigin) && in_array($requestOrigin, $allowedOrigins)) {
            // Use the exact origin from request
            header("Access-Control-Allow-Origin: {$requestOrigin}");
        } else {
            // Fallback to config origin
            header("Access-Control-Allow-Origin: {$configOrigin}");
        }

        // Set other CORS headers
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-Token");
        header("Access-Control-Expose-Headers: Authorization, X-Total-Count, Content-Length");
        header("Access-Control-Max-Age: 86400");

        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit(0);
        }
    }
}
