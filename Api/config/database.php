<?php
class Database {
    private $username = "iee2021080";
    private $password = "1718Dim3##";
    private $db_name  = "ADISEDB";
    private $socket   = "/home/student/iee/2021/iee2021080/mysql/run/mysql.sock";
    public $conn;

    public function __construct() {
        $this->connect();
    }

    public function connect() {
        $this->conn = new mysqli(
            null,               // host null για socket
            $this->username,
            $this->password,
            $this->db_name,
            null,               // port null
            $this->socket
        );

        if ($this->conn->connect_error) {
            die("Σφάλμα σύνδεσης: " . $this->conn->connect_error);
        }

        // Ορίστε UTF-8
        $this->conn->set_charset("utf8");
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
