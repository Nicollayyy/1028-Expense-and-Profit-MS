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

$cols = [];
$res = $conn->query("SHOW COLUMNS FROM `expenses`");
if (!$res) {
    echo json_encode(['success' => false, 'error' => 'SHOW COLUMNS failed', 'db_error' => $conn->error]);
    exit;
}
while ($r = $res->fetch_assoc()) {
    $cols[] = $r;
}

$create = null;
$res2 = $conn->query("SHOW CREATE TABLE `expenses`");
if ($res2 && $row2 = $res2->fetch_assoc()) {
    // MySQL returns CREATE TABLE under different keys depending on client; grab the last column
    $create = end($row2);
}

echo json_encode(['success' => true, 'columns' => $cols, 'create' => $create]);
exit;
?>