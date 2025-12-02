<?php
/**
 * SheCare API - Entry Point
 * 
 * Main entry file for all API requests
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Enable CORS
require_once __DIR__ . '/../middleware/CORS.php';
CORSMiddleware::handle();

// Initialize I18n
require_once __DIR__ . '/../utils/I18n.php';
$lang = $_GET['lang'] ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'id';
$lang = substr($lang, 0, 2); // Get first 2 chars (id, en)
I18n::init($lang);

// Load router
$router = require_once __DIR__ . '/../routes/api.php';

// Get request method and URI
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Handle the request
try {
    $router->dispatch($requestMethod, $requestUri);
} catch (Exception $e) {
    error_log('Application error: ' . $e->getMessage());
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>