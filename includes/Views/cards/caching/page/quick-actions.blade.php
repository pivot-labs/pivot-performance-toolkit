<x-card :title="__('Quick Actions', 'performance-toolkit')" id="ptk-cache-quick-actions">
	<div class="ptk-action-list">
		<button
			type="button"
			class="ptk-action-btn"
			data-ajax-action="{{ esc_attr((string) ($ajax_clear_action ?? 'performance_toolkit_ajax_clear_cache')) }}"
			data-ajax-nonce="{{ esc_attr((string) ($ajax_clear_nonce ?? '')) }}"
			data-success-message="{{ esc_attr((string) ($cache_cleared_message ?? __('Cache cleared successfully.', 'performance-toolkit'))) }}"
		>
			<span class="ptk-action-text">
				<span class="ptk-action-title">{{ __('Purge All Caches', 'performance-toolkit') }}</span>
				<span class="ptk-action-desc">{{ __('Clear all cached files (page, minified assets, etc.)', 'performance-toolkit') }}</span>
			</span>
			<span class="ptk-action-arrow" aria-hidden="true">&gt;</span>
		</button>

		<button
			type="button"
			class="ptk-action-btn"
			data-ajax-action="{{ esc_attr((string) ($ajax_clear_minified_action ?? 'performance_toolkit_ajax_clear_minified_cache')) }}"
			data-ajax-nonce="{{ esc_attr((string) ($ajax_clear_minified_nonce ?? '')) }}"
			data-success-message="{{ esc_attr((string) ($minified_cache_cleared_message ?? __('Minified CSS/JS cache cleared successfully.', 'performance-toolkit'))) }}"
		>
			<span class="ptk-action-text">
				<span class="ptk-action-title">{{ __('Clear Minified CSS/JS Cache', 'performance-toolkit') }}</span>
				<span class="ptk-action-desc">{{ __('Remove all cached minified CSS and JavaScript files', 'performance-toolkit') }}</span>
			</span>
			<span class="ptk-action-arrow" aria-hidden="true">&gt;</span>
		</button>

		<a
			href="#"
			class="ptk-action-btn"
			role="button"
			data-ajax-action="{{ esc_attr((string) ($ajax_refresh_usage_action ?? 'performance_toolkit_ajax_refresh_cache_usage')) }}"
			data-ajax-nonce="{{ esc_attr((string) ($ajax_refresh_usage_nonce ?? '')) }}"
			data-success-message="{{ esc_attr((string) ($preload_not_implemented_message ?? __('Preload cache is not implemented yet.', 'performance-toolkit'))) }}"
		>
			<span class="ptk-action-text">
				<span class="ptk-action-title">{{ __('Preload Cache', 'performance-toolkit') }}</span>
				<span class="ptk-action-desc">{{ __('Generate cache files for your most visited pages.', 'performance-toolkit') }}</span>
			</span>
			<span class="ptk-action-arrow" aria-hidden="true">&gt;</span>
		</a>
	</div>
</x-card>
