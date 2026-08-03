<x-info-card :title="__('How Auto-Purge Works', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-cdn-about">
    <p>
        {{ __('When enabled, the CDN cache will be automatically cleared whenever the following content is updated:', 'pivot-performance-toolkit') }}
    </p>

    <ul style="list-style: disc;padding-left: .8rem;">
        <li style="display: list-item;margin-bottom: 2px;">{{ __('Posts, pages and custom post types', 'pivot-performance-toolkit') }}</li>
        <li style="display: list-item;margin-bottom: 2px;">{{ __('Media files (images, documents, etc.)', 'pivot-performance-toolkit') }}</li>
        <li style="display: list-item;margin-bottom: 2px;">{{ __('Theme or plugin file changes', 'pivot-performance-toolkit') }}</li>
    </ul>

</x-info-card>