<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class CachePage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
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
        ?>
        <section id="ptk-cache" class="ptk-card">
            <h2><?php esc_html_e('Cache', 'performance-toolkit'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('performance_toolkit'); ?>
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
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[defer_scripts]" value="1" <?php checked((bool) $options['defer_scripts']); ?> />
                        <span><?php esc_html_e('Defer frontend scripts', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Adds defer to non-critical scripts where possible.', 'performance-toolkit'); ?></p>
                </div>

                <div class="ptk-field">
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[lazy_load_images]" value="1" <?php checked((bool) $options['lazy_load_images']); ?> />
                        <span><?php esc_html_e('Lazy load content images', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Adds loading="lazy" to post content images missing the attribute.', 'performance-toolkit'); ?></p>
                </div>

                <?php submit_button(__('Save changes', 'performance-toolkit')); ?>
            </form>
        </section>
        <?php
    }
}

