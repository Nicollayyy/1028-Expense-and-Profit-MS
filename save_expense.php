<?php
include 'expense_monitoring.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category']);
    $amount = floatval($_POST['amount']);
    $date_added = trim($_POST['date_added']);

    if ($amount < 100 || $amount > 5000) {
        die("Amount must be between ₱100 and ₱5000.");
    }

    $stmt = $conn->prepare("INSERT INTO expenses (category, amount, date_added) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $category, $amount, $date_added);
    $stmt->execute();
}

header("Location: index.php");
exit();
