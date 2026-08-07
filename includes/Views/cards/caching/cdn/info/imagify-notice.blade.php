<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@if ($show_imagify_cdn_notice ?? false)
    <x-info-card :title="__('Imagify + Cloudflare', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-cdn-imagify-notice">
        <div class="pivot-performance-toolkit-warning-box">
            <strong>{{ __('Heads up:', 'pivot-performance-toolkit') }}</strong>
            <span>
                {{ __('Imagify was detected, and its default WebP delivery method (server rewrite rules) is known to break behind a CDN, including Cloudflare. In Imagify\'s settings, switch WebP delivery to the "<picture> tag" method to avoid this — it works reliably with Cloudflare.', 'pivot-performance-toolkit') }}
            </span>
        </div>
    </x-info-card>
@endif
