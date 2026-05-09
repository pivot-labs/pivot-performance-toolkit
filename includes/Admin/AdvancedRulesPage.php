<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class AdvancedRulesPage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function slug(): string
    {
        return 'performance-toolkit-advanced-rules';
    }

    public function menuTitle(): string
    {
        return __('Advanced Rules', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Advanced Rules', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'sliders-horizontal';
    }

    public function renderContent(): void
    {
        $options = $this->settings->all();
        $key     = $this->settings->optionKey();
        ?>
        <section id="ptk-advanced-rules" class="ptk-card">
            <h2><?php esc_html_e('Cache exclusions', 'performance-toolkit'); ?></h2>
            <p style="margin:0 0 16px;color:#646970">
                <?php esc_html_e('Enter URLs or path patterns that should never be cached — one per line. Prefix matching is used by default; add a wildcard (*) for substring patterns.', 'performance-toolkit'); ?>
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php settings_fields('performance_toolkit'); ?>

                <input type="hidden" name="<?php echo esc_attr($key); ?>[enable_page_cache]"  value="<?php echo ! empty($options['enable_page_cache']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[cache_ttl]"           value="<?php echo esc_attr((string) $options['cache_ttl']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[max_cache_size_mb]"   value="<?php echo esc_attr((string) $options['max_cache_size_mb']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[minify_html]"         value="<?php echo ! empty($options['minify_html']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[minify_css]"          value="<?php echo ! empty($options['minify_css']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[minify_external_css]" value="<?php echo ! empty($options['minify_external_css']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[minify_external_css_exclusions]" value="<?php echo esc_attr((string) $options['minify_external_css_exclusions']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[minify_external_js]" value="<?php echo ! empty($options['minify_external_js']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[minify_js]"           value="<?php echo ! empty($options['minify_js']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[defer_scripts]"       value="<?php echo ! empty($options['defer_scripts']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($key); ?>[lazy_load_images]"    value="<?php echo ! empty($options['lazy_load_images']) ? '1' : '0'; ?>" />

                <div class="ptk-field">
                    <label for="ptk-excluded-urls"><strong><?php esc_html_e('Never-cache URLs', 'performance-toolkit'); ?></strong></label>
                    <p><?php esc_html_e('Paths are matched from the start of the URL. Use * for wildcards.', 'performance-toolkit'); ?></p>
                    <textarea
                        id="ptk-excluded-urls"
                        name="<?php echo esc_attr($key); ?>[cache_excluded_urls]"
                        class="ptk-exclusions-textarea"
                        rows="10"
                        placeholder="<?php esc_attr_e("/checkout\n/cart\n/my-account/*\n/wc-api/*", 'performance-toolkit'); ?>"
                        spellcheck="false"
                    ><?php echo esc_textarea((string) $options['cache_excluded_urls']); ?></textarea>
                    <p class="ptk-exclusions-hint">
                        <?php
                        echo wp_kses(
                            __('<strong>Examples:</strong> <code>/checkout</code> excludes all URLs starting with /checkout &nbsp;·&nbsp; <code>/my-account/*</code> uses a wildcard &nbsp;·&nbsp; One entry per line.', 'performance-toolkit'),
                            array(
                                'strong' => array(),
                                'code'   => array(),
                            )
                        );
                        ?>
                    </p>
                </div>

                <?php submit_button(__('Save changes', 'performance-toolkit')); ?>
            </form>
        </section>
        <?php
    }
}

