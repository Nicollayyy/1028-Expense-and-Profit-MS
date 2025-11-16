<?php
require_once __DIR__ . '/db.php';

// Helper to detect AJAX (fetch) requests
function is_ajax() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_date = isset($_POST['expense_date']) ? $_POST['expense_date'] : null;
    $category = isset($_POST['category']) ? $_POST['category'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $amount = isset($_POST['amount']) ? $_POST['amount'] : null;

    // basic validation
    if (!$expense_date || !$category || !$amount) {
        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        echo 'Missing required fields';
        exit;
    }

    // Ensure DB connection
    if (!isset($conn) || !$conn) {
        if (is_ajax()) {
            header('Content-Type: application/json');
            $err = isset($db_connect_error) ? $db_connect_error : 'Database connection not available';
            echo json_encode(['success' => false, 'error' => $err]);
            exit;
        }
        echo 'Database connection not available';
        exit;
    }

    // Determine which column to use for the date (handle different schemas and reserved words)
    $inserted = false;
    $lastId = null;
    $diagnostic = [];

    $dateColumn = null;
    $colsRes = $conn->query("SHOW COLUMNS FROM expenses");
    if ($colsRes) {
        while ($c = $colsRes->fetch_assoc()) {
            $field = $c['Field'];
            $diagnostic['columns'][] = $field;
            if ($field === 'date' || $field === 'expense_date') {
                $dateColumn = $field;
            }
            if ($field === 'id') {
                $diagnostic['has_id'] = true;
                $hasIdColumn = true;
            }
        }
        // if still not found, try to pick a column containing 'date'
        if (!$dateColumn) {
            foreach ($diagnostic['columns'] as $f) {
                if (stripos($f, 'date') !== false) { $dateColumn = $f; break; }
            }
        }
    } else {
        $diagnostic['show_columns_error'] = $conn->error;
    }

    // Try to prepare an insert using the discovered date column (quoted)
    if ($dateColumn) {
        $colName = '`' . str_replace('`','``',$dateColumn) . '`';
    $sql = "INSERT INTO expenses ($colName, `category`, `description`, `amount`) VALUES (?, ?, ?, ?)";
        $diagnostic['attempt_sql'] = $sql;
        $stmt = $conn->prepare($sql);
        $diagnostic['prepare_ok'] = $stmt ? true : false;
        if ($stmt) {
            $stmt->bind_param("sssd", $expense_date, $category, $description, $amount);
            if ($stmt->execute()) {
                $inserted = true;
                $lastId = $conn->insert_id;
            } else {
                $diagnostic['execute_error'] = $stmt->error;
            }
            $stmt->close();
        } else {
            $diagnostic['prepare_error'] = $conn->error;
        }
    }

    // If still not inserted, try inserting without a date column (category, description, amount)
    if (!$inserted) {
    $sql2 = "INSERT INTO expenses (`category`, `description`, `amount`) VALUES (?, ?, ?)";
        $diagnostic['attempt_sql_2'] = $sql2;
        $stmt2 = $conn->prepare($sql2);
        $diagnostic['prepare2_ok'] = $stmt2 ? true : false;
        if ($stmt2) {
            $stmt2->bind_param("ssd", $category, $description, $amount);
            if ($stmt2->execute()) {
                $inserted = true;
                $lastId = $conn->insert_id;
            } else {
                $diagnostic['execute2_error'] = $stmt2->error;
            }
            $stmt2->close();
        } else {
            $diagnostic['prepare2_error'] = $conn->error;
        }
    }

    if ($inserted) {
        // Build a response row from the submitted data (don't assume an 'id' column exists)
        $constructedRow = [
            'date' => $expense_date,
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
        ];

        // log success
        @file_put_contents(__DIR__ . '/add_expense.log', date('c') . " - inserted id={$lastId}\n" . print_r($constructedRow, true) . "\n", FILE_APPEND);

        // If table has an id column and we got a last insert id, try to fetch the authoritative row
        $returnedRow = null;
        $hasIdColumn = isset($hasIdColumn) && $hasIdColumn;
        if ($hasIdColumn && $lastId && $lastId > 0) {
            $selSql = "SELECT `id`, COALESCE(`date`, `expense_date`) AS `date`, `category`, `description`, `amount` FROM `expenses` WHERE `id` = ? LIMIT 1";
            if ($selStmt = $conn->prepare($selSql)) {
                $selStmt->bind_param('i', $lastId);
                if ($selStmt->execute()) {
                    $res = $selStmt->get_result();
                    if ($res && $row = $res->fetch_assoc()) {
                        // normalize amount as float
                        if (isset($row['amount'])) $row['amount'] = (float) $row['amount'];
                        $returnedRow = $row;
                    }
                }
                $selStmt->close();
            }
        }

        // prefer authoritative DB row when available
        $row = $returnedRow ?: $constructedRow;

        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'id' => $lastId, 'row' => $row]);
            exit;
        }
        header('Location: html/expenses.html');
        exit;
    } else {
        $err = $conn->error ?: 'Insert failed';
        // log failure
        @file_put_contents(__DIR__ . '/add_expense.log', date('c') . " - insert failed: {$err}\n" . print_r($diagnostic, true) . "\n", FILE_APPEND);
        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $err, 'diagnostic' => $diagnostic, 'conn_error' => $conn->error, 'conn_errno' => $conn->errno]);
            exit;
        }
        echo "Error: " . $err;
    }
}

$conn->close();
?>
