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
    setting-key="minify_external_js"
    :checked="$checked"
    :action="$action"
    :nonce="$nonce"
    :label="__('Minify JavaScript files', 'pivot-performance-toolkit')"
    :description="__('Creates cached minified copies of local enqueued JavaScript files and rewrites their URLs.', 'pivot-performance-toolkit')"
>
    <x-slot name="icon">
        <x-icons.js
            class="shrink-0"
            style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
        />
    </x-slot>
</x-toggles.toggle>

