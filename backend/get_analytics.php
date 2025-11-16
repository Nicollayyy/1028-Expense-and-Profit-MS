<?php
// get_analytics.php
// Returns time-series data for charts: daily (7 days), weekly (8 weeks), monthly (12 months), yearly (5 years)
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($conn)) {
    echo json_encode(['success'=>false,'error'=>'Database connection not available']);
    exit;
}

function tableExists($conn, $table) {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
}

// Choose a sales source table and the SQL expressions for date and amount
$salesSource = null;
$salesDateExpr = 'DATE(`date`)';
$salesAmountExpr = 'amount';
if (tableExists($conn,'sales')) {
    $salesSource = 'sales';
    $salesDateField = '`date`';
    $salesAmountExpr = 'amount';
} elseif (tableExists($conn,'orders')) {
    $salesSource = 'orders';
    $salesDateField = 'COALESCE(created_at, `date`)';
    $salesAmountExpr = 'COALESCE(total, amount, 0)';
} elseif (tableExists($conn,'product_sales')) {
    $salesSource = 'product_sales';
    $salesDateField = '`date`';
    $salesAmountExpr = 'amount';
}

// Choose an expenses source
$expensesSource = null;
if (tableExists($conn,'expenses')) {
    $expensesSource = 'expenses';
    $expensesDateField = '`date`';
    $expensesAmountExpr = 'amount';
} elseif (tableExists($conn,'transactions')) {
    $expensesSource = 'transactions';
    $expensesDateField = '`date`';
    // assume negative amounts are expenses or a type column
    $expensesAmountExpr = "(CASE WHEN amount < 0 THEN -amount WHEN type='expense' THEN amount ELSE 0 END)";
}

// Helper to fill date buckets
function build_date_range($start, $end, $step = '+1 day', $format='Y-m-d'){
    $dates = [];
    $current = strtotime($start);
    $endTs = strtotime($end);
    while ($current <= $endTs) {
        $dates[] = date($format, $current);
        $current = strtotime($step, $current);
    }
    return $dates;
}

$now = new DateTime();

// Daily - last 7 days
$dailyLabels = [];
$dailyData = [];
$start = (new DateTime('today'))->modify('-6 days')->format('Y-m-d');
$end = (new DateTime('today'))->format('Y-m-d');
$dates = build_date_range($start, $end, '+1 day', 'Y-m-d');
foreach ($dates as $d) { $dailyLabels[] = $d; $dailyData[$d] = 0; }
if ($salesSource) {
    $sql = "SELECT DATE({$salesDateField}) AS d, COALESCE(SUM({$salesAmountExpr}),0) AS total FROM {$salesSource} WHERE DATE({$salesDateField}) BETWEEN '{$start}' AND '{$end}' GROUP BY DATE({$salesDateField})";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $k = $r['d']; if (isset($dailyData[$k])) $dailyData[$k] = (float)$r['total'];
        }
    }
}

// Weekly - last 8 weeks (label by week start date)
$weeklyLabels = [];
$weeklyData = [];
$weeks = [];
for ($i=7;$i>=0;$i--) {
    $startW = (new DateTime())->setISODate((int)date('o'),(int)date('W'))->modify("-{$i} weeks")->modify('monday this week')->format('Y-m-d');
    $weeks[] = $startW;
    $weeklyLabels[] = $startW;
    $weeklyData[$startW] = 0;
}
if ($salesSource) {
    // use WEEK() grouping as fallback
    $sql = "SELECT YEAR({$salesDateField}) AS y, WEEK({$salesDateField},1) AS w, COALESCE(SUM({$salesAmountExpr}),0) AS total FROM {$salesSource} WHERE {$salesDateField} >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK) GROUP BY y,w";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            // map to monday date label
            $label = date('Y-m-d', strtotime($r['y'] . 'W' . str_pad($r['w'],2,'0',STR_PAD_LEFT)));
            // fallback: pick closest week start from $weeks
            foreach ($weeks as $wk) {
                if (strpos($label, substr($wk,0,10)) === 0 || abs(strtotime($label)-strtotime($wk)) < 7*24*3600) {
                    $weeklyData[$wk] += (float)$r['total'];
                    break;
                }
            }
        }
    }
}

// Monthly - last 12 months
$monthlyLabels = [];
$monthlyData = [];
for ($i=11;$i>=0;$i--) {
    $m = (new DateTime('first day of this month'))->modify("-{$i} months")->format('Y-m');
    $monthlyLabels[] = $m;
    $monthlyData[$m] = 0;
}
if ($salesSource) {
    $sql = "SELECT DATE_FORMAT({$salesDateField}, '%Y-%m') AS m, COALESCE(SUM({$salesAmountExpr}),0) AS total FROM {$salesSource} WHERE {$salesDateField} >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY m";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $k = $r['m']; if (isset($monthlyData[$k])) $monthlyData[$k] = (float)$r['total'];
        }
    }
}

// Yearly - last 5 years
$yearlyLabels = [];
$yearlyData = [];
for ($i=4;$i>=0;$i--) {
    $y = (int)date('Y') - $i;
    $yearlyLabels[] = (string)$y;
    $yearlyData[(string)$y] = 0;
}
if ($salesSource) {
    $sql = "SELECT YEAR({$salesDateField}) AS y, COALESCE(SUM({$salesAmountExpr}),0) AS total FROM {$salesSource} WHERE {$salesDateField} >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR) GROUP BY y";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $k = (string)$r['y']; if (isset($yearlyData[$k])) $yearlyData[$k] = (float)$r['total'];
        }
    }
}

// Expenses monthly (12 months) - for the expensesMonthly chart
$expensesMonthlyLabels = $monthlyLabels;
$expensesMonthlyData = array_fill(0, count($expensesMonthlyLabels), 0);
if ($expensesSource) {
    $sql = "SELECT DATE_FORMAT({$expensesDateField}, '%Y-%m') AS m, COALESCE(SUM({$expensesAmountExpr}),0) AS total FROM {$expensesSource} WHERE {$expensesDateField} >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY m";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $k = $r['m'];
            $idx = array_search($k, $expensesMonthlyLabels);
            if ($idx !== false) $expensesMonthlyData[$idx] = (float)$r['total'];
        }
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'daily' => ['labels' => $dailyLabels, 'data' => array_values($dailyData)],
        'weekly' => ['labels' => $weeklyLabels, 'data' => array_values($weeklyData)],
        'monthly' => ['labels' => $monthlyLabels, 'data' => array_values($monthlyData)],
        'yearly' => ['labels' => $yearlyLabels, 'data' => array_values($yearlyData)],
        'expenses_monthly' => ['labels' => $expensesMonthlyLabels, 'data' => $expensesMonthlyData]
    ]
]);

?>
