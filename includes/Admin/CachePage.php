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
        $options = $this->settings->all();
        $cache_cleared = isset($_GET['ptk_cache_cleared']) && (string) $_GET['ptk_cache_cleared'] === '1';
        ?>
        <section id="ptk-cache" class="ptk-card">
            <h2><?php esc_html_e('Cache', 'performance-toolkit'); ?></h2>

            <?php if ($cache_cleared) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Cache cleared successfully.', 'performance-toolkit'); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('performance_toolkit'); ?>
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[defer_scripts]" value="<?php echo ! empty($options['defer_scripts']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[lazy_load_images]" value="<?php echo ! empty($options['lazy_load_images']) ? '1' : '0'; ?>" />
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
                'page' => $this->slug(),
                'ptk_cache_cleared' => '1',
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }
}
