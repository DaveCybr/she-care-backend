<?php
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/I18n.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Authentication Middleware
 */
class AuthMiddleware
{
    /**
     * Check if user is authenticated
     */
    public static function authenticate()
    {
        error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        error_log("[AuthMiddleware] Starting authentication");

        $headers = getallheaders();
        error_log("[AuthMiddleware] All headers: " . json_encode($headers));

        $token = null;

        // ✅ FIX: Check for authorization header (case-insensitive)
        $authHeader = null;

        // Try different case variations
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                error_log("[AuthMiddleware] Authorization header found with key: $key");
                break;
            }
        }

        if ($authHeader) {
            error_log("[AuthMiddleware] Authorization header value: " . $authHeader);

            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
                error_log("[AuthMiddleware] Token extracted: " . substr($token, 0, 50) . "...");
            } else {
                error_log("[AuthMiddleware] ERROR: Authorization header doesn't match Bearer pattern");
            }
        } else {
            error_log("[AuthMiddleware] ERROR: No Authorization header found");
            error_log("[AuthMiddleware] Available headers: " . implode(", ", array_keys($headers)));
        }

        if (!$token) {
            error_log("[AuthMiddleware] ERROR: No token available");
            Response::unauthorized(I18n::t('auth.unauthorized'));
        }

        try {
            error_log("[AuthMiddleware] Attempting to decode token...");

            $config = require __DIR__ . '/../config/config.php';
            $secret = $config['jwt']['secret'];

            error_log("[AuthMiddleware] JWT Secret: " . substr($secret, 0, 20) . "...");
            error_log("[AuthMiddleware] JWT Secret Length: " . strlen($secret));

            $payload = JWT::decode($token, $secret);

            error_log("[AuthMiddleware] Token decoded successfully");
            error_log("[AuthMiddleware] Payload: " . json_encode($payload));

            // Get user from database
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$payload['id']]);
            $user = $stmt->fetch();

            if (!$user) {
                error_log("[AuthMiddleware] ERROR: User not found with ID: " . $payload['id']);
                Response::unauthorized(I18n::t('auth.user_not_found'));
            }

            error_log("[AuthMiddleware] User found: " . json_encode($user));
            error_log("[AuthMiddleware] Authentication successful ✅");
            error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            return $user;
        } catch (Exception $e) {
            error_log("[AuthMiddleware] ERROR: Exception caught");
            error_log("[AuthMiddleware] Exception message: " . $e->getMessage());
            error_log("[AuthMiddleware] Exception trace: " . $e->getTraceAsString());
            error_log("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            if (strpos($e->getMessage(), 'expired') !== false) {
                Response::unauthorized(I18n::t('auth.token_expired'));
            } else {
                Response::unauthorized(I18n::t('auth.token_invalid'));
            }
        }
    }

    /**
     * Check if user has admin role
     */
    public static function requireAdmin()
    {
        $user = self::authenticate();

        if ($user['role'] !== 'admin') {
            Response::forbidden(I18n::t('auth.forbidden'));
        }

        return $user;
    }

    /**
     * Get current authenticated user (optional)
     * Returns null if not authenticated
     */
    public static function user()
    {
        $headers = getallheaders();
        $token = null;

        // Case-insensitive header check
        $authHeader = null;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        if (!$token) {
            return null;
        }

        try {
            $config = require __DIR__ . '/../config/config.php';
            $payload = JWT::decode($token, $config['jwt']['secret']);

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$payload['id']]);

            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
}
