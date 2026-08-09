<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Database overview', 'pivot-performance-toolkit')">
    <div class="pivot-performance-toolkit-db-stats">
        <div class="pivot-performance-toolkit-stat">
            <span class="pivot-performance-toolkit-stat-label">{{ __('Total size', 'pivot-performance-toolkit') }}</span>
            <strong>{{ $db_size_formatted }}</strong>
        </div>
        @if ((int) $stats['myisam_overhead_bytes'] > 0)
            <div class="pivot-performance-toolkit-stat">
                <span class="pivot-performance-toolkit-stat-label">{{ __('Reclaimable (MyISAM)', 'pivot-performance-toolkit') }}</span>
                <strong class="pivot-performance-toolkit-stat-warn">{{ $myisam_reclaimable }}</strong>
            </div>
        @endif
        <div class="pivot-performance-toolkit-stat">
            <span class="pivot-performance-toolkit-stat-label">{{ __('Tables', 'pivot-performance-toolkit') }}</span>
            <strong>{{ count($table_stats) }}</strong>
        </div>
        <div class="pivot-performance-toolkit-stat">
            <span class="pivot-performance-toolkit-stat-label">{{ __('DB Engine', 'pivot-performance-toolkit') }}</span>
            <strong>{{ $engine_display }}</strong>
        </div>
    </div>

    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" data-disable-on-submit>
        <input type="hidden" name="action" value="{{ esc_attr($cleanup_action) }}" />
        <input type="hidden" name="pivot_performance_toolkit_task" value="optimize" />
        <span class="inline-flex items-center gap-2">
            @php
                wp_nonce_field('pivot_performance_toolkit_database_cleanup');
                submit_button(__('Optimize all tables', 'pivot-performance-toolkit'), 'secondary', 'submit', false);
            @endphp
            <span class="spinner" style="float: none; margin: 0;"></span>
        </span>
    </form>

</x-card>

