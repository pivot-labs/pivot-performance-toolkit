<x-card-standard
        :title="__('Reset to Safe Defaults', 'performance-toolkit')"
        :description="__('Restore the plugin to a safe baseline when you want to roll back aggressive optimization changes or start a fresh round of testing.', 'performance-toolkit')"
        icon="rotate-ccw"
        tone="red"
>
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-900">
        <p class="font-semibold">{{ __('Destructive Action', 'performance-toolkit') }}</p>
        <p>{{ __('This resets cache, optimization, and media settings to recommended defaults. Export your configuration first if you may want to restore it later.', 'performance-toolkit') }}</p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-gray-500">
            @if (!empty($last_settings_exported_at_gmt))
                @php
                    $timestamp = strtotime($last_settings_exported_at_gmt);
                    $formatted = wp_date(__('M j, Y \a\t g:i A', 'performance-toolkit'), $timestamp);
                @endphp
                {{ sprintf(__('Last configuration export: %s.', 'performance-toolkit'), $formatted) }}
            @else
                {{ __('No configuration export yet.', 'performance-toolkit') }}
            @endif
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
                <input type="hidden" name="action" value="{{ esc_attr($reset_to_defaults_action) }}" />
                @php wp_nonce_field('ptk_reset_to_defaults'); @endphp
                @php submit_button(__('Reset to safe defaults', 'performance-toolkit'), 'secondary', 'submit', false, array('style' => 'background:#d63638;border-color:#d63638;color:#fff;')); @endphp
            </form>
        </div>
    </div>
</x-card-standard>