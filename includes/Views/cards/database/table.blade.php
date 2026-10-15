<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Table breakdown', 'pivot-performance-toolkit')">
	@php
		$show_overhead = (bool) ($show_overhead ?? false);
		$max_rows = isset($max_rows) ? (int) $max_rows : 0;
		$display_rows = $max_rows > 0 ? array_slice($table_stats, 0, $max_rows) : $table_stats;
		$sort_by = (string) ($sort_by ?? 'size');
		$sort_dir = strtolower((string) ($sort_dir ?? 'desc'));
		$size_sort_url = (string) ($size_sort_url ?? '');
		$size_sort_indicator = '';

		if ($sort_by === 'size') {
			$size_sort_indicator = $sort_dir === 'asc' ? ' ↑' : ' ↓';
		}
	@endphp

	@if (!empty($display_rows))
		<table class="pivot-performance-toolkit-table-list">
			<thead>
				<tr>
					<th>{{ __('Table', 'pivot-performance-toolkit') }}</th>
					<th>{{ __('Engine', 'pivot-performance-toolkit') }}</th>
					<th>{{ __('Rows', 'pivot-performance-toolkit') }}</th>
					<th>
						@if ($size_sort_url !== '')
							<a href="{!! esc_url($size_sort_url) !!}" style="text-decoration:none;">
								{{ __('Size', 'pivot-performance-toolkit') }}{{ $size_sort_indicator }}
							</a>
						@else
							{{ __('Size', 'pivot-performance-toolkit') }}{{ $size_sort_indicator }}
						@endif
					</th>
					@if ($show_overhead)
						<th>{{ __('Overhead', 'pivot-performance-toolkit') }}</th>
					@endif
				</tr>
			</thead>
			<tbody>
				@foreach ($display_rows as $table)
					<tr>
						<td><code>{{ $table['name'] }}</code></td>
						<td><span class="pivot-performance-toolkit-engine-badge">{{ $table['engine'] }}</span></td>
						<td>{{ number_format_i18n((int) $table['rows']) }}</td>
						<td>{{ \PivotPerformanceToolkit\Database\DatabaseOptimizer::formatBytes((int) $table['size_bytes']) }}</td>
						@if ($show_overhead)
							<td>
								@if ((int) $table['overhead_bytes'] > 0)
									<span class="pivot-performance-toolkit-overhead-badge">
										{{ \PivotPerformanceToolkit\Database\DatabaseOptimizer::formatBytes((int) $table['overhead_bytes']) }}
									</span>
								@else
									<span class="pivot-performance-toolkit-overhead-ok">-</span>
								@endif
							</td>
						@endif
					</tr>
				@endforeach
			</tbody>
		</table>
		@if ($max_rows > 0 && count($table_stats) > count($display_rows))
			@php
				$view_all_url = \PivotPerformanceToolkit\Admin\AdminShell::pageUrl( 'pivot-performance-toolkit-database-table' );
			@endphp
			<p style="margin-top:8px;font-size:12px;color:#646970;">
				{{ sprintf(
					/* translators: %d: Number of tables shown in the list. */
					__('Showing top %d tables by size.', 'pivot-performance-toolkit'),
					count($display_rows),
				) }}
				<a href="{!! esc_url($view_all_url) !!}" style="color:#646970;text-decoration:underline;">
					{{ __('See all tables', 'pivot-performance-toolkit') }}
				</a>
			</p>
		@endif


	@else
		<p>{{ __('No table statistics are available right now.', 'pivot-performance-toolkit') }}</p>
	@endif
</x-card>
