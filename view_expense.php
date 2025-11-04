<?php
require 'expense_monitoring.php'; 

// Fetch expenses
$stmt = $conn->prepare("SELECT * FROM expenses ORDER BY date_added DESC");
$stmt->execute();
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Expense Records</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">

<h2>Expense List</h2>

<table class="table table-bordered table-striped mt-3">
  <thead>
    <tr>
      <th>ID</th>
      <th>Category</th>
      <th>Amount</th>
      <th>Date</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>

  <?php if (!empty($expenses)): ?>
    <?php foreach ($expenses as $r): ?>
      <tr>
        <td><?= (int)$r['id']; ?></td>
        <td><?= htmlspecialchars($r['category']); ?></td>
        <td><?= number_format($r['amount'], 2); ?></td>
        <td><?= htmlspecialchars($r['date_added']); ?></td>
        <td>
          <a href="delete_expense.php?id=<?= (int)$r['id']; ?>"
             class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this record?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="5">No expenses recorded yet.</td></tr>
  <?php endif; ?>

  </tbody>
</table>

</body>
</html>
