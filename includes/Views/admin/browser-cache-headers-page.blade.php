<section id="ptk-browser-cache-test" class="ptk-card">
    <h2>{{ __('Browser Cache Test', 'performance-toolkit') }}</h2>
    <p>
        {{ __('Test your site\'s browser cache headers and compression configuration. This checks your actual WordPress installation, not just the directives.', 'performance-toolkit') }}
    </p>

    <div style="margin: 16px 0;">
        <button type="button" class="button button-primary" id="ptk-run-cache-test">
            {{ __('Run Cache Test', 'performance-toolkit') }}
        </button>
        <span id="ptk-test-status" style="display: none; margin-left: 12px;">
            <span class="spinner" style="float: none; margin: 0;"></span>
            {{ __('Testing...', 'performance-toolkit') }}
        </span>
    </div>

    <div id="ptk-test-results" style="display: none; margin-top: 16px;">
        <table class="ptk-table-list" style="width: 100%; margin-top: 12px;">
            <thead>
                <tr>
                    <th>{{ __('Asset', 'performance-toolkit') }}</th>
                    <th>{{ __('Cache-Control', 'performance-toolkit') }}</th>
                    <th>{{ __('Compression', 'performance-toolkit') }}</th>
                    <th>{{ __('Status', 'performance-toolkit') }}</th>
                </tr>
            </thead>
            <tbody id="ptk-test-results-body"></tbody>
        </table>

        <div id="ptk-test-summary" style="margin-top: 16px; padding: 12px; background-color: #f0f6fc; border-left: 4px solid #0969da; line-height: 1.6;">
            <p id="ptk-test-summary-text"></p>
        </div>
    </div>

    <div id="ptk-test-errors" style="display: none; margin-top: 16px; padding: 12px; background-color: #fff5f5; border-left: 4px solid #d63638;">
        <strong>{{ __('Test Error:', 'performance-toolkit') }}</strong>
        <p id="ptk-test-error-text" style="margin: 8px 0 0;"></p>
    </div>
</section>

