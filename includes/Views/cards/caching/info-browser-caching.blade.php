<x-info-card :title="__('Browser Caching', 'performance-toolkit')">
    @php
        $benefits = array(
            __('Faster repeat page loads for returning visitors', 'performance-toolkit'),
            __('Reduced bandwidth and server resource usage', 'performance-toolkit'),
            __('Improved Core Web Vitals and perceived performance', 'performance-toolkit'),
            __('Fewer HTTP requests for static assets', 'performance-toolkit'),
            __('Better scalability during traffic spikes', 'performance-toolkit'),
            __('Reduced load on shared hosting environments', 'performance-toolkit'),
        );
    @endphp

    <p>
        {{ __('Browser caching stores static website assets like CSS, JavaScript, fonts, and images in a visitor’s browser so repeat visits load significantly faster and reduce server requests.', 'performance-toolkit') }}
    </p>

    <h4>{{ __('Benefits', 'performance-toolkit') }}</h4>

    <x-info-list :items="$benefits" />
</x-info-card>


