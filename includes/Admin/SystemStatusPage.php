<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Media\ImageOptimizerDetector;
use PerformanceToolkit\Utils\FilesystemCheck;

final class SystemStatusPage extends BladeAdminPage
{
    private Settings $settings;

    private ImageOptimizerDetector $optimizer_detector;

    public function __construct(Settings $settings, ImageOptimizerDetector $optimizer_detector)
    {
        $this->settings           = $settings;
        $this->optimizer_detector = $optimizer_detector;
    }

    public function slug(): string
    {
        return 'performance-toolkit-system-status';
    }

    public function menuTitle(): string
    {
        return __('System Status', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit System Status', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'activity';
    }


    public function view(): string
    {
        return 'admin.system-status-page';
    }

    /**
     * Gather all data needed for the view.
     *
     * @return array<string, mixed>
     */
    protected function buildViewData(): array
    {
        global $wpdb;

        $wpdb_row = $wpdb->get_row(
            "SELECT SUM(data_length + index_length) AS db_size
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE()"
        );

        $mysql_size = (int) ($wpdb_row->db_size ?? 0);
        $cache_on = $this->settings->getBool('enable_page_cache');
        $active_optimizers = $this->optimizer_detector->activeOptimizers();
        $fs_status = FilesystemCheck::getCachedStatus();

        // Format filesystem status
        $fs_status_label = $fs_status['writable']
            ? __('Writable', 'performance-toolkit')
            : __('Read-only / Not writable', 'performance-toolkit');

        $fs_status_color = $fs_status['writable'] ? '#28a745' : '#dc3545';
        $fs_status_value = sprintf(
            '<span style="color: %s; font-weight: bold;">%s</span>',
            $fs_status_color,
            esc_html($fs_status_label)
        );

        $server_software = self::getServerSoftware();
        $object_cache_enabled = self::isObjectCacheEnabled();

        $rows = array(
            array(
                'label' => __('PHP Version', 'performance-toolkit'),
                'value' => PHP_VERSION,
            ),
            array(
                'label' => __('WordPress Version', 'performance-toolkit'),
                'value' => get_bloginfo('version'),
            ),
            array(
                'label' => __('MySQL Version', 'performance-toolkit'),
                'value' => $wpdb->db_version(),
            ),
            array(
                'label' => __('Plugin Version', 'performance-toolkit'),
                'value' => defined('PERFORMANCE_TOOLKIT_VERSION') ? PERFORMANCE_TOOLKIT_VERSION : __('Unknown', 'performance-toolkit'),
            ),
            array(
                'label' => __('Server Software', 'performance-toolkit'),
                'value' => $server_software['name'] . ' ' . $server_software['version'],
            ),
            array(
                'label' => __('MySQL Size', 'performance-toolkit'),
                'value' => self::formatBytes($mysql_size),
            ),
            array(
                'label' => __('Page Cache', 'performance-toolkit'),
                'value' => $cache_on ? __('On', 'performance-toolkit') : __('Off', 'performance-toolkit'),
            ),
            array(
                'label' => __('Object Cache Enabled', 'performance-toolkit'),
                'value' => $object_cache_enabled ? __('Yes', 'performance-toolkit') : __('No', 'performance-toolkit'),
            ),
            array(
                'label' => __('Cache Directory Status', 'performance-toolkit'),
                'value' => $fs_status_value,
                'is_html' => true,
            ),
            array(
                'label' => __('Image Optimizer Plugins', 'performance-toolkit'),
                'value' => $active_optimizers === array() ? __('None detected', 'performance-toolkit') : implode(', ', $active_optimizers),
            ),
        );

        foreach (self::getPHPConfiguration() as $config) {
            $rows[] = $config;
        }

        foreach (self::getServerConfiguration() as $config) {
            $rows[] = $config;
        }

        return array(
            'rows'       => $rows,
            'fs_writable' => $fs_status['writable'],
        );
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Get server software name and version
     */
    private static function getServerSoftware(): array
    {
        $server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
        $name = 'Unknown';
        $version = 'N/A';

        if (stripos($server_software, 'apache') !== false) {
            $name = 'Apache';
            if (preg_match('/Apache[\/\s]([0-9.]+)/i', $server_software, $matches)) {
                $version = $matches[1];
            }
        } elseif (stripos($server_software, 'nginx') !== false) {
            $name = 'Nginx';
            if (preg_match('/nginx[\/\s]([0-9.]+)/i', $server_software, $matches)) {
                $version = $matches[1];
            }
        } elseif (stripos($server_software, 'litespeed') !== false) {
            $name = 'LiteSpeed';
            if (preg_match('/LiteSpeed[\/\s]([0-9.]+)/i', $server_software, $matches)) {
                $version = $matches[1];
            }
        }

        return [
            'name' => $name,
            'version' => $version,
        ];
    }

    /**
     * Check if Object Cache is enabled
     */
    private static function isObjectCacheEnabled(): bool
    {
        // Check for common object cache plugins and setups
        if (! function_exists('wp_cache_add')) {
            return false;
        }

        // Check for Redis or Memcached
        if (defined('WP_REDIS_HOST') || defined('WP_REDIS_PASSWORD')) {
            return true;
        }

        if (class_exists('Memcached') || class_exists('Redis')) {
            return true;
        }

        // Check common object cache plugins
        if (is_object_in_taxonomy('post', 'wp-memcached') || get_transient('wp-cache-enabled')) {
            return true;
        }

        // Check if persistent cache is actually being used
        global $wp_object_cache;
        if (isset($wp_object_cache) && is_object($wp_object_cache)) {
            // Check for common persistent cache handlers
            $cache_class = get_class($wp_object_cache);
            if (
                strpos($cache_class, 'Redis') !== false ||
                strpos($cache_class, 'Memcached') !== false ||
                strpos($cache_class, 'APCu') !== false ||
                strpos($cache_class, 'Cache') !== false
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get important PHP configuration settings for caching and plugin functionality
     */
    private static function getPHPConfiguration(): array
    {
        $config = array();

        // PHP Memory Limit
        $memory_limit = ini_get('memory_limit');
        $config[] = array(
            'label' => __('PHP Memory Limit', 'performance-toolkit'),
            'value' => $memory_limit ? $memory_limit : __('Unlimited', 'performance-toolkit'),
        );

        // PHP Max Execution Time
        $max_execution_time = ini_get('max_execution_time');
        $config[] = array(
            'label' => __('PHP Max Execution Time', 'performance-toolkit'),
            'value' => $max_execution_time ? $max_execution_time . 's' : __('Unlimited', 'performance-toolkit'),
        );

        // PHP Upload Max File Size
        $upload_max_filesize = ini_get('upload_max_filesize');
        $config[] = array(
            'label' => __('PHP Upload Max File Size', 'performance-toolkit'),
            'value' => $upload_max_filesize ? $upload_max_filesize : __('Unknown', 'performance-toolkit'),
        );

        // PHP Post Max Size
        $post_max_size = ini_get('post_max_size');
        $config[] = array(
            'label' => __('PHP Post Max Size', 'performance-toolkit'),
            'value' => $post_max_size ? $post_max_size : __('Unknown', 'performance-toolkit'),
        );

        // GZip compression
        $gzip_enabled = extension_loaded('zlib') && ini_get('zlib.output_compression');
        $config[] = array(
            'label' => __('GZip Compression', 'performance-toolkit'),
            'value' => $gzip_enabled ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit'),
        );

        // OPcache
        $opcache_enabled = extension_loaded('Zend OPcache') && ini_get('opcache.enable');
        $config[] = array(
            'label' => __('PHP OPcache', 'performance-toolkit'),
            'value' => $opcache_enabled ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit'),
        );

        return $config;
    }

    /**
     * Get important server configuration settings for caching and plugin functionality
     */
    private static function getServerConfiguration(): array
    {
        $config = array();
        $server_software = self::getServerSoftware();

        // Apache/Nginx specific settings
        if ($server_software['name'] === 'Apache') {
            // Check for mod_rewrite
            $mod_rewrite = function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules(), true);
            $config[] = array(
                'label' => __('Apache mod_rewrite', 'performance-toolkit'),
                'value' => $mod_rewrite ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit'),
            );

            // Check for mod_expires
            $mod_expires = function_exists('apache_get_modules') && in_array('mod_expires', apache_get_modules(), true);
            $config[] = array(
                'label' => __('Apache mod_expires', 'performance-toolkit'),
                'value' => $mod_expires ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit'),
            );

            // Check for mod_deflate
            $mod_deflate = function_exists('apache_get_modules') && in_array('mod_deflate', apache_get_modules(), true);
            $config[] = array(
                'label' => __('Apache mod_deflate', 'performance-toolkit'),
                'value' => $mod_deflate ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit'),
            );

            // Check for mod_headers
            $mod_headers = function_exists('apache_get_modules') && in_array('mod_headers', apache_get_modules(), true);
            $config[] = array(
                'label' => __('Apache mod_headers', 'performance-toolkit'),
                'value' => $mod_headers ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit'),
            );
        } elseif ($server_software['name'] === 'Nginx') {
            $config[] = array(
                'label' => __('Nginx Configuration', 'performance-toolkit'),
                'value' => __('Please review your server configuration', 'performance-toolkit'),
            );
        }

        // HTTPS status
        $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $config[] = array(
            'label' => __('HTTPS Enabled', 'performance-toolkit'),
            'value' => $https ? __('Yes', 'performance-toolkit') : __('No', 'performance-toolkit'),
        );

        // File upload capability
        $uploads_dir = wp_upload_dir();
        $uploads_writable = is_writable($uploads_dir['basedir']);
        $config[] = array(
            'label' => __('Uploads Directory Writable', 'performance-toolkit'),
            'value' => $uploads_writable ? __('Yes', 'performance-toolkit') : __('No', 'performance-toolkit'),
        );

        return $config;
    }
}