<section id="ptk-browser-cache" class="ptk-card">
    <h2>{{ __('Browser Cache & Compression Settings', 'performance-toolkit') }}</h2>
    <p>
        {{ __('Browser caching tells visitors\' browsers to store static assets locally. Gzip compression reduces file sizes during transfer. Both dramatically improve repeat-visitor performance.', 'performance-toolkit') }}
    </p>

    <div style="margin-top: 20px; padding: 12px; background-color: #e7f3ff; border-left: 4px solid #0969da;">
        <p style="margin: 0;">
            <strong>{{ __('Server Detected:', 'performance-toolkit') }}</strong>
            <code>{{ $server_software !== '' ? $server_software : 'Unknown' }}</code>
        </p>
    </div>

    <div style="margin-top: 24px;">
        <div role="tablist" aria-label="{{ __('Server configuration tabs', 'performance-toolkit') }}">
            <div role="tabpanel" aria-labelledby="ptk-tab-htaccess" id="ptk-panel-htaccess">
                <h3>{{ __('Apache .htaccess Configuration', 'performance-toolkit') }}</h3>
                <p>{{ __('Add this to your .htaccess file in the WordPress root directory. Most shared hosting uses Apache.', 'performance-toolkit') }}</p>

                <div style="margin: 16px 0;">
                    <h4 style="margin: 0 0 8px;">{{ __('What this does:', 'performance-toolkit') }}</h4>
                    <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
                        <li>{{ __('Sets browser cache expiration times (1 year for versioned assets, 1 month for images)', 'performance-toolkit') }}</li>
                        <li>{{ __('Enables Gzip compression for HTML, CSS, and JavaScript', 'performance-toolkit') }}</li>
                        <li>{{ __('Adds cache-control headers to improve hit rates', 'performance-toolkit') }}</li>
                        <li>{{ __('Prevents MIME-type sniffing for security', 'performance-toolkit') }}</li>
                    </ul>
                </div>

                <div style="margin: 16px 0; padding: 12px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong>{{ __('Copy-friendly snippet:', 'performance-toolkit') }}</strong>
                        <button type="button" class="button button-secondary" id="ptk-copy-htaccess" data-text="{{ esc_attr($htaccess_snippet) }}">
                            {{ __('Copy', 'performance-toolkit') }}
                        </button>
                    </div>
                    <pre style="margin: 0; padding: 12px; background: white; border: 1px solid #ccc; border-radius: 3px; overflow-x: auto; font-size: 12px; line-height: 1.4;"><code>{{ $htaccess_snippet }}</code></pre>
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

            <div role="tabpanel" aria-labelledby="ptk-tab-nginx" id="ptk-panel-nginx" style="margin-top: 32px;">
                <h3>{{ __('Nginx Configuration', 'performance-toolkit') }}</h3>
                <p>{{ __('Ask your hosting provider to add this to your Nginx server block configuration. Nginx is common on managed hosting and higher-tier plans.', 'performance-toolkit') }}</p>

                <div style="margin: 16px 0;">
                    <h4 style="margin: 0 0 8px;">{{ __('What this does:', 'performance-toolkit') }}</h4>
                    <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
                        <li>{{ __('Sets browser cache expiration times (1 year for versioned assets, 1 month for images)', 'performance-toolkit') }}</li>
                        <li>{{ __('Enables Gzip compression for text-based files', 'performance-toolkit') }}</li>
                        <li>{{ __('Adds cache-control and immutable headers', 'performance-toolkit') }}</li>
                        <li>{{ __('Adds security headers to prevent MIME-sniffing', 'performance-toolkit') }}</li>
                    </ul>
                </div>

                <div style="margin: 16px 0; padding: 12px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong>{{ __('Copy-friendly snippet:', 'performance-toolkit') }}</strong>
                        <button type="button" class="button button-secondary" id="ptk-copy-nginx" data-text="{{ esc_attr($nginx_snippet) }}">
                            {{ __('Copy', 'performance-toolkit') }}
                        </button>
                    </div>
                    <pre style="margin: 0; padding: 12px; background: white; border: 1px solid #ccc; border-radius: 3px; overflow-x: auto; font-size: 12px; line-height: 1.4;"><code>{{ $nginx_snippet }}</code></pre>
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
        </div>
    </div>

    <div style="margin-top: 32px; padding: 12px; background-color: #f0f6fc; border-left: 4px solid #0969da;">
        <h3 style="margin-top: 0;">{{ __('Testing Your Configuration', 'performance-toolkit') }}</h3>
        <p>{{ __('After applying these directives, test your headers using:', 'performance-toolkit') }}</p>
        <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
            <li>
                <a href="https://www.webpagetest.org/" target="_blank" rel="noopener noreferrer">WebPageTest.org</a>
                {{ __('(detailed waterfall + caching info)', 'performance-toolkit') }}
            </li>
            <li>
                <a href="https://gtmetrix.com/" target="_blank" rel="noopener noreferrer">GTmetrix</a>
                {{ __('(cache headers report)', 'performance-toolkit') }}
            </li>
            <li>{{ __('Browser DevTools -> Network tab -> Response Headers', 'performance-toolkit') }}</li>
        </ul>
    </div>

    <div style="margin-top: 24px; padding: 12px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
        <h3 style="margin-top: 0;">{{ __('Cache-Busting Strategy', 'performance-toolkit') }}</h3>
        <p>{{ __('With 1-year browser cache on versioned assets (like style.css?v=123), you need a way to invalidate old versions when you update. WordPress theme/plugin versioning handles this automatically through query strings.', 'performance-toolkit') }}</p>
        <p style="margin: 0;">{{ __('Always clear your server cache and CDN cache when updating themes, plugins, or custom code.', 'performance-toolkit') }}</p>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyButtons = document.querySelectorAll('[id^="ptk-copy-"]');
    const copiedLabel = @json(__('Copied!', 'performance-toolkit'));
    const copyFailedLabel = @json(__('Failed to copy. Please try again.', 'performance-toolkit'));
    const homeUrl = @json($home_url);
    const noAssetsLabel = @json(__('No testable assets found. Make sure your WordPress site is publicly accessible.', 'performance-toolkit'));
    const resultsLabel = @json(__('Results:', 'performance-toolkit'));
    const assetsHaveHeadersLabel = @json(__('assets have cache headers', 'performance-toolkit'));
    const assetsCompressedLabel = @json(__('assets are compressed', 'performance-toolkit'));
    const configGoodLabel = @json(__('Browser cache configuration looks good!', 'performance-toolkit'));
    const applyConfigLabel = @json(__('Not all assets have cache headers. Apply the configuration below.', 'performance-toolkit'));

    copyButtons.forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const text = this.getAttribute('data-text');
            navigator.clipboard.writeText(text).then(() => {
                const original = this.textContent;
                this.textContent = copiedLabel;
                setTimeout(() => {
                    this.textContent = original;
                }, 2000);
            }).catch(() => {
                alert(copyFailedLabel);
            });
        });
    });

    const testButton = document.getElementById('ptk-run-cache-test');
    if (testButton) {
        testButton.addEventListener('click', runCacheTest);
    }

    async function runCacheTest() {
        const status = document.getElementById('ptk-test-status');
        const results = document.getElementById('ptk-test-results');
        const errors = document.getElementById('ptk-test-errors');
        const resultsBody = document.getElementById('ptk-test-results-body');
        const summaryText = document.getElementById('ptk-test-summary-text');

        status.style.display = 'inline';
        results.style.display = 'none';
        errors.style.display = 'none';
        resultsBody.innerHTML = '';

        try {
            const assets = await getTestAssets();
            const testResults = [];

            for (const asset of assets) {
                try {
                    const result = await testAsset(asset);
                    testResults.push(result);
                    addResultRow(resultsBody, result);
                } catch (e) {
                    console.error('Error testing asset:', asset, e);
                }
            }

            status.style.display = 'none';
            results.style.display = 'block';
            generateSummary(testResults, summaryText);
        } catch (error) {
            status.style.display = 'none';
            errors.style.display = 'block';
            document.getElementById('ptk-test-error-text').textContent = error.message;
        }
    }

    async function getTestAssets() {
        try {
            const response = await fetch(homeUrl);
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const assets = [];

            doc.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
                const href = link.getAttribute('href');
                if (href && !href.includes('//fonts.')) {
                    assets.push({ url: href, type: 'CSS', contentType: 'text/css' });
                }
            });

            doc.querySelectorAll('script[src]').forEach((script) => {
                const src = script.getAttribute('src');
                if (src && src.includes('.js') && !src.includes('//')) {
                    assets.push({ url: src, type: 'JavaScript', contentType: 'application/javascript' });
                }
            });

            const img = doc.querySelector('img');
            if (img) {
                const src = img.getAttribute('src');
                if (src && !src.includes('//')) {
                    assets.push({ url: src, type: 'Image', contentType: 'image' });
                }
            }

            return assets.slice(0, 5);
        } catch (e) {
            console.error('Error fetching home page:', e);
            return [];
        }
    }

    async function testAsset(asset) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);

        try {
            const response = await fetch(asset.url, { signal: controller.signal });
            clearTimeout(timeoutId);

            const cacheControl = response.headers.get('Cache-Control') || 'Not set';
            const contentEncoding = response.headers.get('Content-Encoding') || 'None';
            const contentLength = response.headers.get('Content-Length') || 'Unknown';
            const etag = response.headers.get('ETag');

            return {
                url: asset.url.split('/').pop(),
                type: asset.type,
                cacheControl: cacheControl,
                encoding: contentEncoding,
                size: contentLength,
                etag: etag ? 'Yes' : 'No',
                status: response.status,
            };
        } catch (e) {
            clearTimeout(timeoutId);
            return {
                url: asset.url.split('/').pop(),
                type: asset.type,
                cacheControl: 'Error: ' + e.message,
                encoding: 'N/A',
                size: 'N/A',
                etag: 'N/A',
                status: 'Error',
            };
        }
    }

    function addResultRow(tbody, result) {
        const row = tbody.insertRow();
        const cacheStatus = result.cacheControl !== 'Not set' && result.cacheControl !== 'Error' ? 'GOOD' : 'CHECK';
        const compressionStatus = result.encoding !== 'None' && result.encoding !== 'N/A' ? 'OK ' + result.encoding : 'NOT USED';

        row.innerHTML = `
            <td><strong>${escapeHtml(result.type)}</strong><br><small>${escapeHtml(result.url)}</small></td>
            <td><small>${escapeHtml(result.cacheControl)}</small></td>
            <td><small>${escapeHtml(compressionStatus)}</small></td>
            <td>${cacheStatus}</td>
        `;
    }

    function generateSummary(results, summaryElement) {
        if (results.length === 0) {
            summaryElement.textContent = noAssetsLabel;
            return;
        }

        const good = results.filter((r) => r.cacheControl !== 'Not set' && r.cacheControl !== 'Error' && !r.cacheControl.includes('Error')).length;
        const compressed = results.filter((r) => r.encoding !== 'None' && r.encoding !== 'N/A').length;

        let summary = `<strong>${resultsLabel} ${good}/${results.length} ${assetsHaveHeadersLabel}</strong><br>`;
        summary += `${compressed}/${results.length} ${assetsCompressedLabel}`;

        if (good === results.length && compressed >= Math.floor(results.length / 2)) {
            summary += `<br><strong style="color: #28a745;">${configGoodLabel}</strong>`;
        } else if (good < results.length / 2) {
            summary += `<br><strong style="color: #ffc107;">${applyConfigLabel}</strong>`;
        }

        summaryElement.innerHTML = summary;
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };

        return String(text).replace(/[&<>"']/g, (m) => map[m]);
    }
});
</script>

