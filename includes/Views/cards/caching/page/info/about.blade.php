<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-info-card :title="__('About Page Cache', 'pivot-performance-toolkit')" class="pivot-performance-toolkit-object-cache-card">
	@php
		$benefits = array(
			__('Faster page load times', 'pivot-performance-toolkit'),
			__('Reduced server load', 'pivot-performance-toolkit'),
			__('Better user experience', 'pivot-performance-toolkit'),
			__('Improved SEO rankings', 'pivot-performance-toolkit'),
		);
	@endphp

<p>
	{{ __('Page caching stores a static version of your pages and serves it to visitors, reducing server load and improving response times.', 'pivot-performance-toolkit') }}
</p>

	<h4>{{ __('Benefits:', 'pivot-performance-toolkit') }}</h4>

	<x-info-list :items="$benefits" />



</x-info-card>