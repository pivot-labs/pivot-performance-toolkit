<div class="ptk-col ptk-col--main">
    <x-card :title="__('Cache', 'performance-toolkit')" id="ptk-cache">
        @if ($settings_updated)
            <div class="notice notice-success is-dismissible">
                <p>{{ __('Settings saved successfully.', 'performance-toolkit') }}</p>
            </div>
        @endif

        @if ($cache_cleared)
            <div class="notice notice-success is-dismissible">
                <p>{{ __('Cache cleared successfully.', 'performance-toolkit') }}</p>
            </div>
        @endif

        <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
            @php
                settings_fields('performance_toolkit');

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

            <div class="ptk-field">
                <label>
                    <input
                        type="checkbox"
                        name="{{ $option_key }}[enable_page_cache]"
                        value="1"
                        {{ !empty($options['enable_page_cache']) ? 'checked' : '' }}
                    />
                    <span>{{ __('Enable page cache', 'performance-toolkit') }}</span>
                </label>
                <p>{{ __('Store and serve cache files for anonymous visitors.', 'performance-toolkit') }}</p>
            </div>

            <div class="ptk-field">
                <label for="ptk-cache-ttl">{{ __('Cache TTL (seconds)', 'performance-toolkit') }}</label>
                <input
                    id="ptk-cache-ttl"
                    type="number"
                    min="60"
                    step="60"
                    name="{{ $option_key }}[cache_ttl]"
                    value="{{ esc_attr((string) $options['cache_ttl']) }}"
                    class="small-text"
                />
            </div>

            <div class="ptk-field">
                <label for="ptk-max-cache-size">{{ __('Max cache size (MB)', 'performance-toolkit') }}</label>
                <input
                    id="ptk-max-cache-size"
                    type="number"
                    min="1"
                    step="1"
                    name="{{ $option_key }}[max_cache_size_mb]"
                    value="{{ esc_attr((string) $options['max_cache_size_mb']) }}"
                    class="small-text"
                />
                <p>{{ __('When the cache folder exceeds this size the oldest files are pruned automatically.', 'performance-toolkit') }}</p>

                <div class="ptk-cache-usage">
                    <div class="ptk-cache-usage-bar">
                        <div
                            class="ptk-cache-usage-fill {{ $usage_pct >= 90 ? 'is-critical' : ($usage_pct >= 70 ? 'is-warning' : '') }}"
                            style="width: {{ esc_attr((string) $usage_pct) }}%"
                        ></div>
                    </div>
                    <span class="ptk-cache-usage-label">
                        {{ $cache_size_formatted }} of {{ $options['max_cache_size_mb'] }} MB used ({{ $usage_pct }}%)
                    </span>
                </div>
            </div>

            @php
                submit_button(__('Save changes', 'performance-toolkit'));
            @endphp
        </form>

        <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" style="margin-top: 10px;">
            <input type="hidden" name="action" value="{{ esc_attr($clear_action) }}" />
            @php
                wp_nonce_field('ptk_clear_cache');
                submit_button(__('Clear cache', 'performance-toolkit'), 'secondary', 'submit', false);
            @endphp
        </form>
    </x-card>
</div>

<div class="ptk-col ptk-col--sidebar">
    <section class="ptk-card ptk-object-cache-card">
        <h2>{{ __('Object cache', 'performance-toolkit') }}</h2>
        <p class="ptk-object-cache-description">
            {{ __('Stores database query results and runtime objects in memory to reduce database load.', 'performance-toolkit') }}
        </p>

        <div class="ptk-object-cache-stats">
            <div class="ptk-stat">
                <span class="ptk-stat-label">{{ __('Status', 'performance-toolkit') }}</span>
                <strong class="{{ $object_cache['active'] ? 'ptk-object-cache-active' : 'ptk-object-cache-inactive' }}">
                    {{ $object_cache['status_label'] }}
                </strong>
            </div>
            <div class="ptk-stat">
                <span class="ptk-stat-label">{{ __('Provider', 'performance-toolkit') }}</span>
                <strong>{{ $object_cache['provider'] }}</strong>
            </div>
            <div class="ptk-stat">
                <span class="ptk-stat-label">{{ __('Drop-in', 'performance-toolkit') }}</span>
                <strong>{{ $object_cache['dropin_label'] }}</strong>
            </div>
            <div class="ptk-stat">
                <span class="ptk-stat-label">{{ __('Size', 'performance-toolkit') }}</span>
                <strong>{{ $object_cache['size_formatted'] }}</strong>
            </div>
        </div>

        @if ($object_cache['active'])
            <div class="notice notice-success inline ptk-object-cache-notice">
                <p>{{ __('Object cache drop-in detected.', 'performance-toolkit') }}</p>
            </div>
        @else
            <div class="notice notice-warning inline ptk-object-cache-notice">
                <p>{{ __('No object cache drop-in detected.', 'performance-toolkit') }}</p>
            </div>
        @endif

        <p class="ptk-object-cache-note">
            {{ __('Future versions can add Redis/Memcached controls and metrics here.', 'performance-toolkit') }}
        </p>
    </section>
</div>
