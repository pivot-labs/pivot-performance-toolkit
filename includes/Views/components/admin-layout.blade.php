@props([
	'iconUrl',
	'version' => '',
	'helpUrl' => '',
	'primaryNav' => array(),
])

<div class="wrap pivot-performance-toolkit-wrap">
	<div class="pivot-performance-toolkit-chrome">
		<header class="pivot-performance-toolkit-header">
			<div class="pivot-performance-toolkit-page-title">
				<img src="{{ esc_url($iconUrl) }}" alt="{{ __('Pivot Performance Toolkit', 'pivot-performance-toolkit') }}" class="pivot-performance-toolkit-page-title-icon" />
				@if (is_string($version) && $version !== '')
					<span class="pivot-performance-toolkit-status-pill bg-gray-100">v {{ $version }}</span>
				@endif
				<?php do_action( 'pivot_performance_toolkit_after_version_display' ); ?>

			</div>

			@if (is_string($helpUrl) && $helpUrl !== '')
					<a class="button pivot-performance-toolkit-button" href="{{ esc_url((string) $helpUrl) }}" target="_blank" rel="noopener noreferrer">{{ __('Help', 'pivot-performance-toolkit') }}</a>
			@endif
		</header>

		<nav class="pivot-performance-toolkit-primary-nav" aria-label="{{ esc_attr__('Primary', 'pivot-performance-toolkit') }}">
			@foreach ($primaryNav as $item)
				<a
					href="{{ (string) ($item['url'] ?? '#') }}"
					class="{{ !empty($item['active']) ? 'is-active' : '' }}"
				>
					@if (!empty($item['icon']))
						<span class="pivot-performance-toolkit-nav-icon" aria-hidden="true">{!! \PivotPerformanceToolkit\Admin\LucideIcons::render((string) $item['icon']) !!}</span>
					@endif
					<span>{{ esc_html((string) ($item['label'] ?? '')) }}</span>
				</a>
			@endforeach
		</nav>

			<details class="pivot-performance-toolkit-primary-nav-mobile">
				<summary>{{ __('Menu', 'pivot-performance-toolkit') }}</summary>
				<nav aria-label="{{ esc_attr__('Primary', 'pivot-performance-toolkit') }}">
					@foreach ($primaryNav as $item)
						<a
							href="{{ (string) ($item['url'] ?? '#') }}"
							class="{{ !empty($item['active']) ? 'is-active' : '' }}"
						>
							@if (!empty($item['icon']))
										<span class="pivot-performance-toolkit-nav-icon" aria-hidden="true">{!! \PivotPerformanceToolkit\Admin\LucideIcons::render((string) $item['icon']) !!}</span>
							@endif
							<span>{{ esc_html((string) ($item['label'] ?? '')) }}</span>
						</a>
					@endforeach
				</nav>
			</details>
	</div>

	<div class="pivot-performance-toolkit-app">

		<main class="pivot-performance-toolkit-content">
			{{ $slot }}
		</main>
	</div>
</div>

