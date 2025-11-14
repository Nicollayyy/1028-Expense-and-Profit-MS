<?php
include "../expense_db.php";

$date = $_POST['expense_date'];
$category = $_POST['category'];
$description = $_POST['description'];
$amount = $_POST['amount'];

$stmt = $conn->prepare("INSERT INTO expenses (expense_date, category, description, amount) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssd", $date, $category, $description, $amount);
$stmt->execute();

header("Location: expenses.php");
exit;
