<?php
$host = "localhost";
$port = 3306;
$username = "root";
$password = "";
$dbname = "drivehub";

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>