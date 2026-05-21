<x-card :title="__('Optimization Recommendation', 'performance-toolkit')">
@php
    $benefits_list = array(
        __('Defer JavaScript', 'performance-toolkit'),
        __('Minify CSS', 'performance-toolkit'),
        __('Minify JavaScript', 'performance-toolkit'),
    );
@endphp

    <p>
    {{ __('Based on your server and setup, we recommend the following:', 'performance-toolkit') }}
    </p>

    <div class="rounded-md border border-emerald-300 p-2.5 bg-emerald-50">
        <p class="font-bold text-emerald-700">
            {{ __('Enable these for best results', 'performance-toolkit') }}
        </p>
        <x-info-list :items="$benefits_list" />
    </div>

    @if (!empty($is_http11))
        <div class="rounded-md border border-emerald-300 p-2.5 mt-5 bg-emerald-50">
            <p class="font-bold text-emerald-700">{{ __('HTTP/1.1 Detected', 'performance-toolkit') }}</p>
            <p class="text-emerald-700">
                {{ __('Your server appears to be using HTTP/1.1. Combining CSS and JavaScript files may reduce requests and improve page load performance. We recommend enabling file combination and testing key pages for compatibility.', 'performance-toolkit') }}
            </p>
        </div>
    @elseif (!empty($http_protocol_version) && 'unknown' !== strtolower((string) $http_protocol_version))
        <div class="rounded-md border border-amber-300 p-2.5 mt-5 bg-amber-50">
            <p class="font-bold text-amber-700">{{ sprintf(__('HTTP/%s Detected', 'performance-toolkit'), (string) $http_protocol_version) }}</p>
            <p class="text-amber-700">
                {{ __('Your server appears to support HTTP/2 or HTTP/3. File combination is generally not recommended on modern protocols, as parallel asset loading is already optimized by the browser and server.', 'performance-toolkit') }}
            </p>
        </div>
    @else
        <div class="rounded-md border border-gray-300 p-2.5 mt-5 bg-gray-50">
            <p class="font-bold text-gray-700">{{ __('HTTP Protocol Not Confirmed', 'performance-toolkit') }}</p>
            <p class="text-gray-700">
                {{ __('We could not confidently detect the active HTTP protocol. Keep file combination disabled by default, and only enable it if testing confirms HTTP/1.1 traffic.', 'performance-toolkit') }}
            </p>
        </div>
    @endif

    <div class="rounded-md border border-gray-300 p-2.5 mt-5 bg-gray-50">
        <p class="font-bold text-gray-700">
            {{ __('Consider enabling', 'performance-toolkit') }}
        </p>
        <ul class="list-disc" style="list-style: disc; padding-left: 1rem;">
            <li>{{ __('Minify HTML', 'performance-toolkit') }} </li>
            <li>{{ __('Minify Inline CSS', 'performance-toolkit') }} </li>
            <li>{{ __('Minify Inline JavaScript', 'performance-toolkit') }} </li>
        </ul>
    </div>

</x-card>