<?php
/**
 * Database Configuration
 * Centralized database connection using MySQLi
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private $host;
    private $port;
    private $username;
    private $password;
    private $database;

    private function __construct() {
        // Load from environment or use defaults
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
        $this->port = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306);
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
        $this->database = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'admin';
        
        $this->connect();
    }

    private function connect() {
        // Suppress errors, handle them manually
        @$this->connection = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );

        if ($this->connection->connect_error) {
            error_log('Database Connection Failed: ' . $this->connection->connect_error);
            die('Database Connection Failed. Please check system logs.');
        }

        // Set character set
        $this->connection->set_charset('utf8mb4');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function __destruct() {
        $this->close();
    }
}

// Global instance for backward compatibility
$db = Database::getInstance();
$conn = $db->getConnection();
?>