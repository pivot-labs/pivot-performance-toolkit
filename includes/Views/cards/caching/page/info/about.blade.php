<x-info-card :title="__('About Page Cache', 'performance-toolkit')" class="ptk-object-cache-card">
    @php
        $benefits = array(
            __('Faster page load times', 'performance-toolkit'),
            __('Reduced server load', 'performance-toolkit'),
            __('Better user experience', 'performance-toolkit'),
            __('Improved SEO rankings', 'performance-toolkit'),
        );
    @endphp

<p>
    {{ __('Page caching stores a static version of your pages and serves it to visitors, reducing server load and improving response times.', 'performance-toolkit') }}
</p>

    <h4>{{ __('Benefits:', 'performance-toolkit') }}</h4>

    <x-info-list :items="$benefits" />



</x-info-card>