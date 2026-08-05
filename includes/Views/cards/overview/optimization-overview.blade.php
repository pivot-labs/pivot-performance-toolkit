<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Optimization Overview', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-dashboard">
	@if (empty($fs_status['writable']))
		<div style="margin-bottom: 16px; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
			<p style="margin: 0;">
				<strong>{{ __('Warning:', 'pivot-performance-toolkit') }}</strong>
				{{ __('Cache directory not writable. Caching is disabled. See System Status for details.', 'pivot-performance-toolkit') }}
			</p>
		</div>
	@endif

	<div class="pivot-performance-toolkit-stats-grid">
		<div class="pivot-performance-toolkit-stat">
			<span class="pivot-performance-toolkit-stat-label">{{ __('Page cache', 'pivot-performance-toolkit') }}</span>
			<strong>{{ !empty($options['enable_page_cache']) ? __('Enabled', 'pivot-performance-toolkit') : __('Disabled', 'pivot-performance-toolkit') }}</strong>
		</div>
		<div class="pivot-performance-toolkit-stat">
			<span class="pivot-performance-toolkit-stat-label">{{ __('Script defer', 'pivot-performance-toolkit') }}</span>
			<strong>{{ !empty($options['defer_scripts']) ? __('Enabled', 'pivot-performance-toolkit') : __('Disabled', 'pivot-performance-toolkit') }}</strong>
		</div>
		<div class="pivot-performance-toolkit-stat">
			<span class="pivot-performance-toolkit-stat-label">{{ __('Image lazy loading', 'pivot-performance-toolkit') }}</span>
			<strong>{{ !empty($options['lazy_load_images']) ? __('Enabled', 'pivot-performance-toolkit') : __('Disabled', 'pivot-performance-toolkit') }}</strong>
		</div>
		<div class="pivot-performance-toolkit-stat">
			<span class="pivot-performance-toolkit-stat-label">{{ __('Cache directory', 'pivot-performance-toolkit') }}</span>
			<strong style="color: {{ !empty($fs_status['writable']) ? '#28a745' : '#dc3545' }};">
				{{ !empty($fs_status['writable']) ? __('Writable', 'pivot-performance-toolkit') : __('Read-only', 'pivot-performance-toolkit') }}
			</strong>
		</div>
	</div>
</x-card>


<?php do_action( 'pivot_performance_toolkit_render_pro_overview_cards' ); ?>
