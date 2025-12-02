<?php
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/I18n.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Authentication Middleware
 */
class AuthMiddleware {
    
    /**
     * Check if user is authenticated
     */
    public static function authenticate() {
        $headers = getallheaders();
        $token = null;
        
        // Get token from Authorization header
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if (!$token) {
            Response::unauthorized(I18n::t('auth.unauthorized'));
        }
        
        try {
            $config = require __DIR__ . '/../config/config.php';
            $payload = JWT::decode($token, $config['jwt']['secret']);
            
            // Get user from database
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$payload['id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Response::unauthorized(I18n::t('auth.user_not_found'));
            }
            
            return $user;
            
        } catch (Exception $e) {
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
    public static function requireAdmin() {
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
    public static function user() {
        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
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
?>