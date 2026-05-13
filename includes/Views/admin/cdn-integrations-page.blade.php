<x-card :title="__('CDN & Integrations', 'performance-toolkit')" id="ptk-cdn-integrations">
    @if ($settings_updated)
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Settings saved successfully.', 'performance-toolkit') }}</p>
        </div>
    @endif

    @if ($notice !== '')
        <div class="notice {{ $notice === 'success' ? 'notice-success' : 'notice-error' }} is-dismissible">
            <p>{{ $message }}</p>
        </div>
    @endif

    <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
        @php
            settings_fields('performance_toolkit');
        @endphp

        <div class="ptk-field">
            <label for="ptk-cdn-provider">{{ __('Provider', 'performance-toolkit') }}</label>
            <select id="ptk-cdn-provider" name="{{ $option_key }}[cdn_provider]">
                <option value="">{{ __('None', 'performance-toolkit') }}</option>
                <option value="cloudflare" {{ (string) $options['cdn_provider'] === 'cloudflare' ? 'selected' : '' }}>
                    {{ __('Cloudflare', 'performance-toolkit') }}
                </option>
            </select>
        </div>

        <div class="ptk-field">
            <label for="ptk-cloudflare-api-token">{{ __('API token', 'performance-toolkit') }}</label>
            <input
                id="ptk-cloudflare-api-token"
                type="password"
                class="regular-text"
                autocomplete="new-password"
                name="{{ $option_key }}[cloudflare_api_token]"
                value="{{ esc_attr((string) $options['cloudflare_api_token']) }}"
            />
        </div>

        <div class="ptk-field">
            <label for="ptk-cloudflare-zone-id">{{ __('Zone ID', 'performance-toolkit') }}</label>
            <input
                id="ptk-cloudflare-zone-id"
                type="text"
                class="regular-text"
                name="{{ $option_key }}[cloudflare_zone_id]"
                value="{{ esc_attr((string) $options['cloudflare_zone_id']) }}"
            />
        </div>

        <div class="ptk-field">
            <input type="hidden" name="{{ $option_key }}[cloudflare_auto_purge]" value="0" />
            <label>
                <input
                    type="checkbox"
                    name="{{ $option_key }}[cloudflare_auto_purge]"
                    value="1"
                    {{ !empty($options['cloudflare_auto_purge']) ? 'checked' : '' }}
                />
                <span>{{ __('Auto-purge on content update', 'performance-toolkit') }}</span>
            </label>
        </div>

        @php
            submit_button(__('Save changes', 'performance-toolkit'));
        @endphp
    </form>

    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" style="margin-top:10px;">
        <input type="hidden" name="action" value="{{ esc_attr($test_action) }}" />
        @php
            wp_nonce_field('ptk_cloudflare_test');
            submit_button(__('Test connection', 'performance-toolkit'), 'secondary', 'submit', false);
        @endphp
    </form>

    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" style="margin-top:10px;">
        <input type="hidden" name="action" value="{{ esc_attr($purge_action) }}" />
        @php
            wp_nonce_field('ptk_cloudflare_purge');
            submit_button(__('Purge cache', 'performance-toolkit'), 'secondary', 'submit', false);
        @endphp
    </form>
</x-card>

