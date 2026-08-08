<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Quick Optimizations', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-file-quick">
    <ul role="list" class="grid grid-cols-1 gap-4 items-stretch">
        <x-toggles.defer
            :checked="!empty($options['defer_scripts'])"
            :action="$ajax_save_quick_toggle_action"
            :nonce="$ajax_save_quick_toggle_nonce"
        />
        <x-toggles.delay-js
            :checked="!empty($options['delay_js_execution'])"
            :action="$ajax_save_quick_toggle_action"
            :nonce="$ajax_save_quick_toggle_nonce"
        />
        <x-toggles.minify-html
            :checked="!empty($options['minify_html'])"
            :action="$ajax_save_quick_toggle_action"
            :nonce="$ajax_save_quick_toggle_nonce"
        />
        <x-toggles.minify-css
            :checked="!empty($options['minify_css'])"
            :action="$ajax_save_quick_toggle_action"
            :nonce="$ajax_save_quick_toggle_nonce"
        />
        <x-toggles.minify-external-css
            :checked="!empty($options['minify_external_css'])"
            :action="$ajax_save_quick_toggle_action"
            :nonce="$ajax_save_quick_toggle_nonce"
        />
        <x-toggles.minify-external-js
            :checked="!empty($options['minify_external_js'])"
            :action="$ajax_save_quick_toggle_action"
            :nonce="$ajax_save_quick_toggle_nonce"
        />
        <x-toggles.minify-js
            :checked="!empty($options['minify_js'])"
            :action="$ajax_save_quick_toggle_action"
            :nonce="$ajax_save_quick_toggle_nonce"
        />
    </ul>
</x-card>







