<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Media\ImageOptimizerDetector;

final class MediaOptimizationPage implements AdminPageInterface
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
        $options             = $this->settings->all();
        $active_optimizers   = $this->optimizer_detector->activeOptimizers();
        $lazyload_providers  = $this->optimizer_detector->activeLazyLoadProviders();
        $external_lazyload_on = $lazyload_providers !== array();
        $optimizer_status    = $active_optimizers === array()
            ? __('None detected', 'performance-toolkit')
            : implode(', ', $active_optimizers);
        ?>
        <section id="ptk-media-optimization" class="ptk-card">
            <h2><?php esc_html_e('Media Optimization', 'performance-toolkit'); ?></h2>

            <div class="ptk-field" style="border:1px solid #dcdcde;padding:12px;border-radius:6px;margin-bottom:16px;">
                <strong><?php esc_html_e('Detected image optimizers', 'performance-toolkit'); ?></strong>
                <p style="margin:6px 0 0;"><?php echo esc_html($optimizer_status); ?></p>
                <?php if ($active_optimizers !== array()) : ?>
                    <p style="margin:6px 0 0;color:#646970;">
                        <?php esc_html_e('Compatibility mode: keep only one lazy-load system enabled to avoid duplicate behavior.', 'performance-toolkit'); ?>
                    </p>
                <?php endif; ?>

                <?php if ($external_lazyload_on) : ?>
                    <p style="margin:6px 0 0;color:#b32d2e;">
                        <?php
                        printf(
                            /* translators: %s: plugin names */
                            esc_html__('Lazy-load is currently managed by: %s. Performance Toolkit lazy-load is temporarily disabled to prevent conflicts.', 'performance-toolkit'),
                            esc_html(implode(', ', $lazyload_providers))
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php settings_fields('performance_toolkit'); ?>

                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[enable_page_cache]" value="<?php echo ! empty($options['enable_page_cache']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[cache_ttl]" value="<?php echo esc_attr((string) $options['cache_ttl']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[max_cache_size_mb]" value="<?php echo esc_attr((string) $options['max_cache_size_mb']); ?>" />
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
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[defer_scripts]" value="<?php echo ! empty($options['defer_scripts']) ? '1' : '0'; ?>" />

                <div class="ptk-field">
                    <?php if ($external_lazyload_on) : ?>
                        <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[lazy_load_images]" value="<?php echo ! empty($options['lazy_load_images']) ? '1' : '0'; ?>" />
                    <?php else : ?>
                        <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[lazy_load_images]" value="0" />
                    <?php endif; ?>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[lazy_load_images]" value="1" <?php checked((bool) $options['lazy_load_images']); ?> <?php disabled($external_lazyload_on); ?> />
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
