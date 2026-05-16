<x-info-card :title="__('Why optimize your database?', 'performance-toolkit')">
    @php
        $benefits = array(
            __('Improve overall site performance', 'performance-toolkit'),
            __('Reduce database size', 'performance-toolkit'),
            __('Speed up queries and page loads', 'performance-toolkit'),
            __('Remove unnecessary clutter', 'performance-toolkit'),
        );
    @endphp

    <p style="margin:0 0 16px;color:#646970">{{ __('Regular database optimization can:', 'performance-toolkit') }}</p>
    <x-info-list :items="$benefits" />
    <p style="margin:0;color:#646970">{{ __('Recommended: Optimize your database weekly.', 'performance-toolkit') }}</p>

    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" style="margin-top:16px;">
        <input type="hidden" name="action" value="{{ esc_attr($cleanup_action) }}" />
        <input type="hidden" name="ptk_task" value="optimize" />
        @php
            wp_nonce_field('ptk_database_cleanup');
            submit_button(__('Optimize all tables', 'performance-toolkit'), 'secondary', 'submit', false);
        @endphp
    </form>
</x-info-card>