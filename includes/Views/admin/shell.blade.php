<x-admin-layout
    :icon-url="$icon_url"
    :version="$plugin_version"
    :help-url="$help_url"
    :primary-nav="$primary_nav"
>
    <header class="ptk-page-header">
        <h1>{{ $page_heading }}</h1>
        @if (is_string($page_description) && $page_description !== '')
            <p>{{ $page_description }}</p>
        @endif
    </header>

    @if (!empty($secondary_nav))
        <nav class="ptk-secondary-nav" aria-label="{{ esc_attr__('Section', 'performance-toolkit') }}">
            @foreach ($secondary_nav as $item)
                <a href="{{ (string) ($item['url'] ?? '#') }}" class="{{ !empty($item['active']) ? 'is-active' : '' }}">
                    {{ esc_html((string) ($item['label'] ?? '')) }}
                </a>
            @endforeach
        </nav>
    @endif

    <div class="ptk-grid">
        <div class="ptk-main">
            <div class="ptk-page-layout ptk-page-layout--{{ esc_attr((string) ($page_layout ?? 'two-col')) }}">
                @if (is_string($page_view) && $page_view !== '')
                    @include($page_view, is_array($page_data) ? $page_data : array())
                @else
                    {!! $content !!}
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
