
<?php
// file_uploaddrive.php - backend-only JSON API for the File Manager
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit;
}

function api_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    api_error("Google PHP Client not found (vendor/autoload.php). Please run 'composer install'", 500);
}
require_once __DIR__ . '/vendor/autoload.php';

if (!file_exists(__DIR__ . '/credentials.json')) {
    // We'll check for credentials later after resolving configured credentials path.
    // Keep this check as a fallback removed since path may be configured below.
}

// Ensure the Google Client class is available (otherwise give a clear error instead of a fatal)
// Support both the legacy Google_Client and the modern namespaced Google\Client
if (!class_exists('\Google_Client') && !class_exists('Google\Client')) {
    api_error("Google Client classes not found. Please install the Google API client via Composer: composer require google/apiclient", 500);
}
// Load configuration values (env overrides config.php)
$config = [];
if (file_exists(__DIR__ . '/config.php')) {
    $cfg = require __DIR__ . '/config.php';
    if (is_array($cfg)) {
        $config = $cfg;
    }
}

try {
    // Instantiate whichever Google Client class is available (legacy or namespaced)
    if (class_exists('Google_Client')) {
        $client = new Google_Client();
    } elseif (class_exists('Google\\Client')) {
        $client = new \Google\Client();
    } else {
        api_error("Google Client classes not found after autoload", 500);
    }

    // Credentials path: prefer environment variable, then config.php, then default credentials.json
    $credentialsPath = getenv('DRIVE_CREDENTIALS_PATH') ?: (isset($config['DRIVE_CREDENTIALS_PATH']) ? $config['DRIVE_CREDENTIALS_PATH'] : (__DIR__ . '/credentials.json'));
    if (!file_exists($credentialsPath)) {
        api_error("Google credentials not found ({$credentialsPath}). Please add the service account file or set DRIVE_CREDENTIALS_PATH", 500);
    }
    $client->setAuthConfig($credentialsPath);

    // Detect available Drive service class and scope at runtime to avoid static analyzer errors
    $scope = 'https://www.googleapis.com/auth/drive.file';
    $serviceClass = null;

    if (class_exists('Google\\Service\\Drive')) {
        // Modern namespaced client
        $serviceClass = 'Google\\Service\\Drive';
        if (defined($serviceClass . '::DRIVE_FILE')) {
            $scope = constant($serviceClass . '::DRIVE_FILE');
        }
    } elseif (class_exists('Google_Service_Drive')) {
        // Legacy class name (older google/apiclient versions)
        $serviceClass = 'Google_Service_Drive';
        if (defined($serviceClass . '::DRIVE_FILE')) {
            $scope = constant($serviceClass . '::DRIVE_FILE');
        }
    } else {
        api_error("Google Drive service class not found. Ensure google/apiclient is installed", 500);
    }

    $client->addScope($scope);
    $service = new $serviceClass($client);
    // detect appropriate Drive file metadata class for creating files
    if ($serviceClass === 'Google\\Service\\Drive') {
        $driveFileClass = 'Google\\Service\\Drive\\DriveFile';
    } else {
        $driveFileClass = 'Google_Service_Drive_DriveFile';
    }
} catch (Throwable $e) {
    // Throwable covers both Exception and Error (safer for runtime failures)
    api_error('Failed to initialize Google client: ' . $e->getMessage(), 500);
}

$folderId = getenv('DRIVE_FOLDER_ID') ?: ($config['DRIVE_FOLDER_ID'] ?? null);
if (empty($folderId)) {
    api_error('Drive folder ID is not configured. Set the environment variable DRIVE_FOLDER_ID or put it in config.php as DRIVE_FOLDER_ID', 500);
}

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $optParams = [
            'fields' => 'files(id,name,mimeType,createdTime,size)',
            'q' => "'$folderId' in parents",
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
            'pageSize' => 1000
        ];
        $driveFiles = $service->files->listFiles($optParams);
        $files = [];
        foreach ($driveFiles->getFiles() as $f) {
            $files[] = [
                'id' => $f->getId(),
                'name' => $f->getName(),
                'mimeType' => $f->getMimeType(),
                'createdTime' => $f->getCreatedTime(),
                'size' => $f->getSize()
            ];
        }
        echo json_encode(['success' => true, 'data' => $files]);
        exit;
    } catch (Throwable $e) {
        api_error('Error fetching files from Drive: ' . $e->getMessage(), 500);
    }
}

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        api_error('No file uploaded', 400);
    }

    $file_name = $_FILES['file']['name'];
    $file_tmp = $_FILES['file']['tmp_name'];

    $allowed_types = ['jpg','jpeg','png','pdf','doc','docx','xlsx','txt','csv','gif'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        api_error('File type not allowed. Allowed: ' . implode(', ', $allowed_types), 400);
    }

    try {
        // Instantiate metadata using the detected Drive file class (namespaced or legacy)
        if (isset($driveFileClass) && class_exists($driveFileClass)) {
            $fileMetadata = new $driveFileClass([
                'name' => $file_name,
                'parents' => [$folderId]
            ]);
        } else {
            // fallback: build a plain array for metadata (some clients accept arrays)
            $fileMetadata = ['name' => $file_name, 'parents' => [$folderId]];
        }

        $content = file_get_contents($file_tmp);
        $created = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => mime_content_type($file_tmp) ?: 'application/octet-stream',
            'uploadType' => 'multipart',
            'fields' => 'id,name',
            'supportsAllDrives' => true
        ]);

        // Use accessor methods to get properties from the created file object
        $createdId = method_exists($created, 'getId') ? $created->getId() : ($created->id ?? null);
        $createdName = method_exists($created, 'getName') ? $created->getName() : ($created->name ?? null);

        echo json_encode(['success' => true, 'data' => ['id' => $createdId, 'name' => $createdName]]);
        exit;
    } catch (Throwable $e) {
        api_error('Error uploading to Google Drive: ' . $e->getMessage(), 500);
    }
}

api_error('Invalid action or method. Use GET?action=list or POST?action=upload', 400);




