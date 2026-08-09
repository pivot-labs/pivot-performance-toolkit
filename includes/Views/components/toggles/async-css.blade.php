<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props([
    'action' => '',
    'nonce' => '',
    'checked' => false,
])

<x-toggles.toggle
    setting-key="async_css_loading"
    :checked="$checked"
    :action="$action"
    :nonce="$nonce"
    :label="__('Load CSS asynchronously', 'pivot-performance-toolkit')"
    :description="__('Eliminates render-blocking stylesheets by loading them without blocking first paint. May briefly show unstyled content on slow connections.', 'pivot-performance-toolkit')"
>
    <x-slot name="icon">
        <x-icons.css
            class="shrink-0 border-gray-200 bg-gray-100 text-gray-600"
            style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
        />
    </x-slot>
</x-toggles.toggle>
