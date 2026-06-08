<?php
$host = 'localhost';
$user = 'root';
$password = '';
$db = "ordering_system";

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_error($conn));
}
?>