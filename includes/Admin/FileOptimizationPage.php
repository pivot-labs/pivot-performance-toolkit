<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class FileOptimizationPage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function slug(): string
    {
        return 'performance-toolkit-file-optimization';
    }

    public function menuTitle(): string
    {
        return __('File Optimization', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit File Optimization', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'dashicons-media-code';
    }

    public function renderContent(): void
    {
        $options = $this->settings->all();
        ?>
        <section id="ptk-file-optimization" class="ptk-card">
            <h2><?php esc_html_e('File Optimization', 'performance-toolkit'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php settings_fields('performance_toolkit'); ?>

                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[enable_page_cache]" value="<?php echo ! empty($options['enable_page_cache']) ? '1' : '0'; ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[cache_ttl]" value="<?php echo esc_attr((string) $options['cache_ttl']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[max_cache_size_mb]" value="<?php echo esc_attr((string) $options['max_cache_size_mb']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[cache_excluded_urls]" value="<?php echo esc_attr((string) $options['cache_excluded_urls']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[lazy_load_images]" value="<?php echo ! empty($options['lazy_load_images']) ? '1' : '0'; ?>" />

                <div class="ptk-field">
                    <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[defer_scripts]" value="0" />
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[defer_scripts]" value="1" <?php checked((bool) $options['defer_scripts']); ?> />
                        <span><?php esc_html_e('Defer frontend scripts', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Adds defer to non-critical scripts where possible.', 'performance-toolkit'); ?></p>
                </div>

                <div class="ptk-field">
                    <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_html]" value="0" />
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_html]" value="1" <?php checked((bool) $options['minify_html']); ?> />
                        <span><?php esc_html_e('Minify HTML output', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Removes non-essential whitespace and safe HTML comments from frontend output.', 'performance-toolkit'); ?></p>
                </div>

                <div class="ptk-field">
                    <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_css]" value="0" />
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_css]" value="1" <?php checked((bool) $options['minify_css']); ?> />
                        <span><?php esc_html_e('Minify inline CSS', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Minifies inline style blocks in frontend HTML output.', 'performance-toolkit'); ?></p>
                </div>

                <div class="ptk-field">
                    <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_css]" value="0" />
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_css]" value="1" <?php checked((bool) $options['minify_external_css']); ?> />
                        <span><?php esc_html_e('Minify external CSS files', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Creates cached minified copies of local enqueued stylesheet files and rewrites their URLs.', 'performance-toolkit'); ?></p>

                    <label for="ptk-external-css-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                        <?php esc_html_e('External CSS exclusions', 'performance-toolkit'); ?>
                    </label>
                    <textarea
                        id="ptk-external-css-exclusions"
                        name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_css_exclusions]"
                        class="ptk-exclusions-textarea"
                        rows="6"
                        placeholder="<?php esc_attr_e("woocommerce-layout\nstyle.css\n/wp-content/themes/your-theme/css/*", 'performance-toolkit'); ?>"
                        spellcheck="false"
                    ><?php echo esc_textarea((string) $options['minify_external_css_exclusions']); ?></textarea>
                    <p class="ptk-exclusions-hint">
                        <?php
                        echo wp_kses(
                            __('<strong>One rule per line.</strong> You can exclude by stylesheet handle, file name, full path, or wildcard pattern. Examples: <code>woocommerce-layout</code>, <code>style.css</code>, <code>/wp-content/themes/your-theme/css/*</code>.', 'performance-toolkit'),
                            array(
                                'strong' => array(),
                                'code'   => array(),
                            )
                        );
                        ?>
                    </p>
                </div>

                <div class="ptk-field">
                    <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_js]" value="0" />
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_js]" value="1" <?php checked((bool) $options['minify_external_js']); ?> />
                        <span><?php esc_html_e('Minify external JavaScript files', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Creates cached minified copies of local enqueued JavaScript files and rewrites their URLs.', 'performance-toolkit'); ?></p>

                    <label for="ptk-external-js-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                        <?php esc_html_e('External JavaScript exclusions', 'performance-toolkit'); ?>
                    </label>
                    <textarea
                        id="ptk-external-js-exclusions"
                        name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_external_js_exclusions]"
                        class="ptk-exclusions-textarea"
                        rows="6"
                        placeholder="<?php esc_attr_e("jquery-core\napp.js\n/wp-content/themes/your-theme/js/*", 'performance-toolkit'); ?>"
                        spellcheck="false"
                    ><?php echo esc_textarea((string) $options['minify_external_js_exclusions']); ?></textarea>
                    <p class="ptk-exclusions-hint">
                        <?php
                        echo wp_kses(
                            __('<strong>One rule per line.</strong> You can exclude by script handle, file name, full path, or wildcard pattern. Examples: <code>jquery-core</code>, <code>app.js</code>, <code>/wp-content/themes/your-theme/js/*</code>.', 'performance-toolkit'),
                            array(
                                'strong' => array(),
                                'code'   => array(),
                            )
                        );
                        ?>
                    </p>
                </div>

                <div class="ptk-field">
                    <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_js]" value="0" />
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[minify_js]" value="1" <?php checked((bool) $options['minify_js']); ?> />
                        <span><?php esc_html_e('Minify inline JavaScript', 'performance-toolkit'); ?></span>
                    </label>
                    <p><?php esc_html_e('Minifies inline script blocks in frontend HTML output.', 'performance-toolkit'); ?></p>
                </div>

                <div class="ptk-http11-only">
                    <h3><?php esc_html_e('HTTP/1.1 only: File combination', 'performance-toolkit'); ?></h3>
                    <p>
                        <?php esc_html_e('Combining CSS/JS files is usually only beneficial on HTTP/1.1 servers. On HTTP/2 and HTTP/3, it often reduces cache efficiency and may hurt real-world performance.', 'performance-toolkit'); ?>
                    </p>

                    <div class="ptk-http11-warning">
                        <strong><?php esc_html_e('Warning:', 'performance-toolkit'); ?></strong>
                        <span><?php esc_html_e('File combination can break dependency order, plugin-specific assets, and conditional loading logic. Use only after testing key pages (home, shop, cart, checkout, account, blog, and landing pages).', 'performance-toolkit'); ?></span>
                    </div>

                    <div class="ptk-field" style="margin-top:14px;">
                        <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_css]" value="0" />
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_css]" value="1" <?php checked((bool) $options['combine_css']); ?> />
                            <span><?php esc_html_e('Combine external CSS files', 'performance-toolkit'); ?></span>
                        </label>
                        <p><?php esc_html_e('Merge eligible CSS files into fewer requests. Recommended only for HTTP/1.1 environments.', 'performance-toolkit'); ?></p>

                        <label for="ptk-combine-css-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                            <?php esc_html_e('CSS combine exclusions', 'performance-toolkit'); ?>
                        </label>
                        <textarea
                            id="ptk-combine-css-exclusions"
                            name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_css_exclusions]"
                            class="ptk-exclusions-textarea"
                            rows="5"
                            placeholder="<?php esc_attr_e("woocommerce-layout\nstyle.css\n/wp-content/themes/your-theme/css/*", 'performance-toolkit'); ?>"
                            spellcheck="false"
                        ><?php echo esc_textarea((string) $options['combine_css_exclusions']); ?></textarea>
                    </div>

                    <div class="ptk-field">
                        <input type="hidden" name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_js]" value="0" />
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_js]" value="1" <?php checked((bool) $options['combine_js']); ?> />
                            <span><?php esc_html_e('Combine external JavaScript files', 'performance-toolkit'); ?></span>
                        </label>
                        <p><?php esc_html_e('Merge eligible JS files into fewer requests. Recommended only for HTTP/1.1 environments.', 'performance-toolkit'); ?></p>

                        <label for="ptk-combine-js-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                            <?php esc_html_e('JS combine exclusions', 'performance-toolkit'); ?>
                        </label>
                        <textarea
                            id="ptk-combine-js-exclusions"
                            name="<?php echo esc_attr($this->settings->optionKey()); ?>[combine_js_exclusions]"
                            class="ptk-exclusions-textarea"
                            rows="5"
                            placeholder="<?php esc_attr_e("jquery-core\napp.js\n/wp-content/themes/your-theme/js/*", 'performance-toolkit'); ?>"
                            spellcheck="false"
                        ><?php echo esc_textarea((string) $options['combine_js_exclusions']); ?></textarea>
                    </div>

                    <p class="ptk-http11-note">
                        <?php esc_html_e('Recommendation: keep minification enabled and only enable file combination when your origin truly serves HTTP/1.1 traffic.', 'performance-toolkit'); ?>
                    </p>
                </div>

                <?php submit_button(__('Save changes', 'performance-toolkit')); ?>
            </form>
        </section>
        <?php
    }
}

