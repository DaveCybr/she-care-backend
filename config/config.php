<?php
/**
 * Application Configuration
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/../.env');

// Get environment variable
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

return [
    // Application
    'app' => [
        'name' => env('APP_NAME', 'SheCare'),
        'env' => env('APP_ENV', 'development'),
        'url' => env('APP_URL', 'http://localhost/shecare-api'),
        'timezone' => 'Asia/Jakarta',
    ],
    
    // Database
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_NAME', 'shecare_db'),
        'username' => env('DB_USER', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
    
    // JWT
    'jwt' => [
        'secret' => env('JWT_SECRET', 'your_jwt_secret_key'),
        'expire' => (int) env('JWT_EXPIRE', 604800), // 7 days in seconds
    ],
    
    // Email
    'mail' => [
        'host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@shecare.com'),
        'from_name' => env('MAIL_FROM_NAME', 'SheCare'),
    ],
    
    // API Keys
    'api' => [
        'news_api_key' => env('NEWS_API_KEY', ''),
        'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],
    
    // CORS
    'cors' => [
        'origin' => env('CORS_ORIGIN', 'http://localhost:3000'),
    ],
    
    // Language
    'language' => [
        'default' => env('DEFAULT_LANG', 'id'),
        'supported' => ['id', 'en'],
    ],
    
    // Pagination
    'pagination' => [
        'default_limit' => 10,
        'max_limit' => 100,
    ],
];
?>