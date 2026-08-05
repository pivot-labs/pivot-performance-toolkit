<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-info-card :title="__('About CDN', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-cdn-about">
    <p>
        {{ __('A CDN delivers your website\'s static files (CSS, JS, images, etc.) from global servers closer to your visitors for faster load times.', 'pivot-performance-toolkit') }}
    </p>


    <h4>{{ __('Benefits:', 'pivot-performance-toolkit') }}</h4>

    <x-list :items="[
        __('Faster global page load times', 'pivot-performance-toolkit'),
        __('Reduced server load and bandwidth', 'pivot-performance-toolkit'),
        __('Improved Core Web Vitals', 'pivot-performance-toolkit'),
        __('Better reliability and availability', 'pivot-performance-toolkit'),
        __('Built-in security and DoS protection', 'pivot-performance-toolkit'),
    ]" />


</x-info-card>