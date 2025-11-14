<?php
$host = "localhost";
$user = "root"; // change if needed
$pass = "";     // change if needed
$dbname = "tea_cafe"; // your database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
