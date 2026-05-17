<x-card :title="__('Server Detection', 'performance-toolkit')" id="ptk-browser-cache-detection">
    @php
        $server_label = 'Apache';

        if (stripos((string) $server_software, 'nginx') !== false) {
            $server_label = 'Nginx Server';
        } elseif (stripos((string) $server_software, 'apache') !== false) {
            $server_label = 'Apache Server';
        }
    @endphp

    <p>
        {{ __('We detected your current web server software to help you choose the correct browser cache and compression configuration.', 'performance-toolkit') }}
    </p>

    <div style="margin-top: 20px; padding: 12px; background-color: #e7f3ff; border-left: 4px solid #0969da;">
        <p style="margin: 0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <strong>{{ __('Detected Environment:', 'performance-toolkit') }}</strong>
            <code>{{ $server_software !== '' ? $server_software : 'Unknown' }}</code>
            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                {{ $server_label }}
            </span>
        </p>
    </div>
</x-card>

