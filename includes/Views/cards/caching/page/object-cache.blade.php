<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Object Cache', 'pivot-performance-toolkit')" class="pivot-performance-toolkit-object-cache-card" id="pivot-performance-toolkit-object-cache-card">

	<?php
	$oc             = $object_cache ?? array();
	$is_our_dropin  = ! empty( $oc['is_our_dropin'] );
	$has_foreign    = ! empty( $oc['has_foreign'] );
	$is_active      = ! empty( $oc['active'] );
	$can_enable     = ! empty( $oc['can_enable'] );
	$status_label   = $oc['status_label'] ?? __( 'Inactive', 'pivot-performance-toolkit' );
	$provider       = $oc['provider'] ?? __( 'None', 'pivot-performance-toolkit' );
	$entry_count    = (int) ( $oc['entry_count'] ?? 0 );
	$size_formatted = $oc['size_formatted'] ?? '0 B';
	?>

	<p class="m-0 mb-4 text-sm text-gray-500">
		{{ __( 'Persist WordPress object cache entries to disk between requests, reducing database load and speeding up repeated queries.', 'pivot-performance-toolkit' ) }}
	</p>

	{{-- Stats grid --}}
	<div class="pivot-performance-toolkit-object-cache-stats">
		<div class="pivot-performance-toolkit-stat">
			<span class="pivot-performance-toolkit-stat-label">{{ __( 'Status', 'pivot-performance-toolkit' ) }}</span>
			<strong class="{{ $is_active ? 'text-green-600' : 'text-gray-400' }}">
				{{ $status_label }}
			</strong>
		</div>
		<div class="pivot-performance-toolkit-stat">
			<span class="pivot-performance-toolkit-stat-label">{{ __( 'Provider', 'pivot-performance-toolkit' ) }}</span>
			<strong>{{ $provider }}</strong>
		</div>
		@if ( $is_our_dropin )
			<div class="pivot-performance-toolkit-stat">
				<span class="pivot-performance-toolkit-stat-label">{{ __( 'Entries', 'pivot-performance-toolkit' ) }}</span>
				<strong>{{ number_format( $entry_count ) }}</strong>
			</div>
			<div class="pivot-performance-toolkit-stat">
				<span class="pivot-performance-toolkit-stat-label">{{ __( 'Cache Size', 'pivot-performance-toolkit' ) }}</span>
				<strong>{{ $size_formatted }}</strong>
			</div>
		@else
			<div class="pivot-performance-toolkit-stat">
				<span class="pivot-performance-toolkit-stat-label">{{ __( 'Drop-in', 'pivot-performance-toolkit' ) }}</span>
				<strong>{{ $oc['dropin_label'] ?? __( 'Not installed', 'pivot-performance-toolkit' ) }}</strong>
			</div>
		@endif
	</div>

	{{-- Notice area for AJAX feedback --}}
	<div class="pivot-performance-toolkit-oc-notice" style="display:none"></div>

	{{-- Foreign drop-in warning --}}
	@if ( $has_foreign )
		<div class="notice notice-warning inline mt-4">
			<p class="m-0 text-sm">
				<strong>{{ __( 'External object cache detected.', 'pivot-performance-toolkit' ) }}</strong>
				{{ __( 'Another plugin is managing object-cache.php. Pivot Performance Toolkit will not overwrite it.', 'pivot-performance-toolkit' ) }}
			</p>
		</div>
	@endif

	{{-- Action buttons --}}
	<div class="mt-4 flex flex-wrap gap-2 items-center">

		@if ( ! $is_active && $can_enable )
			<button
				type="button"
				class="button button-primary pivot-performance-toolkit-oc-btn"
				data-oc-action="{{ esc_attr( (string) ( $ajax_enable_object_cache_action ?? '' ) ) }}"
				data-oc-nonce="{{ esc_attr( (string) ( $ajax_enable_object_cache_nonce ?? '' ) ) }}"
				data-oc-reload="1"
			>
				<?php esc_html_e( 'Enable File Object Cache', 'pivot-performance-toolkit' ); ?>
			</button>
		@endif

		@if ( $is_our_dropin )
			<button
				type="button"
				class="button pivot-performance-toolkit-oc-btn"
				data-oc-action="{{ esc_attr( (string) ( $ajax_flush_object_cache_action ?? '' ) ) }}"
				data-oc-nonce="{{ esc_attr( (string) ( $ajax_flush_object_cache_nonce ?? '' ) ) }}"
			>
				<?php esc_html_e( 'Flush Object Cache', 'pivot-performance-toolkit' ); ?>
			</button>

			<button
				type="button"
				class="button pivot-performance-toolkit-oc-btn"
				data-oc-action="{{ esc_attr( (string) ( $ajax_disable_object_cache_action ?? '' ) ) }}"
				data-oc-nonce="{{ esc_attr( (string) ( $ajax_disable_object_cache_nonce ?? '' ) ) }}"
				data-oc-reload="1"
			>
				<?php esc_html_e( 'Disable File Object Cache', 'pivot-performance-toolkit' ); ?>
			</button>
		@endif

	</div>

</x-card>
