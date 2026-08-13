<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-info-card :title="__('Browser Caching', 'pivot-performance-toolkit')">
	@php
		$benefits = array(
			__('Faster repeat page loads for returning visitors', 'pivot-performance-toolkit'),
			__('Reduced bandwidth and server resource usage', 'pivot-performance-toolkit'),
			__('Improved Core Web Vitals and perceived performance', 'pivot-performance-toolkit'),
			__('Fewer HTTP requests for static assets', 'pivot-performance-toolkit'),
			__('Better scalability during traffic spikes', 'pivot-performance-toolkit'),
			__('Reduced load on shared hosting environments', 'pivot-performance-toolkit'),
		);
	@endphp

	<p>
		{{ __('Browser caching stores static website assets like CSS, JavaScript, fonts, and images in a visitor’s browser so repeat visits load significantly faster and reduce server requests.', 'pivot-performance-toolkit') }}
	</p>

	<h4>{{ __('Benefits', 'pivot-performance-toolkit') }}</h4>

	<x-info-list :items="$benefits" />
</x-info-card>


