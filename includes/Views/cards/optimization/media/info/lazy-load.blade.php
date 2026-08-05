<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-info-card :title="__('Lazy Loading', 'pivot-performance-toolkit')">
    @php
        $benefits = array(
            __('Delays loading off-screen images until they are needed', 'pivot-performance-toolkit'),
            __('Reduces initial page load time for image-heavy pages', 'pivot-performance-toolkit'),
            __('Improves perceived page speed during initial rendering', 'pivot-performance-toolkit'),
            __('Can improve Core Web Vitals metrics such as Largest Contentful Paint (LCP)', 'pivot-performance-toolkit'),
        );
    @endphp
    <p>
        {{ __('Lazy loading works by delaying the loading of off-screen images, videos, and iframes until they are close to entering the visitor’s viewport. Instead of downloading all media immediately during the initial page load, assets are loaded only when needed as the user scrolls through the page.', 'pivot-performance-toolkit') }}
    </p>

    <div class="font-bold pb-3.5">{{ __('Benefits', 'pivot-performance-toolkit') }}</div>
    <x-info-list :items="$benefits" />

</x-info-card>