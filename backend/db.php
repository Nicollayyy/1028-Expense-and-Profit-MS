<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expense_profit_monitoring";

// Create connection
$conn = @new mysqli($servername, $username, $password, $dbname);

// When connection fails, avoid emitting HTML (die) so AJAX endpoints can return JSON diagnostics.
if ($conn->connect_error) {
    // expose a variable so callers can detect and include diagnostics
    $db_connect_error = $conn->connect_error;
    // set connection to null so callers know it's unavailable
    $conn = null;
    // also write a small log for server-side debugging
    @file_put_contents(__DIR__ . '/db_connection.log', date('c') . " - DB connect error: " . $db_connect_error . "\n", FILE_APPEND);
}
?>
