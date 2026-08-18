<?php
if (!class_exists('DBConnection')) {
    class DBConnection {
        protected $db;

        private $host;
        private $port;
        private $user;
        private $pass;
        private $dbname;

        public function __construct() {
            // Read configuration from environment variables or use fallback defaults
            $this->host   = $_ENV['DB_HOST']   ?? 'localhost';
            $this->port   = (int)($_ENV['DB_PORT']   ?? 3307);
            $this->user   = $_ENV['DB_USER']   ?? 'root';
            $this->pass   = $_ENV['DB_PASS']   ?? '';
            $this->dbname = $_ENV['DB_NAME']   ?? 'admin';

            // Enable MySQLi error reporting exceptions
            mysqli_report(MYSQLI_REPORT_OFF);

            $this->db = @new mysqli($this->host, $this->user, $this->pass, $this->dbname, $this->port);

            if ($this->db->connect_error) {
                error_log('Database Connection Failed: ' . $this->db->connect_error);
                die('Database Connection Failed. Please check system logs or contact the administrator.');
            }

            $this->db->set_charset("utf8mb4");
        }

        public function db_connect() {
            return $this->db;
        }

        public function __destruct() {
            if ($this->db && !$this->db->connect_error) {
                $this->db->close();
            }
        }
    }
}

if (!function_exists('format_num')) {
    /**
     * Helper function to format numbers cleanly
     */
    function format_num($number = '', $decimal = '') {
        if (is_numeric($number)) {
            $ex = explode(".", (string)$number);
            $dec_len = isset($ex[1]) ? strlen($ex[1]) : 0;
            if ($decimal !== '' && is_numeric($decimal)) {
                return number_format((float)$number, (int)$decimal);
            } else {
                return number_format((float)$number, $dec_len);
            }
        } else {
            return '0';
        }
    }
}

// Global database objects for legacy compatibility
if (!isset($db)) {
    $db = new DBConnection();
}
if (!isset($conn)) {
    $conn = $db->db_connect();
}