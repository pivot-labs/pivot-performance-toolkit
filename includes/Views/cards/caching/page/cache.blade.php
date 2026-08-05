<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Cache', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-cache">
    @if ($settings_updated)
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Settings saved successfully.', 'pivot-performance-toolkit') }}</p>
        </div>
    @endif

    @if ($cache_cleared)
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Cache cleared successfully.', 'pivot-performance-toolkit') }}</p>
        </div>
    @endif

    <form id="pivot-performance-toolkit-cache-settings-form" method="post" action="{{ esc_url(admin_url('options.php')) }}">
        @php
            settings_fields('pivot_performance_toolkit');

            // Hidden fields for unchecked checkboxes (WordPress form standard)
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[defer_scripts]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[lazy_load_images]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[cache_excluded_urls]" value="' . esc_attr((string) $options['cache_excluded_urls']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_html]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_css]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_css]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_css_exclusions]" value="' . esc_attr((string) $options['minify_external_css_exclusions']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_js]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_js_exclusions]" value="' . esc_attr((string) $options['minify_external_js_exclusions']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_css]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_css_exclusions]" value="' . esc_attr((string) $options['combine_css_exclusions']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_js]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_js_exclusions]" value="' . esc_attr((string) $options['combine_js_exclusions']) . '" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_js]" value="0" />';
            echo '<input type="hidden" name="' . esc_attr($option_key) . '[enable_page_cache]" value="0" />';
        @endphp

        <div class="pivot-performance-toolkit-field">
            <label>
                <input
                    type="checkbox"
                    name="{{ $option_key }}[enable_page_cache]"
                    value="1"
                    {{ !empty($options['enable_page_cache']) ? 'checked' : '' }}
                />
                <span>{{ __('Enable page cache', 'pivot-performance-toolkit') }}</span>
            </label>
            <p>{{ __('Store and serve cache files for anonymous visitors.', 'pivot-performance-toolkit') }}</p>
        </div>

        <div class="pivot-performance-toolkit-field">
            <label for="pivot-performance-toolkit-cache-ttl">{{ __('Cache TTL (seconds)', 'pivot-performance-toolkit') }}</label>
            <input
                id="pivot-performance-toolkit-cache-ttl"
                type="number"
                min="60"
                step="60"
                name="{{ $option_key }}[cache_ttl]"
                value="{{ esc_attr((string) $options['cache_ttl']) }}"
                class="small-text"
            />
        </div>

        <div class="pivot-performance-toolkit-field">
            <label for="pivot-performance-toolkit-max-cache-size">{{ __('Max cache size (MB)', 'pivot-performance-toolkit') }}</label>
            <input
                id="pivot-performance-toolkit-max-cache-size"
                type="number"
                min="1"
                step="1"
                name="{{ $option_key }}[max_cache_size_mb]"
                value="{{ esc_attr((string) $options['max_cache_size_mb']) }}"
                class="small-text"
            />
            <p>{{ __('When the cache folder exceeds this size the oldest files are pruned automatically.', 'pivot-performance-toolkit') }}</p>

            <div class="pivot-performance-toolkit-cache-usage">
                <div class="pivot-performance-toolkit-cache-usage-bar">
                    <div
                        class="pivot-performance-toolkit-cache-usage-fill {{ $usage_pct >= 90 ? 'is-critical' : ($usage_pct >= 70 ? 'is-warning' : '') }}"
                        style="width: {{ esc_attr((string) $usage_pct) }}%"
                    ></div>
                </div>
                @php
                    $cache_limit_mb_label = number_format_i18n((int) $options['max_cache_size_mb']);
                    $usage_pct_label = number_format_i18n((int) $usage_pct);
                @endphp
                <span class="pivot-performance-toolkit-cache-usage-label">
                    {{ sprintf(
                        /* translators: 1: Used cache size in human-readable units, 2: Configured max cache size in MB, 3: Percentage of cache usage. */
                        __('%1$s of %2$s MB used (%3$s%%)', 'pivot-performance-toolkit'),
                        $cache_size_formatted,
                        $cache_limit_mb_label,
                        $usage_pct_label
                    ) }}
                </span>
            </div>
        </div>

    </form>

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 10px;">
        <button type="submit" form="pivot-performance-toolkit-cache-settings-form" class="button button-primary">
            {{ __('Save changes', 'pivot-performance-toolkit') }}
        </button>

        <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
            <input type="hidden" name="action" value="{{ esc_attr($clear_action) }}" />
            @php
                wp_nonce_field('pivot_performance_toolkit_clear_cache');
                submit_button(__('Clear cache', 'pivot-performance-toolkit'), 'secondary', 'submit', false);
            @endphp
        </form>
    </div>
</x-card>
