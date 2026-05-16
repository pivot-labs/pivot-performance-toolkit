<x-card :title="__('Browser Cache & Compression Settings', 'performance-toolkit')" id="ptk-browser-cache">

    @php
        $is_nginx      = stripos((string) ($server_software ?? ''), 'nginx') !== false;
        $primary       = $is_nginx ? 'nginx'  : 'apache';
        $secondary     = $is_nginx ? 'apache' : 'nginx';
        $secondary_label = $is_nginx
            ? __('Apache .htaccess Configuration', 'performance-toolkit')
            : __('Nginx Configuration', 'performance-toolkit');
        $secondary_description = __('Even if another server is detected, your hosting environment may use Apache or Nginx as a reverse proxy for static assets or PHP processing.', 'performance-toolkit');
    @endphp

    <div style="margin: 16px 0;">
        <h4 style="margin: 0 0 8px;">{{ __('What this does:', 'performance-toolkit') }}</h4>
        <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
            <li>{{ __('Sets browser cache expiration times (1 year for versioned assets, 1 month for images)', 'performance-toolkit') }}</li>
            <li>{{ __('Enables Gzip compression for HTML, CSS, and JavaScript', 'performance-toolkit') }}</li>
            <li>{{ __('Adds cache-control headers to improve hit rates', 'performance-toolkit') }}</li>
            <li>{{ __('Adds security headers to prevent MIME-sniffing', 'performance-toolkit') }}</li>
        </ul>
    </div>

    {{-- Primary: detected server --}}
    <div style="margin-top: 24px;">
        @include('cards.caching.browser-cache-settings.' . $primary)
    </div>

    {{-- Secondary: the other server, collapsed inside a disclosure --}}
    <details class="ptk-server-details">
        <summary class="ptk-server-details__summary">
            <span class="ptk-server-details__summary-text">
                <span class="ptk-server-details__summary-title">{{ $secondary_label }}</span>
                <span class="ptk-server-details__summary-description">{{ $secondary_description }}</span>
            </span>
        </summary>
        <div class="ptk-server-details__body">
            @include('cards.caching.browser-cache-settings.' . $secondary)
        </div>
    </details>

</x-card>

