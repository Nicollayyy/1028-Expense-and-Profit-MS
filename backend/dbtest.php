<?php
// Simple MySQL database connection test

$host = "localhost";     // MySQL server
$user = "root";          // MySQL username
$pass = "";              // MySQL password
$db   = "expense_profit_monitoring"; // Database name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
echo "✅ Successfully connected to database: " . $db;

$conn->close();
?>
