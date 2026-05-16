<x-card :title="__('Browser Cache Test', 'performance-toolkit')" id="ptk-browser-cache-test">
    <p>
        Browser caching stores static assets locally to improve repeat visitor performance and reduce server load
    </p>
    <p>
        Test your current browser cache headers and compression configuration.
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
</x-card>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const homeUrl = @json($home_url);
    const noAssetsLabel = @json(__('No testable assets found. Make sure your WordPress site is publicly accessible.', 'performance-toolkit'));
    const resultsLabel = @json(__('Results:', 'performance-toolkit'));
    const assetsHaveHeadersLabel = @json(__('assets have cache headers', 'performance-toolkit'));
    const assetsCompressedLabel = @json(__('assets are compressed', 'performance-toolkit'));
    const configGoodLabel = @json(__('Browser cache configuration looks good!', 'performance-toolkit'));
    const applyConfigLabel = @json(__('Not all assets have cache headers. Apply the configuration below.', 'performance-toolkit'));

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

