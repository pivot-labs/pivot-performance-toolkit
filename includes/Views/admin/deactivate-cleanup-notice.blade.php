<div class="notice notice-warning is-dismissible">
	<p>
		<strong>{{ esc_html__( 'Performance Toolkit – Deactivation Cleanup Incomplete', 'performance-toolkit' ) }}</strong>
	</p>
	<p>
		{{ esc_html__( 'Some cache cleanup steps failed during deactivation. You can safely remove these files manually.', 'performance-toolkit' ) }}
	</p>
	<ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
		@foreach ( $errors as $error )
			<li>{{ esc_html( $error ) }}</li>
		@endforeach
	</ul>
	<p>{{ esc_html__( 'Recommended actions:', 'performance-toolkit' ) }}</p>
	<ol style="margin: 8px 0 8px 20px; list-style: decimal;">
		@if ( '' !== $cache_dir )
			<li>
				{!! sprintf(
					/* translators: %s: absolute cache directory path. */
					wp_kses_post( __( 'Delete stale HTML cache files from <code>%s</code>.', 'performance-toolkit' ) ),
					esc_html( $cache_dir )
				) !!}
			</li>
		@endif
		@if ( '' !== $config_file )
			<li>
				{!! sprintf(
					/* translators: %s: absolute cache config file path. */
					wp_kses_post( __( 'If it still exists, remove <code>%s</code>.', 'performance-toolkit' ) ),
					esc_html( $config_file )
				) !!}
			</li>
		@endif
		<li>{{ esc_html__( 'Check ownership/permissions for wp-content and cache directories so PHP can delete files.', 'performance-toolkit' ) }}</li>
	</ol>
</div>

