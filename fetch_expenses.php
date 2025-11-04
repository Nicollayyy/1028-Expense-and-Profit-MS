<?php
include 'expense_monitoring.php';

$stmt = $conn->prepare("SELECT * FROM expenses ORDER BY id DESC");
$stmt->execute();
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($expenses);
