<?php
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../config/config.php';

$config = require __DIR__ . '/../config/config.php';

// Token dari frontend
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MywiaWF0IjoxNzY0NzMzMDIzLCJleHAiOjE3NjUzMzc4MjN9.3DYqq2Il71dD8hniYHSw4wftXqNprgFCJZymuPVtoFc';

echo "=== TOKEN VERIFICATION TEST ===\n\n";

echo "Token: " . $token . "\n";
echo "Secret: " . $config['jwt']['secret'] . "\n";
echo "Secret Length: " . strlen($config['jwt']['secret']) . "\n\n";

try {
    $payload = JWT::decode($token, $config['jwt']['secret']);

    echo "✅ SUCCESS: Token decoded\n";
    echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
    echo "User ID: " . $payload['id'] . "\n";
    echo "Issued At: " . date('Y-m-d H:i:s', $payload['iat']) . "\n";
    echo "Expires At: " . date('Y-m-d H:i:s', $payload['exp']) . "\n";
    echo "Current Time: " . date('Y-m-d H:i:s', time()) . "\n";
    echo "Is Expired? " . ($payload['exp'] < time() ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
