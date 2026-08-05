<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
    @if ($cleaned_task)
        <div class="notice notice-success is-dismissible">
            <p>
                @if ($cleaned_task === 'optimize')
                    {{ sprintf(
                        /* translators: %d: Number of database tables optimized. */
                        __('%d database table(s) optimized successfully.', 'pivot-performance-toolkit'),
                        $cleaned_count
                    ) }}
                @else
                    {{ sprintf(
                        /* translators: 1: Cleanup task label, 2: Number of items removed. */
                        __('%1$s: %2$d item(s) removed successfully.', 'pivot-performance-toolkit'),
                        $task_labels[$cleaned_task] ?? $cleaned_task,
                        $cleaned_count
                    ) }}
                @endif
            </p>
        </div>
    @endif

    @include('cards.database.overview', array(
        'stats' => $stats,
        'table_stats' => $table_stats,
        'db_size_formatted' => $db_size_formatted,
        'myisam_reclaimable' => $myisam_reclaimable,
        'engine_display' => $engine_display,
        'cleanup_action' => $cleanup_action,
    ))

    @include('cards.database.table', array(
        'table_stats' => $table_stats,
        'show_overhead' => $show_overhead,
        'max_rows'  => 8,
        'sort_by' => $sort_by,
        'sort_dir' => $sort_dir,
    ))

</div>

<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--sidebar">
    @include('cards.database.cleanup', array(
        'cleanup_items' => $cleanup_items,
        'cleanup_action' => $cleanup_action,
    ))

    @include('cards.database.info.why')


 </div>






