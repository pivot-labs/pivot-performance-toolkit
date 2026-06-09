@props([
	'iconUrl',
	'version' => '',
	'helpUrl' => '',
	'primaryNav' => array(),
])

<div class="wrap ptk-wrap">
	<div class="ptk-chrome">
		<header class="ptk-header">
			<div class="ptk-page-title">
				<img src="{{ esc_url($iconUrl) }}" alt="{{ __('WP Performance Toolkit', 'performance-toolkit') }}" class="ptk-page-title-icon" />
				@if (is_string($version) && $version !== '')
					<span class="ptk-status-pill bg-gray-100">v {{ $version }}</span>
				@endif
				<?php do_action( 'ptk_after_version_display' ); ?>

			</div>

			@if (is_string($helpUrl) && $helpUrl !== '')
					<a class="button ptk-button" href="{{ esc_url((string) $helpUrl) }}" target="_blank" rel="noopener noreferrer">{{ __('Help', 'performance-toolkit') }}</a>
			@endif
		</header>

		<nav class="ptk-primary-nav" aria-label="{{ esc_attr__('Primary', 'performance-toolkit') }}">
			@foreach ($primaryNav as $item)
				<a
					href="{{ (string) ($item['url'] ?? '#') }}"
					class="{{ !empty($item['active']) ? 'is-active' : '' }}"
				>
					@if (!empty($item['icon']))
						<span class="ptk-nav-icon" aria-hidden="true">{!! \PerformanceToolkit\Admin\LucideIcons::render((string) $item['icon']) !!}</span>
					@endif
					<span>{{ esc_html((string) ($item['label'] ?? '')) }}</span>
				</a>
			@endforeach
		</nav>

			<details class="ptk-primary-nav-mobile">
				<summary>{{ __('Menu', 'performance-toolkit') }}</summary>
				<nav aria-label="{{ esc_attr__('Primary', 'performance-toolkit') }}">
					@foreach ($primaryNav as $item)
						<a
							href="{{ (string) ($item['url'] ?? '#') }}"
							class="{{ !empty($item['active']) ? 'is-active' : '' }}"
						>
							@if (!empty($item['icon']))
										<span class="ptk-nav-icon" aria-hidden="true">{!! \PerformanceToolkit\Admin\LucideIcons::render((string) $item['icon']) !!}</span>
							@endif
							<span>{{ esc_html((string) ($item['label'] ?? '')) }}</span>
						</a>
					@endforeach
				</nav>
			</details>
	</div>

	<div class="ptk-app">

		<main class="ptk-content">
			{{ $slot }}
		</main>
	</div>
</div>

