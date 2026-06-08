<x-card-standard
    :title="__('Website Profile', 'performance-toolkit')"
    :description="__('Choose the profile that best matches your site so upcoming presets and recommendations can align with your use case.', 'performance-toolkit')"
    icon="dashicons-admin-settings"
    tone="blue"
>
    @php
        $recommendation = $website_profile_recommendation ?? array();
        $recommended_label = is_array($recommendation) ? (string) ($recommendation['label'] ?? '') : '';
        $signals = is_array($recommendation) && isset($recommendation['signals']) && is_array($recommendation['signals'])
            ? $recommendation['signals']
            : array();
    @endphp

    @if ($recommended_label !== '')
        <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900">
            <p class="font-semibold">{{ __('Recommended profile', 'performance-toolkit') }}: {{ esc_html($recommended_label) }}</p>
            @if (!empty($signals))
                <p class="mt-1">{{ sprintf(
                    /* translators: %s: comma separated detected plugin names */
                    __('Detected signals: %s', 'performance-toolkit'),
                    esc_html(implode(', ', $signals))
                ) }}</p>
            @endif
            <p class="mt-1 text-xs text-blue-800">{{ __('This is a recommendation only and does not automatically change your selected profile.', 'performance-toolkit') }}</p>
        </div>
    @endif

    <div id="ptk-website-profile-notice"></div>

    <form method="post" action="{{ esc_url(admin_url('admin-ajax.php')) }}"
          data-ajax-action-form
          data-ajax-notice-target="#ptk-website-profile-notice"
          class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        @php wp_nonce_field('ptk_set_website_profile'); @endphp
        <input type="hidden" name="action" value="{{ esc_attr($website_profile_action) }}" />

        <div class="min-w-0 flex-1">
            <label for="ptk-website-profile" class="mb-2 block text-sm font-medium text-gray-900">
                {{ __('Site type', 'performance-toolkit') }}
            </label>
            <select id="ptk-website-profile" name="ptk_website_profile" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700">
                @foreach ($website_profile_options as $profile_value => $profile_label)
                    <option value="{{ esc_attr($profile_value) }}" {{ $website_profile === $profile_value ? 'selected' : '' }}>
                        {{ esc_html($profile_label) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="sm:shrink-0">
            @php submit_button(__('Save profile', 'performance-toolkit'), 'secondary', 'submit', false); @endphp
        </div>
    </form>
</x-card-standard>
