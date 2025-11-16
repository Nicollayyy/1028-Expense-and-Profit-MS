<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error'=>'unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$name = $_POST['name'] ?? '';
// simple validation
$name = basename($name);
$dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$path = $dir . DIRECTORY_SEPARATOR . $name;
if (is_file($path)) {
    unlink($path);
    echo json_encode(['ok' => true]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
}
exit();

?>
