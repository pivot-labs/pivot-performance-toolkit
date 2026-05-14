<x-card :title="__('Database overview', 'performance-toolkit')">
    <div class="ptk-db-stats">
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Total size', 'performance-toolkit') }}</span>
            <strong>{{ $db_size_formatted }}</strong>
        </div>
        @if ((int) $stats['myisam_overhead_bytes'] > 0)
            <div class="ptk-stat">
                <span class="ptk-stat-label">{{ __('Reclaimable (MyISAM)', 'performance-toolkit') }}</span>
                <strong class="ptk-stat-warn">{{ $myisam_reclaimable }}</strong>
            </div>
        @endif
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Tables', 'performance-toolkit') }}</span>
            <strong>{{ count($table_stats) }}</strong>
        </div>
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('DB Engine', 'performance-toolkit') }}</span>
            <strong>{{ $engine_display }}</strong>
        </div>
    </div>

    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
        <input type="hidden" name="action" value="{{ esc_attr($cleanup_action) }}" />
        <input type="hidden" name="ptk_task" value="optimize" />
        @php
            wp_nonce_field('ptk_database_cleanup');
            submit_button(__('Optimize all tables', 'performance-toolkit'), 'secondary', 'submit', false);
        @endphp
    </form>

</x-card>

