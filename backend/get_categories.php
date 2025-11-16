<?php
// Returns a JSON array of categories.
// Tries the `categories` table first; if not present, falls back to distinct product category names.
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($conn)) {
    echo json_encode([]);
    exit;
}

// Helper: check if a table exists
function tableExists($conn, $table) {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
}

$out = [];

if (tableExists($conn, 'categories')) {
    // Try to read id, name, slug
    $sql = "SELECT id, name, slug FROM categories ORDER BY name";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $out[] = [
                'id' => $r['id'] ?? null,
                'name' => $r['name'] ?? ($r['category'] ?? ''),
                'slug' => $r['slug'] ?? ($r['name'] ?? '')
            ];
        }
        echo json_encode($out);
        exit;
    }
}

// Fallback: try to get distinct category names from products table
if (tableExists($conn, 'products')) {
    $sql = "SELECT DISTINCT COALESCE(category, '') AS name FROM products ORDER BY name";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $name = $r['name'];
            if ($name === null || $name === '') continue;
            $slug = preg_replace('/[^a-z0-9]+/i', '-', trim(strtolower($name)));
            $out[] = ['id' => null, 'name' => $name, 'slug' => $slug];
        }
        echo json_encode($out);
        exit;
    }
}

// No categories found — return empty array
echo json_encode($out);

?>
