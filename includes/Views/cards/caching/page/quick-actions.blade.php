<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Quick Actions', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-cache-quick-actions">
	<div class="pivot-performance-toolkit-action-list">
		<button
			type="button"
			class="pivot-performance-toolkit-action-btn"
			data-ajax-action="{{ esc_attr((string) ($ajax_clear_action ?? 'pivot_performance_toolkit_ajax_clear_cache')) }}"
			data-ajax-nonce="{{ esc_attr((string) ($ajax_clear_nonce ?? '')) }}"
			data-success-message="{{ esc_attr((string) ($cache_cleared_message ?? __('Cache cleared successfully.', 'pivot-performance-toolkit'))) }}"
		>
			<span class="pivot-performance-toolkit-action-text">
				<span class="pivot-performance-toolkit-action-title">{{ __('Purge All Caches', 'pivot-performance-toolkit') }}</span>
				<span class="pivot-performance-toolkit-action-desc">{{ __('Clear all cached files (page, minified assets, etc.)', 'pivot-performance-toolkit') }}</span>
			</span>
			<span class="pivot-performance-toolkit-action-indicator">
				<span class="spinner" style="float: none; margin: 0;"></span>
				<span class="pivot-performance-toolkit-action-arrow" aria-hidden="true">&gt;</span>
			</span>
		</button>

		<button
			type="button"
			class="pivot-performance-toolkit-action-btn"
			data-ajax-action="{{ esc_attr((string) ($ajax_clear_minified_action ?? 'pivot_performance_toolkit_ajax_clear_minified_cache')) }}"
			data-ajax-nonce="{{ esc_attr((string) ($ajax_clear_minified_nonce ?? '')) }}"
			data-success-message="{{ esc_attr((string) ($minified_cache_cleared_message ?? __('Minified CSS/JS cache cleared successfully.', 'pivot-performance-toolkit'))) }}"
		>
			<span class="pivot-performance-toolkit-action-text">
				<span class="pivot-performance-toolkit-action-title">{{ __('Clear Minified CSS/JS Cache', 'pivot-performance-toolkit') }}</span>
				<span class="pivot-performance-toolkit-action-desc">{{ __('Remove all cached minified CSS and JavaScript files', 'pivot-performance-toolkit') }}</span>
			</span>
			<span class="pivot-performance-toolkit-action-indicator">
				<span class="spinner" style="float: none; margin: 0;"></span>
				<span class="pivot-performance-toolkit-action-arrow" aria-hidden="true">&gt;</span>
			</span>
		</button>

		<a
			href="#"
			class="pivot-performance-toolkit-action-btn"
			role="button"
			data-ajax-action="{{ esc_attr((string) ($ajax_preload_action ?? 'pivot_performance_toolkit_ajax_preload_cache')) }}"
			data-ajax-nonce="{{ esc_attr((string) ($ajax_preload_nonce ?? '')) }}"
			data-success-message="{{ esc_attr((string) ($preload_cache_message ?? __('Preload started. This can take a moment.', 'pivot-performance-toolkit'))) }}"
		>
			<span class="pivot-performance-toolkit-action-text">
				<span class="pivot-performance-toolkit-action-title">{{ __('Preload Cache', 'pivot-performance-toolkit') }}</span>
				<span class="pivot-performance-toolkit-action-desc">{{ __('Generate cache files for your most visited pages.', 'pivot-performance-toolkit') }}</span>
			</span>
			<span class="pivot-performance-toolkit-action-indicator">
				<span class="spinner" style="float: none; margin: 0;"></span>
				<span class="pivot-performance-toolkit-action-arrow" aria-hidden="true">&gt;</span>
			</span>
		</a>
	</div>
</x-card>
