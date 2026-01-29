<?php
class Database {
  $socket = "/home/student/iee/2021/iee2021080/mysql/run/mysql.sock";
$conn = new mysqli(null, "iee2021080", "1718Dim3##", "ADISEDB", null, $socket);

    public $conn;
   
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