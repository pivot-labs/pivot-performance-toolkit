<x-info-card :title="__('Need Help', 'performance-toolkit')">
	@php
		$external_link_icon = \PerformanceToolkit\Admin\LucideIcons::render('external-link');
		$external_link_icon = str_replace('<svg ', '<svg class="h-4 w-4" ', $external_link_icon);
	@endphp

	<p>{{ __('Learn more about cache rules and exclusions.', 'performance-toolkit') }}</p>

	<ul style="margin: 12px 0 0; padding-left: 0; list-style: none; display: grid; gap: 8px;">
		<li>
			<a href="https://docs.wpperformance-toolkit.com" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
				<span>{{ __('View Documentation', 'performance-toolkit') }}</span>
				{!! $external_link_icon !!}
			</a>
		</li>
		<li>
			<a href="#" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
				<span>{{ __('Cache Rule Examples', 'performance-toolkit') }}</span>
				{!! $external_link_icon !!}
			</a>
		</li>
		<li>
			<a href="#" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
				<span>{{ __('Troubleshooting Guide', 'performance-toolkit') }}</span>
				{!! $external_link_icon !!}
			</a>
		</li>
	</ul>
</x-info-card>
