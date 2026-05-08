<?php

declare(strict_types=1);

namespace PerformanceToolkit\Cache;

use PerformanceToolkit\Contracts\ModuleInterface;
use PerformanceToolkit\Core\Settings;

final class PageCache implements ModuleInterface
{
    private Settings $settings;

    private string $cache_dir;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        $this->cache_dir = WP_CONTENT_DIR . '/cache/performance-toolkit';
    }

    public function register(): void
    {
        // Cache writing – fires after headers are sent but before output is flushed.
        add_action('template_redirect', array($this, 'startBuffering'), 1);

        // Invalidate cache when content changes.
        add_action('save_post', array($this, 'purgeAll'));
        add_action('deleted_post', array($this, 'purgeAll'));

        // Refresh the flat config file whenever settings are saved.
        add_action('update_option_' . $this->settings->optionKey(), array($this, 'writeConfigFile'));
    }

    public function startBuffering(): void
    {
        if (! $this->settings->getBool('enable_page_cache') || ! $this->isCacheableRequest()) {
            return;
        }

        if (! file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }

        $cache_file = $this->cacheFilePath();

        ob_start(
            static function (string $html) use ($cache_file): string {
                if ($html === '') {
                    return $html;
                }

                file_put_contents($cache_file, $html, LOCK_EX);
                header('X-Performance-Toolkit-Cache: MISS');

                return $html;
            }
        );
    }

    public function purgeAll(): void
    {
        if (! is_dir($this->cache_dir)) {
            return;
        }

        foreach (glob($this->cache_dir . '/*.html') ?: array() as $file_path) {
            @unlink($file_path);
        }
    }

    /**
     * Write a flat PHP config file that the advanced-cache.php drop-in
     * can read before WordPress is fully loaded.
     */
    public function writeConfigFile(): void
    {
        if (! file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }

        $enabled = $this->settings->getBool('enable_page_cache');
        $ttl     = $this->settings->getInt('cache_ttl');

        $content = sprintf(
            "<?php\nreturn array(\n    'enabled' => %s,\n    'ttl'     => %d,\n);\n",
            $enabled ? 'true' : 'false',
            $ttl
        );

        file_put_contents($this->cache_dir . '/config.php', $content, LOCK_EX);
    }

    private function isCacheableRequest(): bool
    {
        if (is_admin() || is_user_logged_in() || is_preview() || is_feed() || is_404()) {
            return false;
        }

        if (! isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'GET') {
            return false;
        }

        $request_uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $request_path = strtok($request_uri, '?') ?: '/';  // strip query string for matching

        foreach ($this->settings->getLines('cache_excluded_urls') as $pattern) {
            if (strpos($pattern, '*') !== false) {
                // Wildcard pattern — e.g. /my-account/*
                if (fnmatch($pattern, $request_path)) {
                    return false;
                }
            } else {
                // Prefix match — /checkout matches /checkout, /checkout/, /checkout/step-2
                if (strpos($request_path, rtrim($pattern, '/')) === 0) {
                    return false;
                }
            }
        }

        return true;
    }

    private function cacheFilePath(): string
    {
        $scheme      = (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $host        = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $cache_key   = md5($scheme . '://' . $host . $request_uri);

        return $this->cache_dir . '/' . $cache_key . '.html';
    }
}

