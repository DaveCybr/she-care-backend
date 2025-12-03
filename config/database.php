<?php

/**
 * Database Connection Class
 */
class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        // Use require instead of require_once to get fresh config
        $config = require __DIR__ . '/config.php';

        // Validate config structure
        if (!is_array($config) || !isset($config['database'])) {
            error_log("Config file did not return valid configuration array");
            die(json_encode([
                'success' => false,
                'message' => 'Configuration file error. Please check config.php'
            ]));
        }

        $db = $config['database'];

        // Validate all required database config keys
        $required = ['host', 'port', 'database', 'username', 'password'];
        foreach ($required as $key) {
            if (!isset($db[$key])) {
                error_log("Missing required database config: {$key}");
                die(json_encode([
                    'success' => false,
                    'message' => "Database configuration missing: {$key}"
                ]));
            }
        }

        try {
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4";

            $this->connection = new PDO(
                $dsn,
                $db['username'],
                $db['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please check configuration.'
            ]));
        }
    }

    /**
     * Get Database instance (Singleton pattern)
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
