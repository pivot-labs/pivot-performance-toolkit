<x-card :title="__('CDN & Integrations', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-cdn-integrations">
    @if ($settings_updated)
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Settings saved successfully.', 'pivot-performance-toolkit') }}</p>
        </div>
    @endif

    @if ($notice !== '')
        <div class="notice {{ $notice === 'success' ? 'notice-success' : 'notice-error' }} is-dismissible">
            <p>{{ $message }}</p>
        </div>
    @endif

    <form method="post" action="{{ esc_url(admin_url('options.php')) }}" class="pivot-performance-toolkit-form-aligned">
        @php
            settings_fields('pivot_performance_toolkit');

            $provider_logos = array(
                'cloudflare' => esc_url(PIVOT_PERFORMANCE_TOOLKIT_URL . 'src/img/providers/cloudflare-svgrepo-com.svg'),
            );
        @endphp

        <div class="pivot-performance-toolkit-field pivot-performance-toolkit-form-row">
            <label class="pivot-performance-toolkit-form-label" for="pivot-performance-toolkit-cdn-provider-button">{{ __('Provider', 'pivot-performance-toolkit') }}</label>
            <div class="pivot-performance-toolkit-form-control">
                <x-icon-select
                    name="{{ $option_key }}[cdn_provider]"
                    id="pivot-performance-toolkit-cdn-provider"
                    :value="(string) $options['cdn_provider']"
                    :options="[
                        ['value' => '',           'label' => __('None', 'pivot-performance-toolkit'),       'logo' => null],
                        ['value' => 'cloudflare', 'label' => __('Cloudflare', 'pivot-performance-toolkit'), 'logo' => $provider_logos['cloudflare']],
                    ]"
                />
            </div>
        </div>

        <div class="pivot-performance-toolkit-field pivot-performance-toolkit-form-row">
            <label class="pivot-performance-toolkit-form-label" for="pivot-performance-toolkit-cloudflare-api-token">{{ __('API token', 'pivot-performance-toolkit') }}</label>
            <div class="pivot-performance-toolkit-form-control">
                <input
                    id="pivot-performance-toolkit-cloudflare-api-token"
                    type="password"
                    class="regular-text"
                    autocomplete="new-password"
                    name="{{ $option_key }}[cloudflare_api_token]"
                    value="{{ esc_attr((string) $options['cloudflare_api_token']) }}"
                />
                <p class="pivot-performance-toolkit-field-help">{{ __('API token with Zone:Read and Purge Cache permissions.', 'pivot-performance-toolkit') }}</p>
            </div>
        </div>

        <div class="pivot-performance-toolkit-field pivot-performance-toolkit-form-row">
            <label class="pivot-performance-toolkit-form-label" for="pivot-performance-toolkit-cloudflare-zone-id">{{ __('Zone ID', 'pivot-performance-toolkit') }}</label>
            <div class="pivot-performance-toolkit-form-control">
                <input
                    id="pivot-performance-toolkit-cloudflare-zone-id"
                    type="text"
                    class="regular-text"
                    name="{{ $option_key }}[cloudflare_zone_id]"
                    value="{{ esc_attr((string) $options['cloudflare_zone_id']) }}"
                />
                <p class="pivot-performance-toolkit-field-help">{{ __('Find your Zone ID in the Cloudflare dashboard.', 'pivot-performance-toolkit') }}</p>
            </div>
        </div>

        <div class="pivot-performance-toolkit-field pivot-performance-toolkit-form-row pivot-performance-toolkit-form-row--full">
            <input  type="hidden" name="{{ $option_key }}[cloudflare_auto_purge]" value="0" />
            <div class="pivot-performance-toolkit-form-control">
                <label class="pivot-performance-toolkit-form-check" for="pivot-performance-toolkit-cloudflare-auto-purge">
                    <input
                        id="pivot-performance-toolkit-cloudflare-auto-purge"
                        type="checkbox"
                        style="margin: 0 !important"
                        name="{{ $option_key }}[cloudflare_auto_purge]"
                        value="1"
                        {{ !empty($options['cloudflare_auto_purge']) ? 'checked' : '' }}
                    />
                    <span class="pivot-performance-toolkit-form-check-text">
                        <span class="pivot-performance-toolkit-form-check-title">{{ __('Auto-purge on content update', 'pivot-performance-toolkit') }}</span>
                        <span class="pivot-performance-toolkit-form-check-description">{{ __('Automatically purge CDN cache when posts, pages or media are updated.', 'pivot-performance-toolkit') }}</span>
                    </span>
                </label>
            </div>
        </div>

        @php
            submit_button(__('Save changes', 'pivot-performance-toolkit'));
        @endphp
    </form>
</x-card>


