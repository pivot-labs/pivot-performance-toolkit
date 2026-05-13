@if ($cleaned_task)
    <div class="notice notice-success is-dismissible">
        <p>
            @if ($cleaned_task === 'optimize')
                {{ sprintf(
                    __('%d database table(s) optimized successfully.', 'performance-toolkit'),
                    $cleaned_count
                ) }}
            @else
                {{ sprintf(
                    __('%1$s: %2$d item(s) removed successfully.', 'performance-toolkit'),
                    $task_labels[$cleaned_task] ?? $cleaned_task,
                    $cleaned_count
                ) }}
            @endif
        </p>
    </div>
@endif

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

<x-card :title="__('Cleanup', 'performance-toolkit')">
    <p style="margin:0 0 16px;color:#646970">{{ __('Remove unnecessary data to keep your database lean and fast.', 'performance-toolkit') }}</p>

    <div class="ptk-cleanup-list">
        @foreach ($cleanup_items as $task => $item)
            <div class="ptk-cleanup-item">
                <div class="ptk-cleanup-item-info">
                    <div class="ptk-cleanup-item-label">{{ $item['label'] }}</div>
                    <div class="ptk-cleanup-item-desc">{{ $item['desc'] }}</div>
                </div>
                <span class="ptk-cleanup-badge {{ $item['count'] > 0 ? 'has-items' : '' }}">
                    {{ number_format_i18n((int) $item['count']) }}
                </span>
                <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
                    <input type="hidden" name="action" value="{{ esc_attr($cleanup_action) }}" />
                    <input type="hidden" name="ptk_task" value="{{ esc_attr($task) }}" />
                    @php
                        wp_nonce_field('ptk_database_cleanup');
                    @endphp
                    <button
                        type="submit"
                        class="button button-secondary ptk-cleanup-btn"
                        {{ (int) $item['count'] === 0 ? 'disabled' : '' }}
                    >
                        {{ __('Clean', 'performance-toolkit') }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>
</x-card>

@if (!empty($table_stats))
    <x-card :title="__('Table breakdown', 'performance-toolkit')">
        <table class="ptk-table-list">
            <thead>
                <tr>
                    <th>{{ __('Table', 'performance-toolkit') }}</th>
                    <th>{{ __('Engine', 'performance-toolkit') }}</th>
                    <th>{{ __('Rows', 'performance-toolkit') }}</th>
                    <th>{{ __('Size', 'performance-toolkit') }}</th>
                    <th>{{ __('Overhead', 'performance-toolkit') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($table_stats as $table)
                    <tr>
                        <td><code>{{ $table['name'] }}</code></td>
                        <td><span class="ptk-engine-badge">{{ $table['engine'] }}</span></td>
                        <td>{{ number_format_i18n((int) $table['rows']) }}</td>
                        <td>{{ \PerformanceToolkit\Database\DatabaseOptimizer::formatBytes((int) $table['size_bytes']) }}</td>
                        <td>
                            @if ((int) $table['overhead_bytes'] > 0)
                                <span class="ptk-overhead-badge">
                                    {{ \PerformanceToolkit\Database\DatabaseOptimizer::formatBytes((int) $table['overhead_bytes']) }}
                                </span>
                            @else
                                <span class="ptk-overhead-ok">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>
@endif

