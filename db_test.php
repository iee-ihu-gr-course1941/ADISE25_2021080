<?php
$socket = "/home/staff/iee2021080/mysql/run/mysql.sock";
$conn = new mysqli(null, "iee2021080", "Ο_ΚΩΔΙΚΟΣ_ΣΟΥ", "ADISEDB", null, $socket);

if ($conn->connect_error) {
    die("Σφάλμα σύνδεσης: " . $conn->connect_error);
} else {
    echo "Σύνδεση στη βάση επιτυχής!";
}
?>
