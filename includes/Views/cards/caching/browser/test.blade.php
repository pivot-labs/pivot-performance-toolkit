<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Browser Cache Test', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-browser-cache-test">
    <p>
        {{ __('Browser caching stores static assets locally to improve repeat visitor performance and reduce server load.', 'pivot-performance-toolkit') }}
    </p>
    <p>
        {{ __('Test your current browser cache headers and compression configuration.', 'pivot-performance-toolkit') }}
    </p>

    <div style="margin: 16px 0;">
        <button type="button" class="button button-primary" id="pivot-performance-toolkit-run-cache-test">
            {{ __('Run Cache Test', 'pivot-performance-toolkit') }}
        </button>
        <span id="pivot-performance-toolkit-test-status" style="display: none; margin-left: 12px;">
            <span class="spinner" style="float: none; margin: 0;"></span>
            {{ __('Testing...', 'pivot-performance-toolkit') }}
        </span>
    </div>

    <div id="pivot-performance-toolkit-test-results" style="display: none; margin-top: 16px;">
        <table class="pivot-performance-toolkit-table-list" style="width: 100%; margin-top: 12px;">
            <thead>
                <tr>
                    <th>{{ __('Asset', 'pivot-performance-toolkit') }}</th>
                    <th>{{ __('Cache-Control', 'pivot-performance-toolkit') }}</th>
                    <th>{{ __('Compression', 'pivot-performance-toolkit') }}</th>
                    <th>{{ __('Status', 'pivot-performance-toolkit') }}</th>
                </tr>
            </thead>
            <tbody id="pivot-performance-toolkit-test-results-body"></tbody>
        </table>

        <div id="pivot-performance-toolkit-test-summary" style="margin-top: 16px; padding: 12px; background-color: #f0f6fc; border-left: 4px solid #0969da; line-height: 1.6;">
            <p id="pivot-performance-toolkit-test-summary-text"></p>
        </div>
    </div>

    <div id="pivot-performance-toolkit-test-errors" style="display: none; margin-top: 16px; padding: 12px; background-color: #fff5f5; border-left: 4px solid #d63638;">
        <strong>{{ __('Test Error:', 'pivot-performance-toolkit') }}</strong>
        <p id="pivot-performance-toolkit-test-error-text" style="margin: 8px 0 0;"></p>
    </div>
</x-card>

