<x-card title="{{ __('Cache exclusions', 'performance-toolkit') }}" id="ptk-advanced-rules">
    @if ($settings_updated)
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Settings saved successfully.', 'performance-toolkit') }}</p>
        </div>
    @endif

    <p style="margin:0 0 16px;color:#646970">
        {{ __('Enter URLs or path patterns that should never be cached - one per line. Prefix matching is used by default; add a wildcard (*) for substring patterns.', 'performance-toolkit') }}
    </p>

    <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
        @php
            settings_fields('performance_toolkit');

            echo '<input type="hidden" name="' . esc_attr($option_key) . '[enable_page_cache]" value="' . (!empty($options['enable_page_cache']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[cache_ttl]" value="' . esc_attr((string) $options['cache_ttl']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[max_cache_size_mb]" value="' . esc_attr((string) $options['max_cache_size_mb']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_html]" value="' . (!empty($options['minify_html']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_css]" value="' . (!empty($options['minify_css']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_css]" value="' . (!empty($options['minify_external_css']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_css_exclusions]" value="' . esc_attr((string) $options['minify_external_css_exclusions']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_js]" value="' . (!empty($options['minify_external_js']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_js_exclusions]" value="' . esc_attr((string) $options['minify_external_js_exclusions']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_css]" value="' . (!empty($options['combine_css']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_css_exclusions]" value="' . esc_attr((string) $options['combine_css_exclusions']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_js]" value="' . (!empty($options['combine_js']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_js_exclusions]" value="' . esc_attr((string) $options['combine_js_exclusions']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_js]" value="' . (!empty($options['minify_js']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[defer_scripts]" value="' . (!empty($options['defer_scripts']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[lazy_load_images]" value="' . (!empty($options['lazy_load_images']) ? '1' : '0') . '" />';
        @endphp

        <div class="ptk-field">
            <label for="ptk-excluded-urls"><strong>{{ __('Never-cache URLs', 'performance-toolkit') }}</strong></label>
            <p>{{ __('Paths are matched from the start of the URL. Use * for wildcards.', 'performance-toolkit') }}</p>
            <p>
                <button type="button" class="button-link" id="ptk-add-woo-exclusions">
                    {{ __('Add WooCommerce default exclusions', 'performance-toolkit') }}
                </button>
            </p>
            <textarea
                id="ptk-excluded-urls"
                name="{{ $option_key }}[cache_excluded_urls]"
                class="ptk-exclusions-textarea"
                rows="10"
                placeholder="{{ esc_attr("/checkout\n/cart\n/my-account/*\n/wc-api/*") }}"
                spellcheck="false"
            >{{ esc_textarea((string) $options['cache_excluded_urls']) }}</textarea>
            <p class="ptk-exclusions-hint">
                {!! wp_kses(
                    __('<strong>Examples:</strong> <code>/checkout</code> excludes all URLs starting with /checkout &nbsp;.&nbsp; <code>/my-account/*</code> uses a wildcard &nbsp;.&nbsp; One entry per line.', 'performance-toolkit'),
                    array('strong' => array(), 'code' => array())
                ) !!}
            </p>
        </div>

        <div class="ptk-field">
            <label for="ptk-bypass-cookies"><strong>{{ __('Bypass cache when cookies exist', 'performance-toolkit') }}</strong></label>
            <p>{{ __('One cookie name or wildcard pattern per line. If a request contains any matching cookie, page cache is bypassed.', 'performance-toolkit') }}</p>
            <textarea
                id="ptk-bypass-cookies"
                name="{{ $option_key }}[cache_bypass_cookies]"
                class="ptk-exclusions-textarea"
                rows="6"
                spellcheck="false"
            >{{ esc_textarea((string) $options['cache_bypass_cookies']) }}</textarea>
            <p class="ptk-exclusions-hint">
                {!! wp_kses(
                    __('<strong>Examples:</strong> <code>woocommerce_items_in_cart</code>, <code>woocommerce_cart_hash</code>, <code>wp_woocommerce_session_*</code>.', 'performance-toolkit'),
                    array('strong' => array(), 'code' => array())
                ) !!}
            </p>
        </div>

        @php
            submit_button(__('Save changes', 'performance-toolkit'));
        @endphp
    </form>

    <script>
        (function () {
            const addButton = document.getElementById('ptk-add-woo-exclusions');
            const textarea = document.getElementById('ptk-excluded-urls');
            const defaults = @json($woo_defaults);

            if (!addButton || !textarea || !Array.isArray(defaults)) {
                return;
            }

            addButton.addEventListener('click', function () {
                const existing = textarea.value
                    .split('\n')
                    .map(function (line) {
                        return line.trim();
                    })
                    .filter(function (line) {
                        return line !== '';
                    });

                const normalized = new Set(existing.map(function (line) {
                    return line.toLowerCase();
                }));

                defaults.forEach(function (rule) {
                    if (typeof rule !== 'string') {
                        return;
                    }

                    if (!normalized.has(rule.toLowerCase())) {
                        existing.push(rule);
                        normalized.add(rule.toLowerCase());
                    }
                });

                textarea.value = existing.join('\n');
                textarea.focus();
            });
        }());
    </script>
</x-card>

