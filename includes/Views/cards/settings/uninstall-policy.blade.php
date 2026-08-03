<x-card-standard
    :title="__('Uninstall Cleanup Policy', 'pivot-performance-toolkit')"
    :description="__('Choose whether plugin settings and cache data should be removed when the plugin is deleted from WordPress.', 'pivot-performance-toolkit')"
    icon="trash-2"
    tone="indigo"
>
    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
        @php wp_nonce_field('pivot_performance_toolkit_set_uninstall_policy'); @endphp
        <input type="hidden" name="action" value="{{ esc_attr($set_uninstall_policy_action) }}" />

        <div class="min-w-0 flex-1 text-sm text-gray-500">
            <label class="flex items-start gap-3">
                <input type="checkbox" name="pivot_performance_toolkit_remove_data_on_uninstall" value="1" {{ $cleanup_on_uninstall ? 'checked' : '' }} class="mt-1" />
                <span class="text-gray-900">{{ __('Remove all Pivot Performance Toolkit data on uninstall', 'pivot-performance-toolkit') }}</span>
            </label>
            <p class="mt-2 leading-6 text-gray-500">
                {{ __('If enabled, deleting the plugin removes its settings and cache files. If disabled, data is preserved for reinstall.', 'pivot-performance-toolkit') }}
            </p>
        </div>

        <div class="md:w-auto md:shrink-0 md:self-center">
            @php submit_button(__('Save uninstall policy', 'pivot-performance-toolkit'), 'secondary', 'submit', false); @endphp
        </div>
    </form>
</x-card-standard>
