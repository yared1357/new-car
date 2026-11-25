<?php
// api/get_cars.php → Public API (for your website, Telegram bot, mobile app, etc.)
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Change to your domain in production
header('Access-Control-Allow-Methods: GET');

// Optional: Cache for 30 seconds (super fast loading)
header('Cache-Control: public, max-age=30');

try {
    $stmt = $pdo->query("
        SELECT
            id, make, model, year, price,
            mileage, fuel_type, transmission, description, featured, images, category, created_at
        FROM cars
        ORDER BY featured DESC, created_at DESC
    ");

    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Base URL for absolute image links (works on any device)
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 2);

    $result = [];

    foreach ($cars as $car) {
        $images = json_decode($car['images'], true) ?? [];

        // Full absolute image URLs
        $full_images = array_map(fn($img) => rtrim($base_url, '/') . '/' . ltrim($img, '/'), $images);
        $first_image = !empty($full_images) ? $full_images[0] : null;

        $result[] = [
            'id'                  => (int)$car['id'],
            'make'                => $car['make'],
            'model'               => $car['model'],
            'year'                => (int)$car['year'],
            'price'               => (float)$car['price'],
            'price_formatted'     => '$' . number_format($car['price']),
            'mileage'             => (int)$car['mileage'],
            'mileage_formatted'   => number_format($car['mileage']) . ' km',
            'fuel_type'           => $car['fuel_type'],
            'transmission'        => $car['transmission'],
            'description'         => $car['description'] ?? '',        // FULL DESCRIPTION INCLUDED
            'featured'            => (bool)$car['featured'],
            'category'            => isset($car['category']) ? $car['category'] : null,
            'is_low_mileage'      => $car['mileage'] < 50000,           // Auto badge
            'created_at'          => $car['created_at'],
            'image_count'         => count($images),
            'first_image'         => $first_image,
            'images'              => $full_images,  // All images with full URL
        ];
    }

    // Final JSON response
    echo json_encode([
        'success' => true,
        'count'   => count($result),
        'data'    => $result,
        'generated_at' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error',
        'message' => 'Failed to fetch cars'
    ]);
}
?>