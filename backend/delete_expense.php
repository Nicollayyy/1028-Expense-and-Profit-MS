<?php
require_once __DIR__ . '/db.php';

function is_ajax() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

$id = null;
if (isset($_POST['id'])) $id = $_POST['id'];
elseif (isset($_GET['id'])) $id = $_GET['id'];

if ($id !== null) {
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: html/expenses.html');
        exit;
    } else {
        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $stmt->error]);
            exit;
        }
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
