<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

try {
    $stmt = $pdo->query("SELECT DISTINCT TRIM(make) AS make FROM cars WHERE make IS NOT NULL AND TRIM(make) <> '' ORDER BY make ASC");
    $brands = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode([ 'success' => true, 'data' => $brands ]);
} catch (Exception $e) {
    error_log('get_brands error: ' . $e->getMessage());
    echo json_encode([ 'success' => false, 'message' => 'Failed to load brands.' ]);
}

?>