<x-card :title="__('Media Optimization', 'performance-toolkit')" id="ptk-media-optimization">
    @if ($settings_updated)
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Settings saved successfully.', 'performance-toolkit') }}</p>
        </div>
    @endif

    <div class="ptk-field" style="border:1px solid #dcdcde;padding:12px;border-radius:6px;margin-bottom:16px;">
        <strong>{{ __('Detected image optimizers', 'performance-toolkit') }}</strong>
        <p style="margin:6px 0 0;">{{ $optimizer_status }}</p>
        @if ($active_optimizers !== [])
            <p style="margin:6px 0 0;color:#646970;">
                {{ __('Compatibility mode: keep only one lazy-load system enabled to avoid duplicate behavior.', 'performance-toolkit') }}
            </p>
        @endif
        @if ($external_lazyload_on)
            <p style="margin:6px 0 0;color:#b32d2e;">
                {{ sprintf(
                    /* translators: %s: Comma-separated list of plugins/providers currently controlling lazy-load. */
                    __('Lazy-load is currently managed by: %s. Performance Toolkit lazy-load is temporarily disabled to prevent conflicts.', 'performance-toolkit'),
                    implode(', ', $lazyload_providers)
                ) }}
            </p>
        @endif
    </div>

    <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
        @php
            settings_fields('performance_toolkit');

            echo '<input type="hidden" name="' . esc_attr($option_key) . '[enable_page_cache]" value="' . (!empty($options['enable_page_cache']) ? '1' : '0') . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[cache_ttl]" value="' . esc_attr((string) $options['cache_ttl']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[max_cache_size_mb]" value="' . esc_attr((string) $options['max_cache_size_mb']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[cache_excluded_urls]" value="' . esc_attr((string) $options['cache_excluded_urls']) . '" />';
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
        @endphp

        <div class="ptk-field">
            @if ($external_lazyload_on)
                <input type="hidden" name="{{ $option_key }}[lazy_load_images]" value="{{ !empty($options['lazy_load_images']) ? '1' : '0' }}" />
            @else
                <input type="hidden" name="{{ $option_key }}[lazy_load_images]" value="0" />
            @endif
            <label>
                <input
                    type="checkbox"
                    name="{{ $option_key }}[lazy_load_images]"
                    value="1"
                    {{ !empty($options['lazy_load_images']) ? 'checked' : '' }}
                    {{ $external_lazyload_on ? 'disabled' : '' }}
                />
                <span>{{ __('Lazy load content images', 'performance-toolkit') }}</span>
            </label>
            <p>{{ __('Adds loading="lazy" to post content images missing the attribute.', 'performance-toolkit') }}</p>
        </div>

        @php
            submit_button(__('Save changes', 'performance-toolkit'));
        @endphp
    </form>
</x-card>
