<?php include "../expense_db.php"; ?>
<!DOCTYPE html>
<html>
<head><title>Add Expense</title></head>
<body>
<form action="save_expense.php" method="POST">
    <label>Date:</label><input type="date" name="expense_date" required><br>
    <label>Category:</label><input type="text" name="category" required><br>
    <label>Description:</label><textarea name="description" required></textarea><br>
    <label>Amount:</label><input type="number" step="0.01" name="amount" required><br>
    <button type="submit">Save</button>
</form>
</body>
</html>
