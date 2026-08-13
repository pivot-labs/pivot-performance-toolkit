<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@if ($cache_purged)
	<div class="notice notice-success is-dismissible">
		<p>{{ __('Pivot Performance Toolkit cache was purged.', 'pivot-performance-toolkit') }}</p>
	</div>
@endif

@if ($page_cache_purged)
	<div class="notice notice-success is-dismissible">
		<p>{{ __("This page's cache was purged.", 'pivot-performance-toolkit') }}</p>
	</div>
@endif

