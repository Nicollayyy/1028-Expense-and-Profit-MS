<?php include 'fetch_expenses.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Expense Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="container py-4">
    <h2 class="text-center mb-4">Expense Monitoring System</h2>

    <!-- Add Expense Form -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-primary text-white">Add New Expense</div>
      <div class="card-body">
        <form action="save_expense.php" method="POST" class="row g-3">
          <div class="col-md-4">
            <select name="category" class="form-select" required>
              <option value="">-- Select Category --</option>
              <option>Staffs Payment</option>
              <option>Rental</option>
              <option>BIR Tax</option>
              <option>Water Bill</option>
              <option>Electricity Bill</option>
            </select>
          </div>

          <div class="col-md-4">
            <input type="number" name="amount" step="0.01" min="100" max="5000" class="form-control" placeholder="Amount (₱100 - ₱5000)" required>
          </div>

          <div class="col-md-3">
            <input type="date" name="date_added" class="form-control" required>
          </div>

          <div class="col-md-1">
            <button type="submit" class="btn btn-success w-100">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Expense List -->
    <div class="card shadow-sm">
      <div class="card-header bg-secondary text-white">Expense List</div>
      <div class="card-body">
        <table class="table table-bordered table-striped text-center">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Category</th>
              <th>Amount (₱)</th>
              <th>Date Added</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($expenses)): ?>
              <?php foreach ($expenses as $r): ?>
                <tr>
                  <td><?= $r['id']; ?></td>
                  <td><?= htmlspecialchars($r['category']); ?></td>
                  <td><?= number_format($r['amount'], 2); ?></td>
                  <td><?= htmlspecialchars($r['date_added']); ?></td>
                  <td>
                    <a href="delete_expense.php?id=<?= $r['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this record?')">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5">No expenses recorded yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</body>
</html>
