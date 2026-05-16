
<div role="tabpanel" aria-labelledby="ptk-tab-nginx" id="ptk-panel-nginx">
    <h3>{{ __('Nginx Configuration', 'performance-toolkit') }}</h3>
    <p>{{ __('Ask your hosting provider to add this to your Nginx server block configuration. Nginx is common on managed hosting and higher-tier plans.', 'performance-toolkit') }}</p>



    <div style="margin: 16px 0; padding: 12px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <strong>{{ __('Copy-friendly snippet:', 'performance-toolkit') }}</strong>
            <button type="button" class="button button-secondary ptk-copy-snippet" id="ptk-copy-nginx" data-text="{{ esc_attr($nginx_snippet) }}">
                {{ __('Copy', 'performance-toolkit') }}
            </button>
        </div>
        <div class="ptk-snippet-wrapper" id="ptk-snippet-nginx-wrapper">
            <pre class="ptk-snippet-pre" style="margin: 0; padding: 12px; background: white; border: 1px solid #ccc; border-radius: 3px; overflow-x: auto; font-size: 12px; line-height: 1.4;"><code>{{ $nginx_snippet }}</code></pre>
        </div>
        <div style="text-align: center; margin-top: 8px;">
            <button type="button" class="button button-secondary ptk-snippet-toggle" data-target="ptk-snippet-nginx-wrapper">
                {{ __('Expand Full Configuration', 'performance-toolkit') }}
            </button>
        </div>
    </div>

    <div style="margin: 16px 0; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
        <p style="margin: 0 0 8px;"><strong>{{ __('How to apply:', 'performance-toolkit') }}</strong></p>
        <ol style="margin: 8px 0 0 20px; list-style-type: decimal;">
            <li>{{ __('Contact your hosting provider or VPS admin', 'performance-toolkit') }}</li>
            <li>{{ __('Share the above snippet and ask them to add it to your Nginx server block', 'performance-toolkit') }}</li>
            <li>{{ __('They will reload/restart Nginx to apply changes', 'performance-toolkit') }}</li>
            <li>{{ __('Clear your site cache to see effects', 'performance-toolkit') }}</li>
        </ol>
    </div>
</div>