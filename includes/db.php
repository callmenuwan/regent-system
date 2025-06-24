<?php
$host = "localhost:3307";
$user = "root";
$password = ""; // your MySQL password (e.g. 'root' or '')
$dbname = "regent_db"; // database name in phpMyAdmin

$conn = mysqli_connect($host, $user, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
