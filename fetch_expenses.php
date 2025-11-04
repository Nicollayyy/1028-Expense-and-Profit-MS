<?php
include 'expense_monitoring.php';

$expenses = [];
$result = $conn->query("SELECT * FROM expenses ORDER BY date_added DESC, id DESC");

if ($result && $result->num_rows > 0) {
    $expenses = $result->fetch_all(MYSQLI_ASSOC);
}
