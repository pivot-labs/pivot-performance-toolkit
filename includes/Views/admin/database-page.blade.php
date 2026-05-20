<div class="ptk-col ptk-col--main">
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

<div class="ptk-col ptk-col--sidebar">
    @include('cards.database.cleanup', array(
        'cleanup_items' => $cleanup_items,
        'cleanup_action' => $cleanup_action,
    ))

    @include('cards.database.info.why')


 </div>


