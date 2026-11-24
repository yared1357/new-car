<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/site_settings.php';

$siteSettings = get_site_settings($pdo);
$siteName = $siteSettings['site_name'] ?: 'Car App';

header('Content-Type: application/manifest+json');
header('Cache-Control: no-cache');

echo json_encode([
    'name' => $siteName,
    'short_name' => $siteName,
    'description' => $siteSettings['tagline'] ?: 'Browse cars and contact us',
    'start_url' => './',
    'scope' => './',
    'display' => 'standalone',
    'orientation' => 'portrait-primary',
    'theme_color' => $siteSettings['primary_color'],
    'background_color' => $siteSettings['header_color'],
    'icons' => [
        [
            'src' => 'icons/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src' => 'icons/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_SLASHES);
