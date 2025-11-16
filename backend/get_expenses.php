<?php
// Return JSON only and avoid stray PHP notices breaking the response for AJAX clients.
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/db.php';

$debug = isset($_GET['debug']) && ($_GET['debug'] == '1' || $_GET['debug'] === 'true');
$response = ['success' => false];

// Ensure DB connection is available
if (!isset($conn) || !$conn) {
    $response['error'] = 'Database connection not available';
    if ($debug && isset($db_connect_error)) $response['db_connect_error'] = $db_connect_error;
    echo json_encode($response);
    exit;
}

// Make sure the expenses table exists before querying
$tblCheck = $conn->query("SHOW TABLES LIKE 'expenses'");
if (!$tblCheck || $tblCheck->num_rows === 0) {
    $response['error'] = "Table 'expenses' not found";
    // include some helpful diagnostics when possible
    if ($conn->error) $response['db_error'] = $conn->error;
    if ($debug) {
        $response['php_sapi'] = php_sapi_name();
        $response['script'] = __FILE__;
    }
    echo json_encode($response);
    exit;
}

// Inspect columns and build a safe SELECT
$colsRes = $conn->query("SHOW COLUMNS FROM `expenses`");
$hasId = false;
$hasDate = false;
$hasExpenseDate = false;
$cols = [];
if ($colsRes) {
    while ($c = $colsRes->fetch_assoc()) {
        if (isset($c['Field'])) {
            $cols[] = $c['Field'];
            if ($c['Field'] === 'id') $hasId = true;
            if ($c['Field'] === 'date') $hasDate = true;
            if ($c['Field'] === 'expense_date') $hasExpenseDate = true;
        }
    }
}

$idSelect = $hasId ? '`id`' : 'NULL AS id';
if ($hasDate && $hasExpenseDate) $dateExpr = 'COALESCE(`date`, `expense_date`) AS date';
elseif ($hasDate) $dateExpr = '`date` AS date';
elseif ($hasExpenseDate) $dateExpr = '`expense_date` AS date';
else $dateExpr = 'NULL AS date';

$orderBy = ($hasDate || $hasExpenseDate) ? 'COALESCE(`date`, `expense_date`)' : ($hasId ? '`id`' : 'NULL');

$sql = "SELECT {$idSelect}, {$dateExpr}, `category`, `description`, `amount` FROM `expenses` ORDER BY {$orderBy} DESC";
$result = $conn->query($sql);

if ($result === false) {
    $response['error'] = 'Query failed';
    $response['db_error'] = $conn->error;
    if ($debug) $response['sql'] = $sql;
    echo json_encode($response);
    exit;
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    // normalize types
    if (isset($r['amount'])) {
        $r['amount'] = (float) $r['amount'];
    }
    $rows[] = $r;
}

$response['success'] = true;
$response['data'] = $rows;
if ($debug) {
    $response['debug'] = ['columns' => $cols, 'sql' => $sql];
}
echo json_encode($response);
exit;
?>
