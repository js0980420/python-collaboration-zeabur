<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // Railway環境變數支持
        $this->host = $_ENV['MYSQLHOST'] ?? $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['MYSQLDATABASE'] ?? $_ENV['DB_NAME'] ?? 'python_teaching';
        $this->username = $_ENV['MYSQLUSER'] ?? $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['MYSQLPASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '';
        $this->port = $_ENV['MYSQLPORT'] ?? $_ENV['DB_PORT'] ?? '3306';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?> 