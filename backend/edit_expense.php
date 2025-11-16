<?php
include "../database/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $expense_date = $_POST['expense_date'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("UPDATE expenses SET expense_date = ?, category = ?, description = ?, amount = ? WHERE id = ?");
    $stmt->bind_param("sssdi", $expense_date, $category, $description, $amount, $id);

    if ($stmt->execute()) {
        header("Location: ../view/view_expense.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
