<?php
/**
 * Plugin Name: Performance Toolkit
 * Plugin URI: https://www.digitalcanvas.com/plugins/performance-toolkit/
 * Description: WordPress performance optimization toolkit.
 * Version: 0.1.0
 * Author: Jeffrey Shaikh
 * Requires at least: 6.5
 * Requires PHP: 8.2
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PERFORMANCE_TOOLKIT_VERSION', '0.1.0');
define('PERFORMANCE_TOOLKIT_PATH', plugin_dir_path(__FILE__));
define('PERFORMANCE_TOOLKIT_URL', plugin_dir_url(__FILE__));

$autoload_file = PERFORMANCE_TOOLKIT_PATH . 'vendor/autoload.php';

if (file_exists($autoload_file)) {
    require_once $autoload_file;
}

register_activation_hook(__FILE__, array('\\PerformanceToolkit\\Core\\Lifecycle', 'activate'));
register_deactivation_hook(__FILE__, array('\\PerformanceToolkit\\Core\\Lifecycle', 'deactivate'));

add_action(
    'plugins_loaded',
    static function (): void {
        if (! class_exists('\\PerformanceToolkit\\Core\\Plugin')) {
            return;
        }

        \PerformanceToolkit\Core\Plugin::instance()->boot();
    }
);

