<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-info-card :title="__('Testing Your Configuration', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-browser-cache">
	@php
		$testing_items = array(
			sprintf(
				'<a href="https://www.webpagetest.org/" target="_blank" rel="noopener noreferrer">WebPageTest.org</a> %s',
				__('(detailed waterfall + caching info)', 'pivot-performance-toolkit')
			),
			sprintf(
				'<a href="https://gtmetrix.com/" target="_blank" rel="noopener noreferrer">GTmetrix</a> %s',
				__('(cache headers report)', 'pivot-performance-toolkit')
			),
			__('Browser DevTools -> Network tab -> Response Headers', 'pivot-performance-toolkit'),
		);
	@endphp

	<p>{{ __('After applying these directives, test your headers using:', 'pivot-performance-toolkit') }}</p>
	<x-info-list :items="$testing_items" :allow-html="true" />

</x-info-card>

