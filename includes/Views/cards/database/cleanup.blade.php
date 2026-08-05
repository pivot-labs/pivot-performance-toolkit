<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Cleanup', 'pivot-performance-toolkit')">
    <p style="margin:0 0 16px;color:#646970">{{ __('Remove unnecessary data to keep your database lean and fast.', 'pivot-performance-toolkit') }}</p>

    <div class="pivot-performance-toolkit-cleanup-list">
        @foreach ($cleanup_items as $task => $item)
            <div class="pivot-performance-toolkit-cleanup-item">
                <div class="pivot-performance-toolkit-cleanup-item-info">
                    <div class="pivot-performance-toolkit-cleanup-item-label">{{ $item['label'] }}</div>
                    <div class="pivot-performance-toolkit-cleanup-item-desc">{{ $item['desc'] }}</div>
                </div>
                <span class="pivot-performance-toolkit-cleanup-badge {{ $item['count'] > 0 ? 'has-items' : '' }}">
                    {{ number_format_i18n((int) $item['count']) }}
                </span>
                <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
                    <input type="hidden" name="action" value="{{ esc_attr($cleanup_action) }}" />
                    <input type="hidden" name="pivot_performance_toolkit_task" value="{{ esc_attr($task) }}" />
                    @php
                        wp_nonce_field('pivot_performance_toolkit_database_cleanup');
                    @endphp
                    <button
                        type="submit"
                        class="button button-secondary pivot-performance-toolkit-cleanup-btn"
                        {{ (int) $item['count'] === 0 ? 'disabled' : '' }}
                    >
                        {{ __('Clean', 'pivot-performance-toolkit') }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>
</x-card>

