<x-card :title="__('Reset to safe defaults', 'performance-toolkit')" id="ptk-tools">


{{-- Reset to safe defaults --}}
<div class="ptk-field" style="margin-top:18px; padding:12px; background-color:#fef5f5; border-left:4px solid #d63638;">
    <h3 style="margin:0 0 8px; color:#d63638;">{{ __('Reset to safe defaults', 'performance-toolkit') }}</h3>
    <p>{{ __('Reset all Performance Toolkit settings to their recommended safe defaults. This action cannot be undone.', 'performance-toolkit') }}</p>
    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
        <input type="hidden" name="action" value="{{ esc_attr($reset_to_defaults_action) }}" />
        @php wp_nonce_field('ptk_reset_to_defaults'); @endphp
        <label style="display:block;margin:6px 0 10px;">
            <input type="checkbox" name="ptk_confirm_reset" value="1" required />
            <span>{{ __('I understand this will reset all settings and cannot be undone', 'performance-toolkit') }}</span>
        </label>
        @php submit_button(__('Reset to safe defaults', 'performance-toolkit'), 'delete', 'submit', false); @endphp
    </form>
</div>



</x-card>