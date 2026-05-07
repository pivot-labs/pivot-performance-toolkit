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

define('PERFORMANCE_TOOLKIT_VERSION', '0.1.0');
define('PERFORMANCE_TOOLKIT_PATH', plugin_dir_path(__FILE__));
define('PERFORMANCE_TOOLKIT_URL', plugin_dir_url(__FILE__));


if (! defined('ABSPATH')) {
    exit;
}