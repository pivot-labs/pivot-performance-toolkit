<x-admin-layout
    :icon-url="$icon_url"
    :version="$plugin_version"
    :help-url="$help_url"
    :primary-nav="$primary_nav"
>
    <header class="pivot-performance-toolkit-page-header">
        <h1>{{ $page_heading }}</h1>
        @if (is_string($page_description) && $page_description !== '')
            <p>{{ $page_description }}</p>
        @endif
    </header>

    @if (!empty($secondary_nav))
        <nav class="pivot-performance-toolkit-secondary-nav" aria-label="{{ esc_attr__('Section', 'pivot-performance-toolkit') }}">
            @foreach ($secondary_nav as $item)
                <a href="{{ (string) ($item['url'] ?? '#') }}" class="{{ !empty($item['active']) ? 'is-active' : '' }}">
                    {{ esc_html((string) ($item['label'] ?? '')) }}
                </a>
            @endforeach
        </nav>
    @endif

    <div class="pivot-performance-toolkit-grid">
        <div class="pivot-performance-toolkit-main">
            <div class="pivot-performance-toolkit-page-layout pivot-performance-toolkit-page-layout--{{ esc_attr((string) ($page_layout ?? 'two-col')) }}">
                @if (is_string($page_view) && $page_view !== '')
                    @include($page_view, is_array($page_data) ? $page_data : array())
                @else
                    {!! $content !!}
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
