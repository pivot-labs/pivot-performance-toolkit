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

    <form method="post" action="{{ esc_url(admin_url('options.php')) }}" class="ptk-form-aligned">
        @php
            settings_fields('performance_toolkit');

            $provider_logos = array(
                'cloudflare' => esc_url(PERFORMANCE_TOOLKIT_URL . 'assets/img/providers/cloudflare-svgrepo-com.svg'),
            );
        @endphp

        <div class="ptk-field ptk-form-row">
            <label class="ptk-form-label" for="ptk-cdn-provider-button">{{ __('Provider', 'performance-toolkit') }}</label>
            <div class="ptk-form-control">
                <x-icon-select
                    name="{{ $option_key }}[cdn_provider]"
                    id="ptk-cdn-provider"
                    :value="(string) $options['cdn_provider']"
                    :options="[
                        ['value' => '',           'label' => __('None', 'performance-toolkit'),       'logo' => null],
                        ['value' => 'cloudflare', 'label' => __('Cloudflare', 'performance-toolkit'), 'logo' => $provider_logos['cloudflare']],
                    ]"
                />
            </div>
        </div>

        <div class="ptk-field ptk-form-row">
            <label class="ptk-form-label" for="ptk-cloudflare-api-token">{{ __('API token', 'performance-toolkit') }}</label>
            <div class="ptk-form-control">
                <input
                    id="ptk-cloudflare-api-token"
                    type="password"
                    class="regular-text"
                    autocomplete="new-password"
                    name="{{ $option_key }}[cloudflare_api_token]"
                    value="{{ esc_attr((string) $options['cloudflare_api_token']) }}"
                />
                <p class="ptk-field-help">{{ __('API token with Zone:Read and Purge Cache permissions.', 'performance-toolkit') }}</p>
            </div>
        </div>

        <div class="ptk-field ptk-form-row">
            <label class="ptk-form-label" for="ptk-cloudflare-zone-id">{{ __('Zone ID', 'performance-toolkit') }}</label>
            <div class="ptk-form-control">
                <input
                    id="ptk-cloudflare-zone-id"
                    type="text"
                    class="regular-text"
                    name="{{ $option_key }}[cloudflare_zone_id]"
                    value="{{ esc_attr((string) $options['cloudflare_zone_id']) }}"
                />
                <p class="ptk-field-help">{{ __('Find your Zone ID in the Cloudflare dashboard.', 'performance-toolkit') }}</p>
            </div>
        </div>

        <div class="ptk-field ptk-form-row ptk-form-row--full">
            <input  type="hidden" name="{{ $option_key }}[cloudflare_auto_purge]" value="0" />
            <div class="ptk-form-control">
                <label class="ptk-form-check" for="ptk-cloudflare-auto-purge">
                    <input
                        id="ptk-cloudflare-auto-purge"
                        type="checkbox"
                        style="margin: 0 !important"
                        name="{{ $option_key }}[cloudflare_auto_purge]"
                        value="1"
                        {{ !empty($options['cloudflare_auto_purge']) ? 'checked' : '' }}
                    />
                    <span class="ptk-form-check-text">
                        <span class="ptk-form-check-title">{{ __('Auto-purge on content update', 'performance-toolkit') }}</span>
                        <span class="ptk-form-check-description">{{ __('Automatically purge CDN cache when posts, pages or media are updated.', 'performance-toolkit') }}</span>
                    </span>
                </label>
            </div>
        </div>

        @php
            submit_button(__('Save changes', 'performance-toolkit'));
        @endphp
    </form>
</x-card>


