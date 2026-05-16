<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class CachePage extends BladeAdminPage
{
    private const CLEAR_ACTION = 'performance_toolkit_clear_cache';
    private const CLEAR_MINIFIED_ACTION = 'performance_toolkit_clear_minified_cache';
    private const AJAX_CLEAR_ACTION = 'performance_toolkit_ajax_clear_cache';
    private const AJAX_CLEAR_MINIFIED_ACTION = 'performance_toolkit_ajax_clear_minified_cache';
    private const AJAX_REFRESH_USAGE_ACTION = 'performance_toolkit_ajax_refresh_cache_usage';

    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        add_action('admin_post_' . self::CLEAR_ACTION, array($this, 'handleClearCache'));
        add_action('admin_post_' . self::CLEAR_MINIFIED_ACTION, array($this, 'handleClearMinifiedCache'));
        add_action('wp_ajax_' . self::AJAX_CLEAR_ACTION, array($this, 'handleClearCacheAjax'));
        add_action('wp_ajax_' . self::AJAX_CLEAR_MINIFIED_ACTION, array($this, 'handleClearMinifiedCacheAjax'));
        add_action('wp_ajax_' . self::AJAX_REFRESH_USAGE_ACTION, array($this, 'handleRefreshCacheUsageAjax'));
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

        $usage_snapshot = $this->getCacheUsageSnapshot($options);
        $object_cache = $this->getObjectCacheStatus();

        return array(
            'options'              => $options,
            'settings_updated'     => $settings_updated,
            'cache_cleared'        => $cache_cleared,
            'cache_size'           => $usage_snapshot['cache_size'],
            'cache_size_formatted' => $usage_snapshot['cache_size_formatted'],
            'max_cache_bytes'      => $usage_snapshot['max_cache_bytes'],
            'usage_pct'            => $usage_snapshot['usage_pct'],
            'option_key'           => $this->settings->optionKey(),
            'clear_action'         => self::CLEAR_ACTION,
            'clear_minified_action' => self::CLEAR_MINIFIED_ACTION,
            'ajax_clear_action'    => self::AJAX_CLEAR_ACTION,
            'ajax_clear_minified_action' => self::AJAX_CLEAR_MINIFIED_ACTION,
            'ajax_refresh_usage_action' => self::AJAX_REFRESH_USAGE_ACTION,
            'ajax_clear_nonce'     => wp_create_nonce('ptk_clear_cache_ajax'),
            'ajax_clear_minified_nonce' => wp_create_nonce('ptk_clear_minified_cache_ajax'),
            'ajax_refresh_usage_nonce' => wp_create_nonce('ptk_refresh_cache_usage_ajax'),
            'cache_cleared_message' => __('Cache cleared successfully.', 'performance-toolkit'),
            'minified_cache_cleared_message' => __('Minified CSS/JS cache cleared successfully.', 'performance-toolkit'),
            'preload_not_implemented_message' => __('Preload cache is not implemented yet.', 'performance-toolkit'),
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

        $this->clearPageCacheFiles();

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

    public function handleClearMinifiedCache(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'performance-toolkit'));
        }

        check_admin_referer('ptk_clear_minified_cache');

        $this->clearMinifiedCacheFiles();

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

    public function handleClearCacheAjax(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'performance-toolkit')), 403);
        }

        check_ajax_referer('ptk_clear_cache_ajax');

        $this->clearPageCacheFiles();

        wp_send_json_success(array(
            'message' => __('Cache cleared successfully.', 'performance-toolkit'),
            'usage' => $this->getUsagePayload(),
        ));
    }

    public function handleClearMinifiedCacheAjax(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'performance-toolkit')), 403);
        }

        check_ajax_referer('ptk_clear_minified_cache_ajax');

        $this->clearMinifiedCacheFiles();

        wp_send_json_success(array(
            'message' => __('Minified CSS/JS cache cleared successfully.', 'performance-toolkit'),
            'usage' => $this->getUsagePayload(),
        ));
    }

    public function handleRefreshCacheUsageAjax(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'performance-toolkit')), 403);
        }

        check_ajax_referer('ptk_refresh_cache_usage_ajax');

        wp_send_json_success(array(
            'message' => __('Preload cache is not implemented yet.', 'performance-toolkit'),
            'usage' => $this->getUsagePayload(),
        ));
    }

    private function clearPageCacheFiles(): void
    {
        $cache_dir = WP_CONTENT_DIR . '/cache/performance-toolkit';

        foreach (glob($cache_dir . '/*.html') ?: array() as $file_path) {
            @unlink($file_path);
        }
    }

    private function clearMinifiedCacheFiles(): void
    {
        $cache_dir = WP_CONTENT_DIR . '/cache/performance-toolkit/minified-assets';

        foreach (glob($cache_dir . '/*.min.css') ?: array() as $file_path) {
            @unlink($file_path);
        }

        foreach (glob($cache_dir . '/*.min.js') ?: array() as $file_path) {
            @unlink($file_path);
        }
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
     * @param array<string, mixed> $options
     * @return array<string, int|string>
     */
    private function getCacheUsageSnapshot(array $options): array
    {
        $cache_dir = WP_CONTENT_DIR . '/cache/performance-toolkit';
        $cache_size = $this->getCacheDirSize($cache_dir);
        $max_cache_size_mb = (int) ($options['max_cache_size_mb'] ?? 0);
        $max_cache_bytes = $max_cache_size_mb * 1048576;
        $usage_pct = $max_cache_bytes > 0 ? min(100, (int) round($cache_size / $max_cache_bytes * 100)) : 0;

        return array(
            'cache_size' => $cache_size,
            'cache_size_formatted' => self::formatBytes($cache_size),
            'max_cache_bytes' => $max_cache_bytes,
            'max_cache_size_mb' => $max_cache_size_mb,
            'usage_pct' => $usage_pct,
            'cache_usage_label' => sprintf(
                __('%1$s of %2$d MB used (%3$d%%)', 'performance-toolkit'),
                self::formatBytes($cache_size),
                $max_cache_size_mb,
                $usage_pct
            ),
        );
    }

    /**
     * @return array<string, int|string>
     */
    private function getUsagePayload(): array
    {
        $snapshot = $this->getCacheUsageSnapshot($this->settings->all());

        return array(
            'usage_pct' => (int) $snapshot['usage_pct'],
            'cache_usage_label' => (string) $snapshot['cache_usage_label'],
        );
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

