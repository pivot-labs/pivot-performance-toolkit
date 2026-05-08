<?php

declare(strict_types=1);

namespace PerformanceToolkit\Core;

final class Lifecycle
{
    private static function dropinSource(): string
    {
        return PERFORMANCE_TOOLKIT_PATH . 'includes/Cache/advanced-cache.php';
    }

    private static function dropinDest(): string
    {
        return WP_CONTENT_DIR . '/advanced-cache.php';
    }

    private static function cacheDir(): string
    {
        return WP_CONTENT_DIR . '/cache/performance-toolkit';
    }

    public static function activate(): void
    {
        // Create cache directory.
        if (! file_exists(self::cacheDir())) {
            wp_mkdir_p(self::cacheDir());
        }

        // Install the advanced-cache.php drop-in.
        self::installDropin();

        // Add WP_CACHE define to wp-config.php.
        self::enableWpCache();
    }

    public static function deactivate(): void
    {
        // Remove the drop-in only if it was installed by us.
        self::removeDropin();

        // Remove WP_CACHE define we added.
        self::disableWpCache();

        // Purge all cached HTML files.
        foreach (glob(self::cacheDir() . '/*.html') ?: array() as $file) {
            @unlink($file);
        }

        // Remove the config file.
        $config = self::cacheDir() . '/config.php';
        if (file_exists($config)) {
            @unlink($config);
        }
    }

    // -------------------------------------------------------------------------
    // Drop-in helpers
    // -------------------------------------------------------------------------

    private static function installDropin(): void
    {
        // Don't overwrite an existing drop-in that belongs to another plugin.
        if (file_exists(self::dropinDest()) && ! self::dropinIsOurs()) {
            return;
        }

        @copy(self::dropinSource(), self::dropinDest());
    }

    private static function removeDropin(): void
    {
        if (file_exists(self::dropinDest()) && self::dropinIsOurs()) {
            @unlink(self::dropinDest());
        }
    }

    /**
     * Returns true if the installed advanced-cache.php was placed by this plugin.
     */
    private static function dropinIsOurs(): bool
    {
        if (! file_exists(self::dropinDest())) {
            return false;
        }

        $contents = (string) file_get_contents(self::dropinDest());

        return str_contains($contents, 'Performance Toolkit');
    }

    // -------------------------------------------------------------------------
    // wp-config.php helpers
    // -------------------------------------------------------------------------

    private static function enableWpCache(): void
    {
        if (defined('WP_CACHE') && WP_CACHE) {
            return; // Already enabled.
        }

        $config = ABSPATH . 'wp-config.php';

        if (! is_writable($config)) {
            return;
        }

        $contents = file_get_contents($config);

        if ($contents === false) {
            return;
        }

        // Already present (maybe defined as false).
        if (preg_match('/define\s*\(\s*[\'"]WP_CACHE[\'"]/', $contents)) {
            return;
        }

        // Insert before the "That's all, stop editing!" comment.
        $new = preg_replace(
            '/(\\/\\*\\s*That\'s all[^*]*\\*\\/)/i',
            "define( 'WP_CACHE', true ); // Added by Performance Toolkit\n$1",
            $contents
        );

        if ($new !== null && $new !== $contents) {
            file_put_contents($config, $new, LOCK_EX);
        }
    }

    private static function disableWpCache(): void
    {
        $config = ABSPATH . 'wp-config.php';

        if (! is_writable($config)) {
            return;
        }

        $contents = file_get_contents($config);

        if ($contents === false) {
            return;
        }

        $new = preg_replace(
            '/^define\s*\(\s*\'WP_CACHE\'.*\/\/ Added by Performance Toolkit\r?\n/m',
            '',
            $contents
        );

        if ($new !== null && $new !== $contents) {
            file_put_contents($config, $new, LOCK_EX);
        }
    }
}
