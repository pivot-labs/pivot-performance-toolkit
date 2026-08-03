<x-card :title="__('Server Detection', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-browser-cache-detection">
    @php
        $server_label = __('Apache', 'pivot-performance-toolkit');

        if (stripos((string) $server_software, 'nginx') !== false) {
            $server_label = __('Nginx Server', 'pivot-performance-toolkit');
        } elseif (stripos((string) $server_software, 'apache') !== false) {
            $server_label = __('Apache Server', 'pivot-performance-toolkit');
        }
    @endphp

    <p>
        {{ __('We detected your current web server software to help you choose the correct browser cache and compression configuration.', 'pivot-performance-toolkit') }}
    </p>

    <div style="margin-top: 20px; padding: 12px; background-color: #e7f3ff; border-left: 4px solid #0969da;">
        <p style="margin: 0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <strong>{{ __('Detected Environment:', 'pivot-performance-toolkit') }}</strong>
            <code>{{ $server_software !== '' ? $server_software : __('Unknown', 'pivot-performance-toolkit') }}</code>
            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                {{ $server_label }}
            </span>
        </p>
    </div>
</x-card>
