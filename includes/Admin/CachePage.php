<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class CachePage implements AdminPageInterface
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

    public function renderContent(): void
    {
        $options       = $this->settings->all();
        $cache_cleared = isset($_GET['ptk_cache_cleared']) && (string) $_GET['ptk_cache_cleared'] === '1';
        $cache_dir     = WP_CONTENT_DIR . '/cache/performance-toolkit';
        $current_size  = $this->getCacheDirSize($cache_dir);
        $max_bytes     = (int) $options['max_cache_size_mb'] * 1048576;
        $usage_pct     = $max_bytes > 0 ? min(100, (int) round($current_size / $max_bytes * 100)) : 0;
        ?>
        <section id="ptk-cache" class="ptk-card">
            <h2><?php esc_html_e('Cache', 'performance-toolkit'); ?></h2>

            <?php if ($cache_cleared) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Cache cleared successfully.', 'performance-toolkit'); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php settings_fields('performance_toolkit'); ?>
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[defer_scripts]" value="<?php echo ! empty($options['defer_scripts']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[lazy_load_images]" value="<?php echo ! empty($options['lazy_load_images']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[cache_excluded_urls]" value="<?php echo esc_attr((string) $options['cache_excluded_urls']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_html]" value="<?php echo ! empty($options['minify_html']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_css]" value="<?php echo ! empty($options['minify_css']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_css]" value="<?php echo ! empty($options['minify_external_css']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_css_exclusions]" value="<?php echo esc_attr((string) $options['minify_external_css_exclusions']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_js]" value="<?php echo ! empty($options['minify_external_js']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_js_exclusions]" value="<?php echo esc_attr((string) $options['minify_external_js_exclusions']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_css]" value="<?php echo ! empty($options['combine_css']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_css_exclusions]" value="<?php echo esc_attr((string) $options['combine_css_exclusions']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_js]" value="<?php echo ! empty($options['combine_js']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_js_exclusions]" value="<?php echo esc_attr((string) $options['combine_js_exclusions']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_js]" value="<?php echo ! empty($options['minify_js']) ? '1' : '0'; ?>" />
                <div class="ptk-field">
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[enable_page_cache]" value="1" <?php checked((bool) $options['enable_page_cache']); ?> />
                        <span><?php esc_html_e('Enable page cache', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Store and serve cache files for anonymous visitors.', 'performance-toolkit'); ?></p>
                </div>

                <div class="ptk-field">
                    <label for="ptk-cache-ttl"><?php esc_html_e('Cache TTL (seconds)', 'performance-toolkit'); ?></label>
                    <input id="ptk-cache-ttl" type="number" min="60" step="60" name="<?php echo esc_attr($this->settings->optionKey()); ?>[cache_ttl]" value="<?php echo esc_attr((string) $options['cache_ttl']); ?>" class="small-text" />
                </div>

                <div class="ptk-field">
                    <label for="ptk-max-cache-size"><?php esc_html_e('Max cache size (MB)', 'performance-toolkit'); ?></label>
                    <input id="ptk-max-cache-size" type="number" min="1" step="1" name="<?php echo esc_attr($this->settings->optionKey()); ?>[max_cache_size_mb]" value="<?php echo esc_attr((string) $options['max_cache_size_mb']); ?>" class="small-text" />
                    <p><?php esc_html_e('When the cache folder exceeds this size the oldest files are pruned automatically.', 'performance-toolkit'); ?></p>
                    <div class="ptk-cache-usage">
                        <div class="ptk-cache-usage-bar">
                            <div class="ptk-cache-usage-fill <?php echo $usage_pct >= 90 ? 'is-critical' : ($usage_pct >= 70 ? 'is-warning' : ''); ?>" style="width:<?php echo esc_attr((string) $usage_pct); ?>%"></div>
                        </div>
                        <span class="ptk-cache-usage-label">
                            <?php
                            printf(
                                /* translators: 1: current size formatted, 2: max size in MB, 3: percentage */
                                esc_html__('%1$s of %2$s MB used (%3$s%%)', 'performance-toolkit'),
                                esc_html(self::formatBytes($current_size)),
                                esc_html((string) $options['max_cache_size_mb']),
                                esc_html((string) $usage_pct)
                            );
                            ?>
                        </span>
                    </div>
                </div>

                <?php submit_button(__('Save changes', 'performance-toolkit')); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px;">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::CLEAR_ACTION); ?>" />
                <?php wp_nonce_field('ptk_clear_cache'); ?>
                <?php submit_button(__('Clear cache', 'performance-toolkit'), 'secondary', 'submit', false); ?>
            </form>
        </section>
        <?php
    }

    public function handleClearCache(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_clear_cache');

        $cache_dir = WP_CONTENT_DIR . '/cache/performance-toolkit';

        foreach (glob($cache_dir . '/*.html') ?: array() as $file_path) {
            @unlink($file_path);
        }

        $redirect_url = add_query_arg(
            array(
                'page'             => $this->slug(),
                'ptk_cache_cleared' => '1',
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getCacheDirSize(string $dir): int
    {
        $total = 0;

        foreach (glob($dir . '/*.html') ?: array() as $file) {
            $total += (int) @filesize($file);
        }

        return $total;
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
}
