<?php
// get_dashboard_stats.php
// Returns JSON with dashboard summary numbers (today's sales, monthly expenses)
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($conn)) {
    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
    exit;
}

function tableExists($conn, $table) {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
}

// initialize
$todaySales = 0.0;
$todayExpenses = 0.0;
$monthlySales = 0.0;
$monthlyExpenses = 0.0;

// Try common sales tables and columns
if (tableExists($conn, 'sales')) {
    // sales table with `amount` and `date`
    $res = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM sales WHERE DATE(`date`) = CURDATE()");
    if ($res) {
        $r = $res->fetch_assoc(); $todaySales = (float)($r['total'] ?? 0);
    }
} elseif (tableExists($conn, 'orders')) {
    // orders table with `total` and `created_at` or `date`
    $res = $conn->query("SELECT COALESCE(SUM(COALESCE(total, amount, 0)),0) AS total FROM orders WHERE DATE(COALESCE(created_at, `date`)) = CURDATE()");
    if ($res) {
        $r = $res->fetch_assoc(); $todaySales = (float)($r['total'] ?? 0);
    }
} else {
    // No known sales table — attempt to infer from products_sales or similar
    if (tableExists($conn, 'product_sales')) {
        $res = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM product_sales WHERE DATE(`date`) = CURDATE()");
        if ($res) { $r = $res->fetch_assoc(); $todaySales = (float)($r['total'] ?? 0); }
    }
}

// Monthly expenses (look for `expenses` table)
if (tableExists($conn, 'expenses')) {
    // today's expenses
    $res = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE DATE(`date`) = CURDATE()");
    if ($res) { $r = $res->fetch_assoc(); $todayExpenses = (float)($r['total'] ?? 0); }

    // monthly expenses
    $res = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE MONTH(`date`) = MONTH(CURDATE()) AND YEAR(`date`) = YEAR(CURDATE())");
    if ($res) { $r = $res->fetch_assoc(); $monthlyExpenses = (float)($r['total'] ?? 0); }
} else {
    // fallback: sum negative entries in transactions or bills if present
    if (tableExists($conn, 'transactions')) {
        // today's expenses from transactions
        $res = $conn->query("SELECT COALESCE(SUM(CASE WHEN amount < 0 THEN -amount WHEN type='expense' THEN amount ELSE 0 END),0) AS total FROM transactions WHERE DATE(`date`) = CURDATE()");
        if ($res) { $r = $res->fetch_assoc(); $todayExpenses = (float)($r['total'] ?? 0); }

        // monthly expenses from transactions
        $res = $conn->query("SELECT COALESCE(SUM(CASE WHEN amount < 0 THEN -amount WHEN type='expense' THEN amount ELSE 0 END),0) AS total FROM transactions WHERE MONTH(`date`) = MONTH(CURDATE()) AND YEAR(`date`) = YEAR(CURDATE())");
        if ($res) { $r = $res->fetch_assoc(); $monthlyExpenses = (float)($r['total'] ?? 0); }
    }
}

// Monthly sales (sum similar to todaySales but for current month)
if (tableExists($conn, 'sales')) {
    $res = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM sales WHERE MONTH(`date`) = MONTH(CURDATE()) AND YEAR(`date`) = YEAR(CURDATE())");
    if ($res) { $r = $res->fetch_assoc(); $monthlySales = (float)($r['total'] ?? 0); }
} elseif (tableExists($conn, 'orders')) {
    $res = $conn->query("SELECT COALESCE(SUM(COALESCE(total, amount, 0)),0) AS total FROM orders WHERE MONTH(COALESCE(created_at, `date`)) = MONTH(CURDATE()) AND YEAR(COALESCE(created_at, `date`)) = YEAR(CURDATE())");
    if ($res) { $r = $res->fetch_assoc(); $monthlySales = (float)($r['total'] ?? 0); }
} elseif (tableExists($conn, 'product_sales')) {
    $res = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM product_sales WHERE MONTH(`date`) = MONTH(CURDATE()) AND YEAR(`date`) = YEAR(CURDATE())");
    if ($res) { $r = $res->fetch_assoc(); $monthlySales = (float)($r['total'] ?? 0); }
}

// Compute profits
$todayProfit = $todaySales - $todayExpenses;
$monthlyProfit = $monthlySales - $monthlyExpenses;

echo json_encode([
    'success' => true,
    'data' => [
        'today_sales' => $todaySales,
        'today_expenses' => $todayExpenses,
        'today_profit' => $todayProfit,
        'monthly_sales' => $monthlySales,
        'monthly_expenses' => $monthlyExpenses,
        'monthly_profit' => $monthlyProfit
    ]
]);

?>
