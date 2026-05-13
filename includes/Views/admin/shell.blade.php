<x-admin-layout :icon-url="$icon_url">
    @if (is_string($page_view) && $page_view !== '')
        @include($page_view, is_array($page_data) ? $page_data : array())
    @else
        {!! $content !!}
    @endif
</x-admin-layout>
