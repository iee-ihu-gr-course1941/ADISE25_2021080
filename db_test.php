<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$socket = "/home/staff/iee2021080/mysql/run/mysql.sock";
$conn = new mysqli(null, "iee2021080", "1718Dim3##", "ADISEDB", null, $socket);

if ($conn->connect_error) {
    die("Σφάλμα σύνδεσης: " . $conn->connect_error);
} else {
    echo "Σύνδεση στη βάση επιτυχής!";
}
?>
