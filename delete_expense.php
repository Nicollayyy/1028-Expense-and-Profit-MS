<?php
include 'expense_monitoring.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM expenses WHERE id = $id");
}

header("Location: index.php");
exit();
