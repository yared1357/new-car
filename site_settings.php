<?php
function get_site_settings(PDO $pdo): array
{
    $defaults = [
        'site_name' => 'Smart Car Selling',
        'tagline' => 'Premium Used Cars',
        'logo_path' => 'car.png',
        'contact_phone' => '',
        'contact_email' => '',
        'address' => '',
        'primary_color' => '#1E40AF',
        'accent_color' => '#F59E0B',
        'header_color' => '#F3F4F6',
        'footer_color' => '#172554',
    ];

    $stmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $setting) {
        if (array_key_exists($setting['setting_key'], $defaults)) {
            $defaults[$setting['setting_key']] = $setting['setting_value'];
        }
    }

    foreach (['primary_color', 'accent_color', 'header_color', 'footer_color'] as $colorSetting) {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $defaults[$colorSetting])) {
            $defaults[$colorSetting] = $colorSetting === 'header_color' ? '#F3F4F6' : ($colorSetting === 'footer_color' ? '#172554' : '#1E40AF');
        }
    }

    return $defaults;
}

function get_social_links(PDO $pdo): array
{
    $links = [];
    $stmt = $pdo->query('SELECT platform, url FROM social_links');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $link) {
        if (is_valid_social_url($link['url'])) {
            $links[$link['platform']] = $link['url'];
        }
    }

    return $links;
}

function is_valid_social_url(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true) && parse_url($url, PHP_URL_HOST) !== null;
}
