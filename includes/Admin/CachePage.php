<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class CachePage extends BladeAdminPage
{
    private const CLEAR_ACTION = 'performance_toolkit_clear_cache';

    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        add_action('admin_post_' . self::CLEAR_ACTION, array($this, 'handleClearCache'));
    }

    public function slug(): string
    {
        return 'performance-toolkit-cache';
    }

    public function menuTitle(): string
    {
        return __('Cache', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Cache', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'rocket';
    }

    public function view(): string
    {
        return 'admin.cache-page';
    }

    /**
     * Gather all data needed for the view.
     *
     * @return array<string, mixed>
     */
    protected function buildViewData(): array
    {
        $options          = $this->settings->all();
        $settings_updated = isset($_GET['settings-updated']) && (string) wp_unslash($_GET['settings-updated']) === 'true';
        $cache_cleared    = (bool) get_transient('performance_toolkit_cache_cleared');

        if ($cache_cleared) {
            delete_transient('performance_toolkit_cache_cleared');
        }

        $cache_dir    = WP_CONTENT_DIR . '/cache/performance-toolkit';
        $current_size = $this->getCacheDirSize($cache_dir);
        $max_bytes    = (int) $options['max_cache_size_mb'] * 1048576;
        $usage_pct    = $max_bytes > 0 ? min(100, (int) round($current_size / $max_bytes * 100)) : 0;
        $object_cache = $this->getObjectCacheStatus();

        return array(
            'options'              => $options,
            'settings_updated'     => $settings_updated,
            'cache_cleared'        => $cache_cleared,
            'cache_size'           => $current_size,
            'cache_size_formatted' => self::formatBytes($current_size),
            'max_cache_bytes'      => $max_bytes,
            'usage_pct'            => $usage_pct,
            'option_key'           => $this->settings->optionKey(),
            'clear_action'         => self::CLEAR_ACTION,
            'object_cache'         => $object_cache,
        );
    }

    /**
     * Get object cache status and details.
     *
     * @return array<string, mixed>
     */
    private function getObjectCacheStatus(): array
    {
        $dropin_path = WP_CONTENT_DIR . '/object-cache.php';

        return array(
            'active'        => file_exists($dropin_path),
            'status_label'  => file_exists($dropin_path) ? __('Active', 'performance-toolkit') : __('Inactive', 'performance-toolkit'),
            'provider'      => defined('WP_REDIS_CLUSTER') ? 'Redis Cluster' : (defined('WP_REDIS_HOST') ? 'Redis' : __('Unknown', 'performance-toolkit')),
            'dropin_label'  => file_exists($dropin_path) ? __('Installed', 'performance-toolkit') : __('Not installed', 'performance-toolkit'),
            'size_bytes'    => wp_cache_get('_stats', '')['bytes'] ?? 0,
            'size_formatted' => self::formatBytes(wp_cache_get('_stats', '')['bytes'] ?? 0),
        );
    }

    public function handleClearCache(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'performance-toolkit'));
        }

        check_admin_referer('ptk_clear_cache');

        $cache_dir = WP_CONTENT_DIR . '/cache/performance-toolkit';

        // Delete all HTML cache files
        foreach (glob($cache_dir . '/*.html') ?: array() as $file_path) {
            @unlink($file_path);
        }

        // Keep this notice to one redirect only.
        set_transient('performance_toolkit_cache_cleared', true, 30);

        $redirect = add_query_arg(
            array(
                'page' => $this->slug(),
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Get the total size of a directory in bytes.
     */
    private function getCacheDirSize(string $dir): int
    {
        $total = 0;

        foreach (glob($dir . '/*.html') ?: array() as $file) {
            $total += (int) @filesize($file);
        }

        return $total;
    }

    /**
     * Format bytes into human-readable format.
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}







