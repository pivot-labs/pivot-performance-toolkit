<x-card :title="__('Browser Cache & Compression Settings', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-browser-cache">

    @php
        $is_nginx      = stripos((string) ($server_software ?? ''), 'nginx') !== false;
        $primary       = $is_nginx ? 'nginx'  : 'apache';
        $secondary     = $is_nginx ? 'apache' : 'nginx';
        $secondary_label = $is_nginx
            ? __('Apache .htaccess Configuration', 'pivot-performance-toolkit')
            : __('Nginx Configuration', 'pivot-performance-toolkit');
        $secondary_description = __('Even if another server is detected, your hosting environment may use Apache or Nginx as a reverse proxy for static assets or PHP processing.', 'pivot-performance-toolkit');
    @endphp

    <div style="margin: 16px 0;">
        <h4 style="margin: 0 0 8px;">{{ __('What this does:', 'pivot-performance-toolkit') }}</h4>
        <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
            <li>{{ __('Sets browser cache expiration times (1 year for versioned assets, 1 month for images)', 'pivot-performance-toolkit') }}</li>
            <li>{{ __('Enables Gzip compression for HTML, CSS, and JavaScript', 'pivot-performance-toolkit') }}</li>
            <li>{{ __('Adds cache-control headers to improve hit rates', 'pivot-performance-toolkit') }}</li>
            <li>{{ __('Adds security headers to prevent MIME-sniffing', 'pivot-performance-toolkit') }}</li>
        </ul>
    </div>

    {{-- Primary: detected server --}}
    <div style="margin-top: 24px;">
        @include('cards.caching.browser-cache-settings.' . $primary)
    </div>

    {{-- Secondary: the other server, collapsed inside a disclosure --}}
    <details class="pivot-performance-toolkit-server-details">
        <summary class="pivot-performance-toolkit-server-details__summary">
            <span class="pivot-performance-toolkit-server-details__summary-text">
                <span class="pivot-performance-toolkit-server-details__summary-title">{{ $secondary_label }}</span>
                <span class="pivot-performance-toolkit-server-details__summary-description">{{ $secondary_description }}</span>
            </span>
        </summary>
        <div class="pivot-performance-toolkit-server-details__body">
            @include('cards.caching.browser-cache-settings.' . $secondary)
        </div>
    </details>

</x-card>

