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
    setting-key="minify_html"
    :checked="$checked"
    :action="$action"
    :nonce="$nonce"
    :label="__('Minify HTML output', 'pivot-performance-toolkit')"
    :description="__('Removes non-essential whitespace and safe HTML comments from frontend output.', 'pivot-performance-toolkit')"
>
    <x-slot name="icon">
        <x-icons.html
            class="shrink-0"
            style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
        />
    </x-slot>
</x-toggles.toggle>

