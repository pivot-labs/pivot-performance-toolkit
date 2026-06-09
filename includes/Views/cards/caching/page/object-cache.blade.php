<x-card :title="__('Object Cache', 'performance-toolkit')" class="ptk-object-cache-card" id="ptk-object-cache-card">

	<?php
	$oc             = $object_cache ?? array();
	$is_our_dropin  = ! empty( $oc['is_our_dropin'] );
	$has_foreign    = ! empty( $oc['has_foreign'] );
	$is_active      = ! empty( $oc['active'] );
	$can_enable     = ! empty( $oc['can_enable'] );
	$status_label   = $oc['status_label'] ?? __( 'Inactive', 'performance-toolkit' );
	$provider       = $oc['provider'] ?? __( 'None', 'performance-toolkit' );
	$entry_count    = (int) ( $oc['entry_count'] ?? 0 );
	$size_formatted = $oc['size_formatted'] ?? '0 B';
	?>

	<p class="m-0 mb-4 text-sm text-gray-500">
		{{ __( 'Persist WordPress object cache entries to disk between requests, reducing database load and speeding up repeated queries.', 'performance-toolkit' ) }}
	</p>

	{{-- Stats grid --}}
	<div class="ptk-object-cache-stats">
		<div class="ptk-stat">
			<span class="ptk-stat-label">{{ __( 'Status', 'performance-toolkit' ) }}</span>
			<strong class="{{ $is_active ? 'text-green-600' : 'text-gray-400' }}">
				{{ $status_label }}
			</strong>
		</div>
		<div class="ptk-stat">
			<span class="ptk-stat-label">{{ __( 'Provider', 'performance-toolkit' ) }}</span>
			<strong>{{ $provider }}</strong>
		</div>
		@if ( $is_our_dropin )
			<div class="ptk-stat">
				<span class="ptk-stat-label">{{ __( 'Entries', 'performance-toolkit' ) }}</span>
				<strong>{{ number_format( $entry_count ) }}</strong>
			</div>
			<div class="ptk-stat">
				<span class="ptk-stat-label">{{ __( 'Cache Size', 'performance-toolkit' ) }}</span>
				<strong>{{ $size_formatted }}</strong>
			</div>
		@else
			<div class="ptk-stat">
				<span class="ptk-stat-label">{{ __( 'Drop-in', 'performance-toolkit' ) }}</span>
				<strong>{{ $oc['dropin_label'] ?? __( 'Not installed', 'performance-toolkit' ) }}</strong>
			</div>
		@endif
	</div>

	{{-- Notice area for AJAX feedback --}}
	<div class="ptk-oc-notice" style="display:none"></div>

	{{-- Foreign drop-in warning --}}
	@if ( $has_foreign )
		<div class="notice notice-warning inline mt-4">
			<p class="m-0 text-sm">
				<strong>{{ __( 'External object cache detected.', 'performance-toolkit' ) }}</strong>
				{{ __( 'Another plugin is managing object-cache.php. Performance Toolkit will not overwrite it.', 'performance-toolkit' ) }}
			</p>
		</div>
	@endif

	{{-- Action buttons --}}
	<div class="mt-4 flex flex-wrap gap-2 items-center">

		@if ( ! $is_active && $can_enable )
			<button
				type="button"
				class="button button-primary ptk-oc-btn"
				data-oc-action="{{ esc_attr( (string) ( $ajax_enable_object_cache_action ?? '' ) ) }}"
				data-oc-nonce="{{ esc_attr( (string) ( $ajax_enable_object_cache_nonce ?? '' ) ) }}"
				data-oc-reload="1"
			>
				<?php esc_html_e( 'Enable File Object Cache', 'performance-toolkit' ); ?>
			</button>
		@endif

		@if ( $is_our_dropin )
			<button
				type="button"
				class="button ptk-oc-btn"
				data-oc-action="{{ esc_attr( (string) ( $ajax_flush_object_cache_action ?? '' ) ) }}"
				data-oc-nonce="{{ esc_attr( (string) ( $ajax_flush_object_cache_nonce ?? '' ) ) }}"
			>
				<?php esc_html_e( 'Flush Object Cache', 'performance-toolkit' ); ?>
			</button>

			<button
				type="button"
				class="button ptk-oc-btn"
				data-oc-action="{{ esc_attr( (string) ( $ajax_disable_object_cache_action ?? '' ) ) }}"
				data-oc-nonce="{{ esc_attr( (string) ( $ajax_disable_object_cache_nonce ?? '' ) ) }}"
				data-oc-reload="1"
			>
				<?php esc_html_e( 'Disable File Object Cache', 'performance-toolkit' ); ?>
			</button>
		@endif

	</div>

</x-card>
