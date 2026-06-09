@props([
	'options' => array(),
	'last_score' => 0,
	'show_table' => true,
	'show_open_full_test' => false,
])

<section class="ptk-card col-span-full w-full" id="ptk-performance-test-card" data-ptk-performance-test>
	<div data-ptk-section="title">
		<div class="flex flex-wrap items-start justify-between gap-3">
			<div>
				<div class="flex items-center gap-3">
					<h2 class="ptk-card-title"><?php esc_html_e( 'Performance Snapshot', 'performance-toolkit' ); ?></h2>
					<div data-ptk-status class="mb-4 rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600"><?php esc_html_e( 'Idle', 'performance-toolkit' ); ?></div>
				</div>
				<p class="mt-1.5 mb-0 text-gray-500"><?php esc_html_e( 'Quick test from dashboard. Open Performance page for full report.', 'performance-toolkit' ); ?></p>
			</div>
					<?php if ( ! empty( $show_open_full_test ) ) : ?>
				<a class="button" href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'page'    => 'performance-toolkit',
									'section' => 'optimization',
									'tab'     => 'performance-toolkit-performance',
								),
								admin_url( 'admin.php' )
							)
						);
						?>
										"><?php esc_html_e( 'Open Full Test', 'performance-toolkit' ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<div data-ptk-section="overall-score">
		<div class="mb-3 grid gap-3 lg:grid-cols-[minmax(0,1fr)_275px]">
			<div class="rounded-lg border border-gray-200 bg-white p-3">
				<div class="grid items-center gap-3 lg:grid-cols-[225px_minmax(0,1fr)]">
					<div class="flex justify-center">
					<x-score-donut :score="(int) $last_score" size="225" data-ptk-score-donut />
					</div>
					<div class="flex flex-col gap-4">
						<div>
							<strong class="mb-1 flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
								<?php esc_html_e( 'Measured URL', 'performance-toolkit' ); ?>
							</strong>
							<span data-ptk-metric="page_url" class="block break-words">-</span>
						</div>
						<div>
							<strong class="mb-1 flex items-center gap-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
								<?php esc_html_e( 'Measured At', 'performance-toolkit' ); ?>
							</strong>
							<span data-ptk-metric="collected_at" class="block break-words">-</span>
						</div>
					</div>
				</div>
			</div>

			<div class="flex flex-col justify-center">
				<div class="rounded-lg border border-gray-200 bg-white p-3" id="ptk-page-cache-status">
					<div class="mb-1 text-lg font-semibold tracking-[0.04em] text-gray-500"><?php esc_html_e( 'Page Cache', 'performance-toolkit' ); ?></div>
					<div class="flex items-center justify-between gap-2 py-2">
						<span class="text-2xl font-semibold" data-ptk-metric="page_cache_hit">-</span>
						<span class="inline-flex h-[35px] w-[35px] items-center justify-center [&_*]:h-[35px] [&_*]:w-[35px]" data-ptk-metric-status="page_cache_hit"></span>
					</div>
					<p class="mt-2 text-sm text-gray-600" data-ptk-cache-message></p>
				</div>
			</div>
		</div>
	</div>

	<div data-ptk-section="test-ui">
		<div class="my-3 flex flex-wrap items-center gap-2">
			<label for="ptk-test-url" class="screen-reader-text"><?php esc_html_e( 'Test Content', 'performance-toolkit' ); ?></label>
			<select id="ptk-test-url" class="min-w-[280px] flex-1 max-w-[460px]">
				<option value="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Homepage', 'performance-toolkit' ); ?></option>
				<?php if ( ! empty( $options['pages'] ) ) : ?>
					<optgroup label="<?php esc_attr_e( 'Pages', 'performance-toolkit' ); ?>">
						<?php foreach ( $options['pages'] as $item ) : ?>
							<option value="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endif; ?>
				<?php if ( ! empty( $options['posts'] ) ) : ?>
					<optgroup label="<?php esc_attr_e( 'Posts', 'performance-toolkit' ); ?>">
						<?php foreach ( $options['posts'] as $item ) : ?>
							<option value="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endif; ?>
			</select>
			<button type="button" class="button button-primary" data-ptk-run-test><?php esc_html_e( 'Run Test', 'performance-toolkit' ); ?></button>
		</div>
	</div>

	<?php if ( ! empty( $show_table ) ) : ?>
		<div data-ptk-section="metrics" data-ptk-results>
			<table class="widefat striped max-w-[700px] w-full">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Metric', 'performance-toolkit' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Value', 'performance-toolkit' ); ?></th>
						<th scope="col" class="text-right" style="text-align:right !important;"><?php esc_html_e( 'Status', 'performance-toolkit' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr><td><strong><?php esc_html_e( 'Page Load Time', 'performance-toolkit' ); ?></strong></td><td data-ptk-metric="load_event_ms">-</td><td class="text-right" data-ptk-metric-status="load_event_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><strong><?php esc_html_e( 'Largest Contentful Paint (LCP)', 'performance-toolkit' ); ?></strong></td><td data-ptk-metric="lcp_ms">-</td><td class="text-right" data-ptk-metric-status="lcp_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><strong><?php esc_html_e( 'Time To First Byte (TTFB)', 'performance-toolkit' ); ?></strong></td><td data-ptk-metric="ttfb_ms">-</td><td class="text-right" data-ptk-metric-status="ttfb_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><strong><?php esc_html_e( 'First Contentful Paint (FCP)', 'performance-toolkit' ); ?></strong></td><td data-ptk-metric="fcp_ms">-</td><td class="text-right" data-ptk-metric-status="fcp_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'DOM Content Loaded', 'performance-toolkit' ); ?></td><td data-ptk-metric="dom_content_loaded_ms">-</td><td class="text-right" data-ptk-metric-status="dom_content_loaded_ms"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'Resource Count', 'performance-toolkit' ); ?></td><td data-ptk-metric="total_resource_count">-</td><td class="text-right" data-ptk-metric-status="total_resource_count"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'Total Image Size', 'performance-toolkit' ); ?></td><td data-ptk-metric="total_image_size_bytes">-</td><td class="text-right" data-ptk-metric-status="total_image_size_bytes"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'Images', 'performance-toolkit' ); ?></td><td data-ptk-metric="total_image_count">-</td><td class="text-right"></td></tr>
					<tr><td><?php esc_html_e( 'Total JS Size', 'performance-toolkit' ); ?></td><td data-ptk-metric="total_js_size_bytes">-</td><td class="text-right" data-ptk-metric-status="total_js_size_bytes"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'JS Scripts', 'performance-toolkit' ); ?></td><td data-ptk-metric="total_js_count">-</td><td class="text-right"></td></tr>
					<tr><td><?php esc_html_e( 'Total CSS Size', 'performance-toolkit' ); ?></td><td data-ptk-metric="total_css_size_bytes">-</td><td class="text-right" data-ptk-metric-status="total_css_size_bytes"><x-status-pill status="-" /></td></tr>
					<tr><td><?php esc_html_e( 'CSS Stylesheets', 'performance-toolkit' ); ?></td><td data-ptk-metric="total_css_count">-</td><td class="text-right"></td></tr>
					<tr><td><?php esc_html_e( 'Page Cache', 'performance-toolkit' ); ?></td><td data-ptk-metric="page_cache_hit">-</td><td class="text-right" data-ptk-metric-status="page_cache_hit"></td></tr>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

