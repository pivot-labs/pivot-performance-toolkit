<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Documentation', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-documentation">
    <p>{{ __('Full guides, setup instructions, and troubleshooting are available in the external documentation site.', 'pivot-performance-toolkit') }}</p>
    <p>
        <a
            class="button button-primary"
            href="{{ esc_url($docs_url) }}"
            target="_blank"
            rel="noopener noreferrer"
        >
            {{ __('Open Documentation', 'pivot-performance-toolkit') }}
        </a>
    </p>
</x-card>

