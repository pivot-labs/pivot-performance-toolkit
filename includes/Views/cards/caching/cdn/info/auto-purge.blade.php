<x-info-card :title="__('How Auto-Purge Works', 'performance-toolkit')" id="ptk-cdn-about">
    <p>
        {{ __('When enabled, the CDN cache will be automatically cleared whenever the following content is updated:', 'performance-toolkit') }}
    </p>

    <ul style="list-style: disc;padding-left: .8rem;">
        <li style="display: list-item;margin-bottom: 2px;">{{ __('Posts, pages and custom post types', 'performance-toolkit') }}</li>
        <li style="display: list-item;margin-bottom: 2px;">{{ __('Media files (images, documents, etc.)', 'performance-toolkit') }}</li>
        <li style="display: list-item;margin-bottom: 2px;">{{ __('Theme or plugin file changes', 'performance-toolkit') }}</li>
    </ul>

</x-info-card>