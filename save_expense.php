<?php
require "expense_monitoring.php";

$category = $_POST['category'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);
$date = $_POST['date_added'] ?? '';

if ($category === '' || $date === '' || $amount <= 0) {
    die("Invalid input.");
}

$stmt = $conn->prepare("INSERT INTO expenses (category, amount, date_added) VALUES (?, ?, ?)");
$stmt->execute([$category, $amount, $date]);

header("Location: index.html");
exit;
