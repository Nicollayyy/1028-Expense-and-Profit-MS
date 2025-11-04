<?php
require "expense_monitoring.php";

$id = intval($_GET['id']);
$stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.html");
exit;
