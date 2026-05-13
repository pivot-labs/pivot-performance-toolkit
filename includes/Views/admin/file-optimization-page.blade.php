<x-card title="{{ __('File Optimization', 'performance-toolkit') }}" id="ptk-file-optimization">
    @if ($settings_updated)
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Settings saved successfully.', 'performance-toolkit') }}</p>
        </div>
    @endif

    <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
        @php
            settings_fields('performance_toolkit');

            echo '<input type="hidden" name="' . esc_attr($option_key) . '[enable_page_cache]" value="' . (!empty($options['enable_page_cache']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[cache_ttl]" value="' . esc_attr((string) $options['cache_ttl']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[max_cache_size_mb]" value="' . esc_attr((string) $options['max_cache_size_mb']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[cache_excluded_urls]" value="' . esc_attr((string) $options['cache_excluded_urls']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[lazy_load_images]" value="' . (!empty($options['lazy_load_images']) ? '1' : '0') . '" />';
        @endphp

        <div class="ptk-field">
            <input type="hidden" name="{{ $option_key }}[defer_scripts]" value="0" />
            <label>
                <input type="checkbox" name="{{ $option_key }}[defer_scripts]" value="1" {{ !empty($options['defer_scripts']) ? 'checked' : '' }} />
                <span>{{ __('Defer frontend scripts', 'performance-toolkit') }}</span>
            </label>
            <p>{{ __('Adds defer to non-critical scripts where possible.', 'performance-toolkit') }}</p>
        </div>

        <div class="ptk-field">
            <input type="hidden" name="{{ $option_key }}[minify_html]" value="0" />
            <label>
                <input type="checkbox" name="{{ $option_key }}[minify_html]" value="1" {{ !empty($options['minify_html']) ? 'checked' : '' }} />
                <span>{{ __('Minify HTML output', 'performance-toolkit') }}</span>
            </label>
            <p>{{ __('Removes non-essential whitespace and safe HTML comments from frontend output.', 'performance-toolkit') }}</p>
        </div>

        <div class="ptk-field">
            <input type="hidden" name="{{ $option_key }}[minify_css]" value="0" />
            <label>
                <input type="checkbox" name="{{ $option_key }}[minify_css]" value="1" {{ !empty($options['minify_css']) ? 'checked' : '' }} />
                <span>{{ __('Minify inline CSS', 'performance-toolkit') }}</span>
            </label>
            <p>{{ __('Minifies inline style blocks in frontend HTML output.', 'performance-toolkit') }}</p>
        </div>

        <div class="ptk-field">
            <input type="hidden" name="{{ $option_key }}[minify_external_css]" value="0" />
            <label>
                <input type="checkbox" name="{{ $option_key }}[minify_external_css]" value="1" {{ !empty($options['minify_external_css']) ? 'checked' : '' }} />
                <span>{{ __('Minify external CSS files', 'performance-toolkit') }}</span>
            </label>
            <p>{{ __('Creates cached minified copies of local enqueued stylesheet files and rewrites their URLs.', 'performance-toolkit') }}</p>

            <label for="ptk-external-css-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                {{ __('External CSS exclusions', 'performance-toolkit') }}
            </label>
            <textarea
                id="ptk-external-css-exclusions"
                name="{{ $option_key }}[minify_external_css_exclusions]"
                class="ptk-exclusions-textarea"
                rows="6"
                placeholder="{{ esc_attr("woocommerce-layout
style.css
/wp-content/themes/your-theme/css/*") }}"
                spellcheck="false"
            >{{ esc_textarea((string) $options['minify_external_css_exclusions']) }}</textarea>
            <p class="ptk-exclusions-hint">
                {!! wp_kses(
                    __('<strong>One rule per line.</strong> You can exclude by stylesheet handle, file name, full path, or wildcard pattern. Examples: <code>woocommerce-layout</code>, <code>style.css</code>, <code>/wp-content/themes/your-theme/css/*</code>.', 'performance-toolkit'),
                    array('strong' => array(), 'code' => array())
                ) !!}
            </p>
        </div>

        <div class="ptk-field">
            <input type="hidden" name="{{ $option_key }}[minify_external_js]" value="0" />
            <label>
                <input type="checkbox" name="{{ $option_key }}[minify_external_js]" value="1" {{ !empty($options['minify_external_js']) ? 'checked' : '' }} />
                <span>{{ __('Minify external JavaScript files', 'performance-toolkit') }}</span>
            </label>
            <p>{{ __('Creates cached minified copies of local enqueued JavaScript files and rewrites their URLs.', 'performance-toolkit') }}</p>

            <label for="ptk-external-js-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                {{ __('External JavaScript exclusions', 'performance-toolkit') }}
            </label>
            <textarea
                id="ptk-external-js-exclusions"
                name="{{ $option_key }}[minify_external_js_exclusions]"
                class="ptk-exclusions-textarea"
                rows="6"
                placeholder="{{ esc_attr("jquery-core
app.js
/wp-content/themes/your-theme/js/*") }}"
                spellcheck="false"
            >{{ esc_textarea((string) $options['minify_external_js_exclusions']) }}</textarea>
            <p class="ptk-exclusions-hint">
                {!! wp_kses(
                    __('<strong>One rule per line.</strong> You can exclude by script handle, file name, full path, or wildcard pattern. Examples: <code>jquery-core</code>, <code>app.js</code>, <code>/wp-content/themes/your-theme/js/*</code>.', 'performance-toolkit'),
                    array('strong' => array(), 'code' => array())
                ) !!}
            </p>
        </div>

        <div class="ptk-field">
            <input type="hidden" name="{{ $option_key }}[minify_js]" value="0" />
            <label>
                <input type="checkbox" name="{{ $option_key }}[minify_js]" value="1" {{ !empty($options['minify_js']) ? 'checked' : '' }} />
                <span>{{ __('Minify inline JavaScript', 'performance-toolkit') }}</span>
            </label>
            <p>{{ __('Minifies inline script blocks in frontend HTML output.', 'performance-toolkit') }}</p>
        </div>

        <div class="ptk-http11-only">
            <h3>{{ __('HTTP/1.1 only: File combination', 'performance-toolkit') }}</h3>
            <p>
                {{ __('Combining CSS/JS files is usually only beneficial on HTTP/1.1 servers. On HTTP/2 and HTTP/3, it often reduces cache efficiency and may hurt real-world performance.', 'performance-toolkit') }}
            </p>

            <div class="ptk-http11-warning">
                <strong>{{ __('Warning:', 'performance-toolkit') }}</strong>
                <span>{{ __('File combination can break dependency order, plugin-specific assets, and conditional loading logic. Use only after testing key pages (home, shop, cart, checkout, account, blog, and landing pages).', 'performance-toolkit') }}</span>
            </div>

            <div class="ptk-field" style="margin-top:14px;">
                <input type="hidden" name="{{ $option_key }}[combine_css]" value="0" />
                <label>
                    <input type="checkbox" name="{{ $option_key }}[combine_css]" value="1" {{ !empty($options['combine_css']) ? 'checked' : '' }} />
                    <span>{{ __('Combine external CSS files', 'performance-toolkit') }}</span>
                </label>
                <p>{{ __('Merge eligible CSS files into fewer requests. Recommended only for HTTP/1.1 environments.', 'performance-toolkit') }}</p>

                <label for="ptk-combine-css-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                    {{ __('CSS combine exclusions', 'performance-toolkit') }}
                </label>
                <textarea
                    id="ptk-combine-css-exclusions"
                    name="{{ $option_key }}[combine_css_exclusions]"
                    class="ptk-exclusions-textarea"
                    rows="5"
                    placeholder="{{ esc_attr("woocommerce-layout
style.css
/wp-content/themes/your-theme/css/*") }}"
                    spellcheck="false"
                >{{ esc_textarea((string) $options['combine_css_exclusions']) }}</textarea>
            </div>

            <div class="ptk-field">
                <input type="hidden" name="{{ $option_key }}[combine_js]" value="0" />
                <label>
                    <input type="checkbox" name="{{ $option_key }}[combine_js]" value="1" {{ !empty($options['combine_js']) ? 'checked' : '' }} />
                    <span>{{ __('Combine external JavaScript files', 'performance-toolkit') }}</span>
                </label>
                <p>{{ __('Merge eligible JS files into fewer requests. Recommended only for HTTP/1.1 environments.', 'performance-toolkit') }}</p>

                <label for="ptk-combine-js-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                    {{ __('JS combine exclusions', 'performance-toolkit') }}
                </label>
                <textarea
                    id="ptk-combine-js-exclusions"
                    name="{{ $option_key }}[combine_js_exclusions]"
                    class="ptk-exclusions-textarea"
                    rows="5"
                    placeholder="{{ esc_attr("jquery-core
app.js
/wp-content/themes/your-theme/js/*") }}"
                    spellcheck="false"
                >{{ esc_textarea((string) $options['combine_js_exclusions']) }}</textarea>
            </div>

            <p class="ptk-http11-note">
                {{ __('Recommendation: keep minification enabled and only enable file combination when your origin truly serves HTTP/1.1 traffic.', 'performance-toolkit') }}
            </p>
        </div>

        @php
            submit_button(__('Save changes', 'performance-toolkit'));
        @endphp
    </form>
</x-card>

