<x-info-card :title="__('About File Optimization', 'performance-toolkit')">
    @php
        $benefits = array(
            __('Faster page load times', 'performance-toolkit'),
            __('Reduced bandwidth usage', 'performance-toolkit'),
            __('Improved Core Web Vitals', 'performance-toolkit'),
            __('Better user experience', 'performance-toolkit'),
        );
    @endphp
    <p>
        {{ __('File optimization reduces the size of your HTML, CSS, and JavaScript files by removing unnecessary characters and loading assets more efficiently.', 'performance-toolkit') }}
    </p>

    <div class="font-bold pb-3.5">{{ __('Benefits', 'performance-toolkit') }}</div>
    <x-info-list :items="$benefits" />

</x-info-card>