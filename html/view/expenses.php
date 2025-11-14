<?php
include "../expense_db.php";

// Get all expenses
$result = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1028 Tea and Cafe - View Expenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../.././css/dashboard.css">
    <link rel="stylesheet" href="../.././css/expenses.css">
</head>
<body>

<div class="header">
    <h1>1028 Tea and Cafe</h1>
    <div class="admin-profile" id="adminProfile">
        <form method="post" action="../logout.php">
            <button type="submit" class="dropdown-logout">Logout</button>
        </form>
    </div>
</div>

<div class="main-container">
    <div class="sidebar">
        <a class="nav-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dash Board</span></a>
        <a class="nav-item" href="../profit.php"><i class="fas fa-chart-line"></i><span>View Profit</span></a>
        <a class="nav-item active" href="expenses.php"><i class="fas fa-money-bill"></i><span>View Expenses</span></a>
        <a class="nav-item" href="products.php"><i class="fas fa-box"></i><span>Products</span></a>
    </div>

    <div class="content">
        <div class="content-title">View Expenses</div>

        <div class="button-container" style="margin-bottom:12px">
            <a href="add_expense.php" class="btn btn-primary">Add Expense</a>
        </div>

        <div class="table-container">
            <div class="table-label">Expenses</div>
            <table class="expense-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="amount-col">Amount</th>
                        <th class="action-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['expense_date'] ?></td>
                        <td><?= $row['category'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td class="amount-col">₱ <?= number_format($row['amount'], 2) ?></td>
                        <td class="action-col">
                            <a href="delete_expense.php?id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Delete this expense?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
