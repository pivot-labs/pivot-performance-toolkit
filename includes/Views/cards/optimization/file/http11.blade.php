<x-card :title="__('HTTP/1.1 File Combination', 'performance-toolkit')" id="ptk-file-optimization-http11">
    <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
        @php
            settings_fields('performance_toolkit');
        @endphp

        <div class="ptk-http11-only">
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
            submit_button(__('Save HTTP/1.1 settings', 'performance-toolkit'));
        @endphp
    </form>
</x-card>

