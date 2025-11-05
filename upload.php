<?php
session_start();
// require login
if (!isset($_SESSION['admin_id'])) {
    header('Location: html/login.html');
    exit();
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (!isset($_FILES['file'])) {
    header('Location: html/file_manager.html');
    exit();
}

$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) {
    // simple error handling
    header('Location: html/file_manager.html');
    exit();
}

$name = basename($f['name']);
// sanitize filename
$name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

// allowed extensions and simple mime checks
$allowedExt = ['pdf','doc','docx','xls','xlsx','csv','png','jpg','jpeg','gif','bmp','webp'];
if (!in_array($ext, $allowedExt, true)) {
    // reject
    header('Location: html/file_manager.html?error=type');
    exit();
}

// optional: additional mime check
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $f['tmp_name']);
finfo_close($finfo);
// allow common mimetypes for our extensions
$mimeAllowMap = [
  'pdf' => ['application/pdf'],
  'doc' => ['application/msword','application/octet-stream'],
  'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
  'xls' => ['application/vnd.ms-excel'],
  'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
  'csv' => ['text/plain','text/csv','application/csv'],
  'png' => ['image/png'],
  'jpg' => ['image/jpeg'],
  'jpeg' => ['image/jpeg'],
  'gif' => ['image/gif'],
  'bmp' => ['image/bmp'],
  'webp' => ['image/webp']
];
if (isset($mimeAllowMap[$ext]) && !in_array($mime, $mimeAllowMap[$ext], true)) {
    // mime mismatch -> but allow if not strict (some servers report octet-stream for docs)
    if (!($ext === 'doc' && $mime === 'application/octet-stream')) {
        header('Location: html/file_manager.html?error=type');
        exit();
    }
}
$target = $uploadDir . DIRECTORY_SEPARATOR . $name;

// avoid overwrite: add suffix if exists
$i = 1;
$base = pathinfo($name, PATHINFO_FILENAME);
$ext = pathinfo($name, PATHINFO_EXTENSION);
while (file_exists($target)) {
    $name = $base . '_' . $i . ($ext ? '.' . $ext : '');
    $target = $uploadDir . DIRECTORY_SEPARATOR . $name;
    $i++;
}

if (move_uploaded_file($f['tmp_name'], $target)) {
    // success
}

header('Location: html/file_manager.html');
exit();

?>
