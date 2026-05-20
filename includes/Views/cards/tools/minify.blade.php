{{-- Minified CSS/JS cache --}}
<x-card-split
        :title="__('Minified CSS/JS Cache', 'performance-toolkit')"
        :description="__('This will delete all generated minified CSS and JavaScript files.',
                           'performance-toolkit')"
        icon="dashicons-media-code"
        tone="emerald"
>
    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-600">

        <p class="font-semibold text-gray-900">{{ __('Current status', 'performance-toolkit') }}</p>
        @php
            printf(
                /* translators: 1: file count, 2: formatted size */
                esc_html__('%1$d file(s), %2$s total.', 'performance-toolkit'),
                esc_html((string) $stats['count']),
                esc_html($stats['size_formatted'])
            );
        @endphp

<!--
        <p>{{ __('Page cache is enabled and serving 87% of anonymous requests from disk cache. Last cache clear was 14 minutes ago.', 'performance-toolkit') }}</p>
-->
    </div>

    <x-slot:actions>

        <div class="space-y-3">
            <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                {{ __('Recommended: clear the cache after changing minification, defer, or CDN settings.', 'performance-toolkit') }}
            </div>

            <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
                <input type="hidden" name="action" value="{{ esc_attr($clear_minified_action) }}" />
                @php wp_nonce_field('ptk_clear_minified_assets'); @endphp
                @php submit_button(__('Clear minified CSS/JS cache', 'performance-toolkit'), 'primary', 'submit', false); @endphp
            </form>
        </div>




    </x-slot:actions>
</x-card-split>