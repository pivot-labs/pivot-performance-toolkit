<x-card :title="__('Uninstall cleanup policy', 'performance-toolkit')" id="ptk-tools">


{{-- Uninstall cleanup policy --}}
<div class="ptk-field" style="margin-top:18px;">

    <p>{{ __('Choose whether plugin settings and cache data should be removed when the plugin is deleted from WordPress.', 'performance-toolkit') }}</p>
    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
        <input type="hidden" name="action" value="{{ esc_attr($set_uninstall_policy_action) }}" />
        @php wp_nonce_field('ptk_set_uninstall_policy'); @endphp
        <label style="display:block;margin:6px 0 10px;">
            <input type="checkbox" name="ptk_remove_data_on_uninstall" value="1" {{ $cleanup_on_uninstall ? 'checked' : '' }} />
            <span>{{ __('Remove all Performance Toolkit data on uninstall', 'performance-toolkit') }}</span>
        </label>
        <p style="margin-top:-4px;color:#646970;">
            {{ __('If enabled, deleting the plugin removes its settings and cache files. If disabled, data is preserved for reinstall.', 'performance-toolkit') }}
        </p>
        @php submit_button(__('Save uninstall policy', 'performance-toolkit'), 'secondary', 'submit', false); @endphp
    </form>
</div>

</x-card>