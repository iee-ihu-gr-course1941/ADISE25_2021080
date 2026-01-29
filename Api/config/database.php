<?php
class Database {
    private $host = "localhost";
    private $db_name = "ADISEDB";
    private $username = "iee2021080";
    private $password = "1718Dim3##";
    $socket = '/home/staff/iee2021080/mysql/run/mysql.sock';
    public $conn;
    $mysqli = new mysqli(null, $user, $pass, $db, null, $socket);
    public function __construct() {
        $this->connect();
    }
    
    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
            return $this->conn;
        } catch(PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
}
?>