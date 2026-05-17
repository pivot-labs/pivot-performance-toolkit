<x-info-card :title="__('Testing Your Configuration', 'performance-toolkit')" id="ptk-browser-cache">
    @php
        $testing_items = array(
            sprintf(
                '<a href="https://www.webpagetest.org/" target="_blank" rel="noopener noreferrer">WebPageTest.org</a> %s',
                __('(detailed waterfall + caching info)', 'performance-toolkit')
            ),
            sprintf(
                '<a href="https://gtmetrix.com/" target="_blank" rel="noopener noreferrer">GTmetrix</a> %s',
                __('(cache headers report)', 'performance-toolkit')
            ),
            __('Browser DevTools -> Network tab -> Response Headers', 'performance-toolkit'),
        );
    @endphp

    <p>{{ __('After applying these directives, test your headers using:', 'performance-toolkit') }}</p>
    <x-info-list :items="$testing_items" :allow-html="true" />

</x-info-card>

