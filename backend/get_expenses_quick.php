<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$response = ['success' => false];
if (!isset($conn) || !$conn) {
    $response['error'] = 'DB connection not available';
    if (isset($db_connect_error)) $response['db_connect_error'] = $db_connect_error;
    echo json_encode($response);
    exit;
}

$sql = "SELECT * FROM `expenses` ORDER BY `id` DESC LIMIT 500";
$res = $conn->query($sql);
if (!$res) {
    echo json_encode(['success' => false, 'error' => 'Query failed', 'db_error' => $conn->error, 'sql' => $sql]);
    exit;
}
$rows = [];
while ($r = $res->fetch_assoc()) {
    if (isset($r['amount'])) $r['amount'] = (float)$r['amount'];
    $rows[] = $r;
}

echo json_encode(['success' => true, 'count' => count($rows), 'data' => $rows]);
exit;
?>