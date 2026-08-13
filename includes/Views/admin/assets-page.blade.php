<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main col-span-full">
	@if ($settings_updated && !isset($_GET['pivot_performance_toolkit_notice']))
		<div class="notice notice-success is-dismissible">
			<p>{{ __('Settings saved successfully.', 'pivot-performance-toolkit') }}</p>
		</div>
	@endif

	<section class="pivot-performance-toolkit-card" data-pivot-performance-toolkit-assets-detector data-ajax-action="{{ esc_attr((string) $ajax_detect_action) }}" data-ajax-nonce="{{ esc_attr((string) $ajax_detect_nonce) }}">
		<h2 class="pivot-performance-toolkit-card-title">{{ __('Assets Detector', 'pivot-performance-toolkit') }}</h2>
		<div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1.8fr)_minmax(280px,1fr)] md:items-start">
			<div id="pivot-performance-toolkit-assets-detector-controls">
				<p class="mt-0">{{ $assets_detector_message }}</p>

				<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:12px 0;">
					<label for="pivot-performance-toolkit-assets-target-url" class="screen-reader-text">{{ __('Select content to scan', 'pivot-performance-toolkit') }}</label>
					<select id="pivot-performance-toolkit-assets-target-url" data-pivot-performance-toolkit-assets-select style="min-width:320px;flex:1;">
						<option value="{{ esc_url(home_url('/')) }}">{{ __('Homepage', 'pivot-performance-toolkit') }}</option>
						@if (!empty($content_options['pages']))
							<optgroup label="{{ esc_attr__('Pages', 'pivot-performance-toolkit') }}">
								@foreach ($content_options['pages'] as $item)
									<option value="{{ esc_url($item['url']) }}">{{ esc_html($item['label']) }}</option>
								@endforeach
							</optgroup>
						@endif
						@if (!empty($content_options['posts']))
							<optgroup label="{{ esc_attr__('Posts', 'pivot-performance-toolkit') }}">
								@foreach ($content_options['posts'] as $item)
									<option value="{{ esc_url($item['url']) }}">{{ esc_html($item['label']) }}</option>
								@endforeach
							</optgroup>
						@endif
					</select>
					<button type="button" class="button button-primary" data-pivot-performance-toolkit-assets-run>{{ __('Detect Assets', 'pivot-performance-toolkit') }}</button>
				</div>

				<p data-pivot-performance-toolkit-assets-status style="margin:8px 0 10px;">{{ __('Idle', 'pivot-performance-toolkit') }}</p>
				<p data-pivot-performance-toolkit-assets-summary style="margin:0 0 12px; color:#4b5563;"></p>
			</div>

			<div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
				<h2 class="m-0 text-sm font-semibold text-gray-900">{{ __('How it works', 'pivot-performance-toolkit') }}</h2>
				<p class="mt-2 mb-0 text-sm text-gray-600">{{ __('The detector requests the selected URL, reads the returned HTML, and lists script, stylesheet, and image URLs found on that page.', 'pivot-performance-toolkit') }}</p>
			</div>
		</div>


		<div data-pivot-performance-toolkit-assets-filters style="display:none;margin-bottom:12px;gap:6px;flex-wrap:wrap;align-items:center;">
			<span class="text-xs font-semibold text-gray-500" style="margin-right:4px;">{{ __('Filter:', 'pivot-performance-toolkit') }}</span>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="all" aria-pressed="true">{{ __('All', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="Theme">{{ __('Theme', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="Plugin">{{ __('Plugin', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="External">{{ __('External', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="Core">{{ __('Core (WP)', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="Other">{{ __('Other', 'pivot-performance-toolkit') }}</button>
		</div>

		<table class="widefat striped" data-pivot-performance-toolkit-assets-table style="display:none;">
			<colgroup>
				<col style="width: 80px;">
				<col style="width: auto;">
				<col style="width: 80px;">
			</colgroup>
			<thead>
				<tr>
					<th scope="col">{{ __('Type', 'pivot-performance-toolkit') }}</th>
					<th scope="col">{{ __('Asset URL', 'pivot-performance-toolkit') }}</th>
					<th scope="col">{{ __('Category', 'pivot-performance-toolkit') }}</th>
				</tr>
			</thead>
			<tbody data-pivot-performance-toolkit-assets-rows></tbody>
		</table>
	</section>
</div>
