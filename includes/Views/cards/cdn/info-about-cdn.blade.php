<x-info-card :title="__('About CDN', 'performance-toolkit')" id="ptk-cdn-about">
    <p>
        A CDN delivers your website's static files (CSS, JS, images, etc.) from global servers closer to your visitors for faster load times.
    </p>


    <h4>Benefits:</h4>

    <x-list :items="[
        __('Faster global page load times', 'performance-toolkit'),
        __('Reduced server load and bandwidth', 'performance-toolkit'),
        __('Improved Core Web Vitals', 'performance-toolkit'),
        __('Better reliability and availability', 'performance-toolkit'),
        __('Built-in security and DoS protection', 'performance-toolkit'),
    ]" />


</x-info-card>