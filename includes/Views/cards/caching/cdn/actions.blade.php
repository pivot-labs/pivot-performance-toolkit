<x-card :title="__('CDN Actions', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-cdn-actions">
    @php
        ob_start();
    @endphp
    <form method="post" action="{{ esc_url(admin_url('admin-ajax.php')) }}" class="m-0" data-ajax-action-form data-ajax-notice-target="#pivot-performance-toolkit-cdn-actions .pivot-performance-toolkit-card-notices">
        <input type="hidden" name="action" value="{{ esc_attr($test_action) }}" />
        <input type="hidden" name="_ajax_nonce" value="{{ esc_attr((string) $test_nonce) }}" />
        <button type="submit" class="inline-flex min-h-9 items-center rounded-md border border-blue-600 bg-white px-4 text-sm font-medium text-blue-600 hover:bg-blue-50">
            {{ __('Test Connection', 'pivot-performance-toolkit') }}
        </button>
    </form>
    @php
        $test_connection_form = (string) ob_get_clean();

        ob_start();
    @endphp
    <form method="post" action="{{ esc_url(admin_url('admin-ajax.php')) }}" class="m-0" data-ajax-action-form data-ajax-notice-target="#pivot-performance-toolkit-cdn-actions .pivot-performance-toolkit-card-notices">
        <input type="hidden" name="action" value="{{ esc_attr($purge_action) }}" />
        <input type="hidden" name="_ajax_nonce" value="{{ esc_attr((string) $purge_nonce) }}" />
        <button type="submit" class="inline-flex min-h-9 items-center rounded-md border border-amber-600 bg-white px-4 text-sm font-medium text-amber-600 hover:bg-amber-50">
            {{ __('Purge Cache', 'pivot-performance-toolkit') }}
        </button>
    </form>
    @php
        $purge_cache_form = (string) ob_get_clean();
    @endphp

    <div class="pivot-performance-toolkit-card-notices" aria-live="polite"></div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <x-action-block
            icon="shield-check"
            color="blue"
            :title="__('Test Connection', 'pivot-performance-toolkit')"
            :description="__('Verify your CDN credentials and confirm communication with the provider API.', 'pivot-performance-toolkit')"
            :form="$test_connection_form"
        />

        <x-action-block
            icon="trash-2"
            color="amber"
            :title="__('Purge Cache', 'pivot-performance-toolkit')"
            :description="__('Clear the provider cache so visitors receive the latest CDN-served assets immediately.', 'pivot-performance-toolkit')"
            :form="$purge_cache_form"
        />
    </div>
</x-card>

