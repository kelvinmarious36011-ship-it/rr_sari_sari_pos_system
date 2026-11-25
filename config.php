<?php
$host = 'localhost';
$user = 'root';
$pass = '';          // Default XAMPP password is empty
$db   = 'rr_sari_sari_pos';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>