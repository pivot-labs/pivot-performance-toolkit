<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once __DIR__ . '/../includes/Core/Settings.php';

use PivotPerformanceToolkit\Core\Settings;

$settings = new Settings();
$input = array(
    'enable_page_cache' => '1',
    'cache_ttl' => 10,
    'defer_scripts' => '',
    'lazy_load_images' => '1',
);

$result = $settings->sanitize($input);

if (! is_array($result) || $result['cache_ttl'] !== 60 || $result['defer_scripts'] !== false) {
    fwrite(STDERR, "Smoke test failed.\n");
    exit(1);
}

echo "Smoke test passed.\n";

