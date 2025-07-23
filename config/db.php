<?php
$host = 'localhost';
$db = 'reshu';
$user = 'root';
$pass = '';
$port = 3307; // ✅ add your custom MySQL port

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
