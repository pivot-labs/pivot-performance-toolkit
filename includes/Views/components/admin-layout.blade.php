@props([
    'iconUrl',
    'version' => '',
    'helpUrl' => '',
    'primaryNav' => array(),
])

<div class="wrap ptk-wrap">
    <div class="ptk-app">
        <header class="ptk-header">
            <div class="ptk-page-title">
                <img src="{{ esc_url($iconUrl) }}" alt="" class="ptk-page-title-icon" />
                <span>{{ __('Performance Toolkit', 'performance-toolkit') }}</span>
                @if (is_string($version) && $version !== '')
                    <span class="ptk-status-pill">v{{ $version }}</span>
                @endif
            </div>

            @if (is_string($helpUrl) && $helpUrl !== '')
                <a class="button ptk-button" href="{{ (string) $helpUrl }}">{{ __('Help', 'performance-toolkit') }}</a>
            @endif
        </header>

        <nav class="ptk-primary-nav" aria-label="{{ esc_attr__('Primary', 'performance-toolkit') }}">
            @foreach ($primaryNav as $item)
                <a
                    href="{{ (string) ($item['url'] ?? '#') }}"
                    class="{{ !empty($item['active']) ? 'is-active' : '' }}"
                >
                    @if (!empty($item['icon']))
                        <span class="dashicons {{ esc_attr((string) $item['icon']) }}" aria-hidden="true"></span>
                    @endif
                    <span>{{ esc_html((string) ($item['label'] ?? '')) }}</span>
                </a>
            @endforeach
        </nav>

        <main class="ptk-content">
            {{ $slot }}
        </main>
    </div>
</div>

