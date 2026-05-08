<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class MediaOptimizationPage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function slug(): string
    {
        return 'performance-toolkit-media-optimization';
    }

    public function menuTitle(): string
    {
        return __('Media Optimization', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Media Optimization', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'image';
    }

    public function renderContent(): void
    {
        $options = $this->settings->all();
        ?>
        <section id="ptk-media-optimization" class="ptk-card">
            <h2><?php esc_html_e('Media Optimization', 'performance-toolkit'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('performance_toolkit'); ?>

                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[enable_page_cache]" value="<?php echo ! empty($options['enable_page_cache']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[cache_ttl]" value="<?php echo esc_attr((string) $options['cache_ttl']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[defer_scripts]" value="<?php echo ! empty($options['defer_scripts']) ? '1' : '0'; ?>" />

                <div class="ptk-field">
                    <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[lazy_load_images]" value="0" />
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
