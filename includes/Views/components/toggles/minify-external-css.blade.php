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
    setting-key="minify_external_css"
    :checked="$checked"
    :action="$action"
    :nonce="$nonce"
    :label="__('Minify CSS files', 'pivot-performance-toolkit')"
    :description="__('Creates cached minified copies of local enqueued stylesheet files and rewrites their URLs.', 'pivot-performance-toolkit')"
>
    <x-slot name="icon">
        <x-icons.css
            class="shrink-0"
            style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
        />
    </x-slot>
</x-toggles.toggle>

