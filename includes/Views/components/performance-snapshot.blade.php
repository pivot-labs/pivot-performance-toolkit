<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props([
	'options' => array(),
	'last_score' => 0,
	'has_result' => false,
	'show_table' => true,
	'show_open_full_test' => false,
])

<section class="pivot-performance-toolkit-card col-span-full w-full" id="pivot-performance-toolkit-performance-test-card" data-pivot-performance-toolkit-performance-test>
	<div data-pivot-performance-toolkit-section="title">
		<div class="flex flex-wrap items-start justify-between gap-3">
			<div>
				<div class="flex items-center gap-3">
					<h2 class="pivot-performance-toolkit-card-title"><?php esc_html_e( 'Performance Snapshot', 'pivot-performance-toolkit' ); ?></h2>
					<div data-pivot-performance-toolkit-status class="mb-4 rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600"><?php esc_html_e( 'Idle', 'pivot-performance-toolkit' ); ?></div>
				</div>
				<p class="mt-1.5 mb-0 text-gray-500"><?php esc_html_e( 'Quick test from dashboard. Open Performance page for full report.', 'pivot-performance-toolkit' ); ?></p>
			</div>
					<?php if ( ! empty( $show_open_full_test ) ) : ?>
				<a class="button" href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'page'    => 'pivot-performance-toolkit',
									'section' => 'optimization',
									'tab'     => 'pivot-performance-toolkit-performance',
								),
								admin_url( 'admin.php' )
							)
						);
						?>
										"><?php esc_html_e( 'Open Full Test', 'pivot-performance-toolkit' ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<div data-pivot-performance-toolkit-section="overall-score">
		<div class="mb-3 grid gap-3 lg:grid-cols-[minmax(0,1fr)_275px]">
			<div class="rounded-lg border border-gray-200 bg-white p-3">
				<div class="grid items-center gap-3 lg:grid-cols-[225px_minmax(0,1fr)]">
					<div class="flex justify-center">
					<x-score-donut :score="(int) $last_score" :has_score="(bool) $has_result" size="225" data-pivot-performance-toolkit-score-donut />
					</div>
					<div class="flex flex-col gap-4">
						<div>
							<strong class="mb-1 flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
								<?php esc_html_e( 'Measured URL', 'pivot-performance-toolkit' ); ?>
							</strong>
							<span data-pivot-performance-toolkit-metric="page_url" class="block break-words">-</span>
						</div>
						<div>
							<strong class="mb-1 flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
								<?php esc_html_e( 'Measured At', 'pivot-performance-toolkit' ); ?>
							</strong>
							<span data-pivot-performance-toolkit-metric="collected_at" class="block break-words">-</span>
						</div>
					</div>
				</div>
			</div>

			<div class="flex flex-col justify-center">
				<div class="rounded-lg border border-gray-200 bg-white p-3" id="pivot-performance-toolkit-page-cache-status">
					<div class="mb-1 text-lg font-semibold tracking-[0.04em] text-gray-500"><?php esc_html_e( 'Page Cache', 'pivot-performance-toolkit' ); ?></div>
					<div class="flex items-center justify-between gap-2 py-2">
						<span class="text-2xl font-semibold" data-pivot-performance-toolkit-metric="page_cache_hit">-</span>
						<span class="inline-flex h-[35px] w-[35px] items-center justify-center [&_*]:h-[35px] [&_*]:w-[35px]" data-pivot-performance-toolkit-metric-status="page_cache_hit"></span>
					</div>
					<p class="mt-2 text-sm text-gray-600" data-pivot-performance-toolkit-cache-message></p>
				</div>
			</div>
		</div>
	</div>

	<div data-pivot-performance-toolkit-section="test-ui">
		<div class="my-3 flex flex-wrap items-center gap-2">
			<label for="pivot-performance-toolkit-test-url" class="screen-reader-text"><?php esc_html_e( 'Test Content', 'pivot-performance-toolkit' ); ?></label>
			<select id="pivot-performance-toolkit-test-url" class="min-w-[280px] flex-1 max-w-[460px]">
				<option value="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Homepage', 'pivot-performance-toolkit' ); ?></option>
				<?php if ( ! empty( $options['pages'] ) ) : ?>
					<optgroup label="<?php esc_attr_e( 'Pages', 'pivot-performance-toolkit' ); ?>">
						<?php foreach ( $options['pages'] as $item ) : ?>
							<option value="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endif; ?>
				<?php if ( ! empty( $options['posts'] ) ) : ?>
					<optgroup label="<?php esc_attr_e( 'Posts', 'pivot-performance-toolkit' ); ?>">
						<?php foreach ( $options['posts'] as $item ) : ?>
							<option value="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endif; ?>
			</select>
			<button type="button" class="button button-primary" data-pivot-performance-toolkit-run-test><?php esc_html_e( 'Run Test', 'pivot-performance-toolkit' ); ?></button>
		</div>
	</div>

	<?php if ( ! empty( $show_table ) ) : ?>
		<div data-pivot-performance-toolkit-section="metrics" data-pivot-performance-toolkit-results>
			<table class="widefat striped max-w-[700px] w-full">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Metric', 'pivot-performance-toolkit' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Value', 'pivot-performance-toolkit' ); ?></th>
						<th scope="col" class="text-right" style="text-align:right !important;"><?php esc_html_e( 'Status', 'pivot-performance-toolkit' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr><td><strong><?php esc_html_e( 'Page Load Time', 'pivot-performance-toolkit' ); ?></strong></td><td data-pivot-performance-toolkit-metric="load_event_ms">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="load_event_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><strong><?php esc_html_e( 'Largest Contentful Paint (LCP)', 'pivot-performance-toolkit' ); ?></strong></td><td data-pivot-performance-toolkit-metric="lcp_ms">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="lcp_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><strong><?php esc_html_e( 'Time To First Byte (TTFB)', 'pivot-performance-toolkit' ); ?></strong></td><td data-pivot-performance-toolkit-metric="ttfb_ms">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="ttfb_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><strong><?php esc_html_e( 'First Contentful Paint (FCP)', 'pivot-performance-toolkit' ); ?></strong></td><td data-pivot-performance-toolkit-metric="fcp_ms">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="fcp_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'DOM Content Loaded', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="dom_content_loaded_ms">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="dom_content_loaded_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'Resource Count', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="total_resource_count">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="total_resource_count"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'Total Image Size', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="total_image_size_bytes">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="total_image_size_bytes"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'Images', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="total_image_count">-</td><td class="text-right"></td></tr>
					<tr><td><?php esc_html_e( 'Total JS Size', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="total_js_size_bytes">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="total_js_size_bytes"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'JS Scripts', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="total_js_count">-</td><td class="text-right"></td></tr>
					<tr><td><?php esc_html_e( 'Total CSS Size', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="total_css_size_bytes">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="total_css_size_bytes"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'CSS Stylesheets', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="total_css_count">-</td><td class="text-right"></td></tr>
					<tr><td><?php esc_html_e( 'Page Cache', 'pivot-performance-toolkit' ); ?></td><td data-pivot-performance-toolkit-metric="page_cache_hit">-</td><td class="text-right" data-pivot-performance-toolkit-metric-status="page_cache_hit"></td></tr>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

