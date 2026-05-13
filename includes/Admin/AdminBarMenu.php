<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Contracts\ModuleInterface;
use WP_Admin_Bar;

final class AdminBarMenu implements ModuleInterface
{
    private const PURGE_ACTION = 'performance_toolkit_purge_all_cache';

    private const PURGE_PAGE_ACTION = 'performance_toolkit_purge_page_cache';

    private const PAGE_CACHE_DIR = WP_CONTENT_DIR . '/cache/performance-toolkit';

    private const MINIFIED_CACHE_DIR = WP_CONTENT_DIR . '/cache/performance-toolkit/minified-assets';

    public function register(): void
    {
        add_action('admin_bar_menu', array($this, 'registerMenu'), 100);
        add_action('admin_post_' . self::PURGE_ACTION, array($this, 'handlePurgeAllCache'));
        add_action('admin_post_' . self::PURGE_PAGE_ACTION, array($this, 'handlePurgePageCache'));
        add_action('admin_notices', array($this, 'renderAdminNotice'));
    }

    public function registerMenu(WP_Admin_Bar $admin_bar): void
    {
        if (! is_admin_bar_showing() || ! current_user_can('manage_options')) {
            return;
        }

        $settings_url = admin_url('admin.php?page=performance-toolkit');
        $purge_url    = wp_nonce_url(
            admin_url('admin-post.php?action=' . self::PURGE_ACTION),
            'ptk_adminbar_purge_all_cache'
        );

        $admin_bar->add_node(
            array(
                'id'    => 'performance-toolkit',
                'title' => __('Performance', 'performance-toolkit'),
                'href'  => $settings_url,
            )
        );

        $admin_bar->add_node(
            array(
                'id'     => 'performance-toolkit-settings',
                'parent' => 'performance-toolkit',
                'title'  => __('Settings', 'performance-toolkit'),
                'href'   => $settings_url,
            )
        );

        $admin_bar->add_node(
            array(
                'id'     => 'performance-toolkit-purge-all-cache',
                'parent' => 'performance-toolkit',
                'title'  => __('Purge all cache', 'performance-toolkit'),
                'href'   => $purge_url,
            )
        );

        $status = $this->getCurrentPageCacheStatus();

        $admin_bar->add_node(
            array(
                'id'     => 'performance-toolkit-page-cache-status',
                'parent' => 'performance-toolkit',
                'title'  => sprintf(
                    /* translators: %s: cache status label */
                    __('Page cache: %s', 'performance-toolkit'),
                    '<span class="ptk-cache-status ' . esc_attr($status['class']) . '">' . esc_html($status['label']) . '</span>'
                ),
                'href'   => false,
                'meta'   => array(
                    'html' => '',
                ),
            )
        );

        $purge_page_url = '#';

        if ($status['cacheable'] && is_string($status['url']) && $status['url'] !== '') {
            $purge_page_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action'     => self::PURGE_PAGE_ACTION,
                        'ptk_target' => rawurlencode($status['url']),
                    ),
                    admin_url('admin-post.php')
                ),
                'ptk_adminbar_purge_page_cache'
            );
        }

        $admin_bar->add_node(
            array(
                'id'     => 'performance-toolkit-purge-page-cache',
                'parent' => 'performance-toolkit',
                'title'  => __('Purge this page', 'performance-toolkit'),
                'href'   => $status['cacheable'] ? $purge_page_url : '#',
                'meta'   => array(
                    'class' => $status['cacheable'] ? '' : 'ptk-disabled',
                    'title' => $status['cacheable'] ? __('Purge cache for this page', 'performance-toolkit') : __('Not on a cacheable page', 'performance-toolkit'),
                ),
            )
        );
    }

    private function isCurrentPageCacheable(): bool
    {
        // Only on frontend.
        if (is_admin()) {
            return false;
        }

        // Only on singular pages/posts
        if (! is_singular()) {
            return false;
        }

        // Skip previews
        if (is_preview()) {
            return false;
        }

        // Skip feeds
        if (is_feed()) {
            return false;
        }

        // Skip 404s
        if (is_404()) {
            return false;
        }

        // Only GET requests.
        if (! isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'GET') {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, bool|string|null>
     */
    private function getCurrentPageCacheStatus(): array
    {
        // Indicator should reflect file state even for logged-in/admin-bar visits.
        if (is_admin() || ! isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'GET') {
            return array(
                'label'     => __('Unavailable', 'performance-toolkit'),
                'class'     => 'ptk-cache-status-bypass',
                'cacheable' => $this->isCurrentPageCacheable(),
                'url'       => null,
            );
        }

        $url = $this->currentRequestUrl();

        if (! is_string($url) || $url === '') {
            return array(
                'label'     => __('Not cacheable', 'performance-toolkit'),
                'class'     => 'ptk-cache-status-bypass',
                'cacheable' => false,
                'url'       => null,
            );
        }

        $cache_file = $this->cacheFilePathFromUrl($url);

        if (! is_string($cache_file) || $cache_file === '') {
            return array(
                'label'     => __('Unavailable', 'performance-toolkit'),
                'class'     => 'ptk-cache-status-bypass',
                'cacheable' => $this->isCurrentPageCacheable(),
                'url'       => $url,
            );
        }

        if (! is_file($cache_file)) {
            return array(
                'label'     => __('File missing', 'performance-toolkit'),
                'class'     => 'ptk-cache-status-miss',
                'cacheable' => $this->isCurrentPageCacheable(),
                'url'       => $url,
            );
        }

        $ttl      = $this->cacheTtl();
        $filetime = (int) @filemtime($cache_file);

        if ($filetime <= 0 || ($filetime + $ttl) < time()) {
            return array(
                'label'     => __('File stale', 'performance-toolkit'),
                'class'     => 'ptk-cache-status-miss',
                'cacheable' => $this->isCurrentPageCacheable(),
                'url'       => $url,
            );
        }

        return array(
            'label'     => __('File present', 'performance-toolkit'),
            'class'     => 'ptk-cache-status-hit',
            'cacheable' => $this->isCurrentPageCacheable(),
            'url'       => $url,
        );
    }

    private function currentRequestUrl(): ?string
    {
        $scheme      = (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $host        = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

        if ($host === '') {
            return null;
        }

        return $scheme . '://' . $host . $request_uri;
    }

    private function cacheFilePathFromUrl(string $url): ?string
    {
        $parts = wp_parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = isset($parts['scheme']) ? (string) $parts['scheme'] : 'http';
        $host   = isset($parts['host']) ? (string) $parts['host'] : '';
        $path   = isset($parts['path']) ? (string) $parts['path'] : '/';
        $query  = isset($parts['query']) ? (string) $parts['query'] : '';

        if ($host === '') {
            return null;
        }

        $request_uri = $path;
        if ($query !== '') {
            $request_uri .= '?' . $query;
        }

        $cache_key = md5($scheme . '://' . $host . $request_uri);

        return self::PAGE_CACHE_DIR . '/' . $cache_key . '.html';
    }

    private function cacheTtl(): int
    {
        $config_file = self::PAGE_CACHE_DIR . '/config.php';

        if (! is_file($config_file)) {
            return 600;
        }

        $config = include $config_file;

        if (! is_array($config)) {
            return 600;
        }

        $ttl = isset($config['ttl']) ? (int) $config['ttl'] : 600;

        return max(60, $ttl);
    }

    public function handlePurgeAllCache(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_adminbar_purge_all_cache');

        foreach (glob(self::PAGE_CACHE_DIR . '/*.html') ?: array() as $file_path) {
            @unlink($file_path);
        }

        foreach (glob(self::MINIFIED_CACHE_DIR . '/*.min.css') ?: array() as $file_path) {
            @unlink($file_path);
        }

        foreach (glob(self::MINIFIED_CACHE_DIR . '/*.min.js') ?: array() as $file_path) {
            @unlink($file_path);
        }

        $redirect = wp_get_referer();

        if (! is_string($redirect) || $redirect === '') {
            $redirect = admin_url('admin.php?page=performance-toolkit');
        }

        wp_safe_redirect(add_query_arg('ptk_cache_purged', '1', $redirect));
        exit;
    }

    public function handlePurgePageCache(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_adminbar_purge_page_cache');

        $target_raw = isset($_GET['ptk_target']) ? sanitize_text_field((string) wp_unslash($_GET['ptk_target'])) : '';
        $target_url = $target_raw !== '' ? rawurldecode($target_raw) : '';

        if ($target_url === '') {
            $target_url = (string) wp_get_referer();
        }

        if ($target_url !== '') {
            $cache_file = $this->cacheFilePathFromUrl($target_url);
            if (is_string($cache_file) && is_file($cache_file)) {
                @unlink($cache_file);
            }
        }

        $redirect = wp_get_referer();

        if (! is_string($redirect) || $redirect === '') {
            $redirect = home_url();
        }

        wp_safe_redirect(add_query_arg('ptk_page_cache_purged', '1', $redirect));
        exit;
    }

    public function renderAdminNotice(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['ptk_cache_purged']) && (string) wp_unslash($_GET['ptk_cache_purged']) === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Performance Toolkit cache was purged.', 'performance-toolkit') . '</p></div>';
        }

        if (isset($_GET['ptk_page_cache_purged']) && (string) wp_unslash($_GET['ptk_page_cache_purged']) === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('This page\'s cache was purged.', 'performance-toolkit') . '</p></div>';
        }

        // Add CSS for disabled menu items
        echo '<style>
            #wpadminbar .ptk-disabled {
                opacity: 0.5;
                pointer-events: none;
                cursor: not-allowed;
            }
            #wpadminbar .ptk-cache-status {
                font-weight: 600;
            }
            #wpadminbar .ptk-cache-status-hit {
                color: #7bd88f;
            }
            #wpadminbar .ptk-cache-status-miss {
                color: #ffce6a;
            }
            #wpadminbar .ptk-cache-status-bypass {
                color: #a7aaad;
            }
        </style>';
    }
}

