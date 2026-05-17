<x-info-card :title="__('Cache-Busting Strategy', 'performance-toolkit')">
	<p>
		{{ __('With 1-year browser cache on versioned assets (like style.css?v=123), you need a way to invalidate old versions when you update. WordPress theme/plugin versioning handles this automatically through query strings.', 'performance-toolkit') }}
	</p>

	<p style="margin: 0;">
		{{ __('Always clear your server cache and CDN cache when updating themes, plugins, or custom code.', 'performance-toolkit') }}
	</p>
</x-info-card>
