<div role="tabpanel" aria-labelledby="ptk-tab-htaccess" id="ptk-panel-htaccess">
    <h3>{{ __('Apache .htaccess Configuration', 'performance-toolkit') }}</h3>
    <p>{{ __('Add this to your .htaccess file in the WordPress root directory. Most shared hosting uses Apache.', 'performance-toolkit') }}</p>



    <div style="margin: 16px 0; padding: 12px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <strong>{{ __('Copy-friendly snippet:', 'performance-toolkit') }}</strong>
            <button type="button" class="button button-secondary ptk-copy-snippet" id="ptk-copy-htaccess" data-text="{{ esc_attr($htaccess_snippet) }}">
                {{ __('Copy', 'performance-toolkit') }}
            </button>
        </div>
        <div class="ptk-snippet-wrapper" id="ptk-snippet-htaccess-wrapper">
            <pre class="ptk-snippet-pre" style="margin: 0; padding: 12px; background: white; border: 1px solid #ccc; border-radius: 3px; overflow-x: auto; font-size: 12px; line-height: 1.4;"><code>{{ $htaccess_snippet }}</code></pre>
        </div>
        <div style="text-align: center; margin-top: 8px;">
            <button type="button" class="button button-secondary ptk-snippet-toggle" data-target="ptk-snippet-htaccess-wrapper">
                {{ __('Expand Full Configuration', 'performance-toolkit') }}
            </button>
        </div>
    </div>

    <div style="margin: 16px 0; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
        <p style="margin: 0 0 8px;"><strong>{{ __('How to apply:', 'performance-toolkit') }}</strong></p>
        <ol style="margin: 8px 0 0 20px; list-style-type: decimal;">
            <li>{{ __('Connect via FTP/SFTP to your server', 'performance-toolkit') }}</li>
            <li>{{ __('Navigate to your WordPress root (where wp-content, wp-admin, wp-includes are)', 'performance-toolkit') }}</li>
            <li>{{ __('Open .htaccess (may be hidden file)', 'performance-toolkit') }}</li>
            <li>{{ __('Add the above snippet (or replace entire file if new)', 'performance-toolkit') }}</li>
            <li>{{ __('Save and clear your site cache', 'performance-toolkit') }}</li>
        </ol>
    </div>
</div>