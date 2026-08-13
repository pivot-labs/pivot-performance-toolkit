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
	setting-key="delay_js_execution"
	:checked="$checked"
	:action="$action"
	:nonce="$nonce"
	:label="__('Delay JavaScript execution', 'pivot-performance-toolkit')"
	:description="__('Holds non-critical scripts until the first user interaction (scroll, click, keypress) or an 8-second fallback, whichever comes first.', 'pivot-performance-toolkit')"
>
	<x-slot name="icon">
		<x-icons.js
			class="shrink-0 border-gray-200 bg-gray-100 text-gray-600"
			style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
		/>
	</x-slot>
</x-toggles.toggle>
