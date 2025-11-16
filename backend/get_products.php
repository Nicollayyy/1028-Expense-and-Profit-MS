<?php
// Returns a JSON array of products for the front-end.
// The script is intentionally permissive about schema: it reads common fields if present
// and returns whatever it can find (id, sku, name, price, stock, category, category_slug).
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($conn)) {
    echo json_encode([]);
    exit;
}

function tableExists($conn, $table) {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
}

if (!tableExists($conn, 'products')) {
    echo json_encode([]);
    exit;
}

// Inspect columns in products table
$cols = [];
$colRes = $conn->query("SHOW COLUMNS FROM products");
if ($colRes) {
    while ($c = $colRes->fetch_assoc()) {
        $cols[$c['Field']] = true;
    }
}

// Build select list depending on available columns
$selectCols = [];
$prefer = ['id','sku','name','price','stock','category','category_slug','category_id'];
foreach ($prefer as $c) {
    if (isset($cols[$c])) $selectCols[] = $c;
}

if (empty($selectCols)) {
    // As a last resort select all
    $sql = "SELECT * FROM products ORDER BY name";
} else {
    $sql = 'SELECT ' . implode(',', array_map(function($c){return $c;}, $selectCols)) . ' FROM products ORDER BY name';
}

$res = $conn->query($sql);
$out = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        // Map fields to a normalized response expected by the front-end
        $p = [];
        $p['id'] = $r['id'] ?? null;
        $p['sku'] = $r['sku'] ?? ($r['product_code'] ?? null);
        $p['name'] = $r['name'] ?? ($r['title'] ?? null);
        $p['price'] = isset($r['price']) ? (float)$r['price'] : (isset($r['cost']) ? (float)$r['cost'] : null);
        $p['stock'] = isset($r['stock']) ? (int)$r['stock'] : (isset($r['qty']) ? (int)$r['qty'] : null);
        $p['category'] = $r['category'] ?? null;
        $p['category_slug'] = $r['category_slug'] ?? null;
        // if no category_slug, derive from category name
        if (empty($p['category_slug']) && !empty($p['category'])) {
            $p['category_slug'] = preg_replace('/[^a-z0-9]+/i', '-', trim(strtolower($p['category'])));
        }
        // include raw row for debugging/extension if needed
        $p['_raw'] = $r;
        $out[] = $p;
    }
}

echo json_encode($out);

?>
