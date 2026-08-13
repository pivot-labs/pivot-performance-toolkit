<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Cache exclusions', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-advanced-rules">
	@if ($settings_updated)
		<div class="notice notice-success is-dismissible">
			<p>{{ __('Settings saved successfully.', 'pivot-performance-toolkit') }}</p>
		</div>
	@endif

	<p style="margin:0 0 16px;color:#646970">
		{{ __('Enter URLs or path patterns that should never be cached - one per line. Prefix matching is used by default; add a wildcard (*) for substring patterns.', 'pivot-performance-toolkit') }}
	</p>

	<form method="post" action="{{ esc_url(admin_url('options.php')) }}">
		@php
			settings_fields('pivot_performance_toolkit');

			echo '<input type="hidden" name="' . esc_attr($option_key) . '[enable_page_cache]" value="' . (!empty($options['enable_page_cache']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[cache_ttl]" value="' . esc_attr((string) $options['cache_ttl']) . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[max_cache_size_mb]" value="' . esc_attr((string) $options['max_cache_size_mb']) . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_html]" value="' . (!empty($options['minify_html']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_css]" value="' . (!empty($options['minify_css']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_css]" value="' . (!empty($options['minify_external_css']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_css_exclusions]" value="' . esc_attr((string) $options['minify_external_css_exclusions']) . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_js]" value="' . (!empty($options['minify_external_js']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_external_js_exclusions]" value="' . esc_attr((string) $options['minify_external_js_exclusions']) . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_css]" value="' . (!empty($options['combine_css']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_css_exclusions]" value="' . esc_attr((string) $options['combine_css_exclusions']) . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_js]" value="' . (!empty($options['combine_js']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[combine_js_exclusions]" value="' . esc_attr((string) $options['combine_js_exclusions']) . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[minify_js]" value="' . (!empty($options['minify_js']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[defer_scripts]" value="' . (!empty($options['defer_scripts']) ? '1' : '0') . '" />';
			echo '<input type="hidden" name="' . esc_attr($option_key) . '[lazy_load_images]" value="' . (!empty($options['lazy_load_images']) ? '1' : '0') . '" />';
		@endphp

		<div class="pivot-performance-toolkit-field">
			<label for="pivot-performance-toolkit-excluded-urls"><strong>{{ __('Never-cache URLs', 'pivot-performance-toolkit') }}</strong></label>
			<p>{{ __('Paths are matched from the start of the URL. Use * for wildcards.', 'pivot-performance-toolkit') }}</p>
			<p>
				<button type="button" class="button-link" id="pivot-performance-toolkit-add-woo-exclusions">
					{{ __('Add WooCommerce default exclusions', 'pivot-performance-toolkit') }}
				</button>
			</p>
			<textarea
				id="pivot-performance-toolkit-excluded-urls"
				name="{{ $option_key }}[cache_excluded_urls]"
				class="pivot-performance-toolkit-exclusions-textarea"
				rows="10"
				placeholder="{{ esc_attr("/checkout\n/cart\n/my-account/*\n/wc-api/*") }}"
				spellcheck="false"
			>{{ esc_textarea((string) $options['cache_excluded_urls']) }}</textarea>
			<p class="pivot-performance-toolkit-exclusions-hint">
				{!! wp_kses(
					__('<strong>Examples:</strong> <code>/checkout</code> excludes all URLs starting with /checkout &nbsp;.&nbsp; <code>/my-account/*</code> uses a wildcard &nbsp;.&nbsp; One entry per line.', 'pivot-performance-toolkit'),
					array('strong' => array(), 'code' => array())
				) !!}
			</p>
		</div>

		<div class="pivot-performance-toolkit-field">
			<label for="pivot-performance-toolkit-bypass-cookies"><strong>{{ __('Bypass cache when cookies exist', 'pivot-performance-toolkit') }}</strong></label>
			<p>{{ __('One cookie name or wildcard pattern per line. If a request contains any matching cookie, page cache is bypassed.', 'pivot-performance-toolkit') }}</p>
			<textarea
				id="pivot-performance-toolkit-bypass-cookies"
				name="{{ $option_key }}[cache_bypass_cookies]"
				class="pivot-performance-toolkit-exclusions-textarea"
				rows="6"
				spellcheck="false"
			>{{ esc_textarea((string) $options['cache_bypass_cookies']) }}</textarea>
			<p class="pivot-performance-toolkit-exclusions-hint">
				{!! wp_kses(
					__('<strong>Examples:</strong> <code>woocommerce_items_in_cart</code>, <code>woocommerce_cart_hash</code>, <code>wp_woocommerce_session_*</code>.', 'pivot-performance-toolkit'),
					array('strong' => array(), 'code' => array())
				) !!}
			</p>
		</div>

		@php
			submit_button(__('Save changes', 'pivot-performance-toolkit'));
		@endphp
	</form>

</x-card>

