<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error'=>'unauthorized']);
    exit();
}

$dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$files = [];
if (is_dir($dir)) {
    $dh = opendir($dir);
    while (($f = readdir($dh)) !== false) {
        if ($f === '.' || $f === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $f;
        if (is_file($path)) {
            $files[] = [
                'name' => $f,
                'size' => filesize($path),
                'url'  => 'uploads/' . rawurlencode($f)
            ];
        }
    }
    closedir($dh);
}

header('Content-Type: application/json');
echo json_encode($files);
exit();

?>
