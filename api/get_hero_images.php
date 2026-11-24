<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM hero_images ORDER BY `order` ASC, created_at DESC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function($img) {
        return [
            'id' => $img['id'],
            'title' => $img['title'],
            'description' => $img['description'],
            'image_url' => $img['image_path'],
            'order' => $img['order']
        ];
    }, $images);

    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch hero images']);
}
?>