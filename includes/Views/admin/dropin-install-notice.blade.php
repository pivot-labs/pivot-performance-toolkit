<div class="notice notice-error is-dismissible">
	<p>
		<strong>{{ esc_html__( 'Pivot Performance Toolkit – Drop-in Installation Failed', 'pivot-performance-toolkit' ) }}</strong>
	</p>
	<p>
		{{ esc_html__( 'Pivot Performance Toolkit could not install the page cache drop-in file. Page caching is currently disabled.', 'pivot-performance-toolkit' ) }}
	</p>
	@if ( '' !== $reason )
		<p>
			{{ sprintf(
				/* translators: %s: reason the drop-in install failed. */
				esc_html__( 'Reason: %s', 'pivot-performance-toolkit' ),
				$reason
			) }}
		</p>
	@endif
	<p>{{ esc_html__( 'Next steps:', 'pivot-performance-toolkit' ) }}</p>
	<ol style="margin: 8px 0 8px 20px; list-style: decimal;">
		<li>{{ esc_html__( 'Ensure your wp-content directory is writable by the web server process.', 'pivot-performance-toolkit' ) }}</li>
		@if ( '' !== $source && '' !== $dest )
			<li>
				{!! sprintf(
					/* translators: 1: source file path, 2: destination file path. */
					wp_kses_post( __( 'Manually copy <code>%1$s</code> to <code>%2$s</code>.', 'pivot-performance-toolkit' ) ),
					esc_html( $source ),
					esc_html( $dest )
				) !!}
			</li>
		@endif
		<li>{{ esc_html__( 'After fixing this, reactivate the plugin or toggle cache settings to retry installation.', 'pivot-performance-toolkit' ) }}</li>
	</ol>
</div>

