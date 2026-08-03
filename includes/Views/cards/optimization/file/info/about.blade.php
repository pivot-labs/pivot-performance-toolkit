<x-info-card :title="__('About File Optimization', 'pivot-performance-toolkit')">
    @php
        $benefits = array(
            __('Faster page load times', 'pivot-performance-toolkit'),
            __('Reduced bandwidth usage', 'pivot-performance-toolkit'),
            __('Improved Core Web Vitals', 'pivot-performance-toolkit'),
            __('Better user experience', 'pivot-performance-toolkit'),
        );
    @endphp
    <p>
        {{ __('File optimization reduces the size of your HTML, CSS, and JavaScript files by removing unnecessary characters and loading assets more efficiently.', 'pivot-performance-toolkit') }}
    </p>

    <div class="font-bold pb-3.5">{{ __('Benefits', 'pivot-performance-toolkit') }}</div>
    <x-info-list :items="$benefits" />

</x-info-card>