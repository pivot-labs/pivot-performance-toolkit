<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Browser Cache Test', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-browser-cache-test">
    <p>
        {{ __('Browser caching stores static assets locally to improve repeat visitor performance and reduce server load.', 'pivot-performance-toolkit') }}
    </p>
    <p>
        {{ __('Test your current browser cache headers and compression configuration.', 'pivot-performance-toolkit') }}
    </p>

    <div style="margin: 16px 0;">
        <button type="button" class="button button-primary" id="pivot-performance-toolkit-run-cache-test">
            {{ __('Run Cache Test', 'pivot-performance-toolkit') }}
        </button>
        <span id="pivot-performance-toolkit-test-status" style="display: none; margin-left: 12px;">
            <span class="spinner" style="float: none; margin: 0;"></span>
            {{ __('Testing...', 'pivot-performance-toolkit') }}
        </span>
    </div>

    <div id="pivot-performance-toolkit-test-results" style="display: none; margin-top: 16px;">
        <table class="pivot-performance-toolkit-table-list" style="width: 100%; margin-top: 12px;">
            <thead>
                <tr>
                    <th>{{ __('Asset', 'pivot-performance-toolkit') }}</th>
                    <th>{{ __('Cache-Control', 'pivot-performance-toolkit') }}</th>
                    <th>{{ __('Compression', 'pivot-performance-toolkit') }}</th>
                    <th>{{ __('Status', 'pivot-performance-toolkit') }}</th>
                </tr>
            </thead>
            <tbody id="pivot-performance-toolkit-test-results-body"></tbody>
        </table>

        <div id="pivot-performance-toolkit-test-summary" style="margin-top: 16px; padding: 12px; background-color: #f0f6fc; border-left: 4px solid #0969da; line-height: 1.6;">
            <p id="pivot-performance-toolkit-test-summary-text"></p>
        </div>
    </div>

    <div id="pivot-performance-toolkit-test-errors" style="display: none; margin-top: 16px; padding: 12px; background-color: #fff5f5; border-left: 4px solid #d63638;">
        <strong>{{ __('Test Error:', 'pivot-performance-toolkit') }}</strong>
        <p id="pivot-performance-toolkit-test-error-text" style="margin: 8px 0 0;"></p>
    </div>
</x-card>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const homeUrl = @json($home_url);
    const noAssetsLabel = @json(__('No testable assets found. Make sure your WordPress site is publicly accessible.', 'pivot-performance-toolkit'));
    const resultsLabel = @json(__('Results:', 'pivot-performance-toolkit'));
    const assetsHaveHeadersLabel = @json(__('assets have cache headers', 'pivot-performance-toolkit'));
    const assetsCompressedLabel = @json(__('assets are compressed', 'pivot-performance-toolkit'));
    const configGoodLabel = @json(__('Browser cache configuration looks good!', 'pivot-performance-toolkit'));
    const applyConfigLabel = @json(__('Not all assets have cache headers. Apply the configuration below.', 'pivot-performance-toolkit'));
    const cacheNotSetLabel = @json(__('Not set', 'pivot-performance-toolkit'));
    const cacheNoneLabel = @json(__('None', 'pivot-performance-toolkit'));
    const cacheUnknownLabel = @json(__('Unknown', 'pivot-performance-toolkit'));
    const cacheYesLabel = @json(__('Yes', 'pivot-performance-toolkit'));
    const cacheErrorPrefixLabel = @json(__('Error:', 'pivot-performance-toolkit'));
    const cacheNaLabel = @json(__('N/A', 'pivot-performance-toolkit'));
    const cacheGoodLabel = @json(__('GOOD', 'pivot-performance-toolkit'));
    const cacheCheckLabel = @json(__('CHECK', 'pivot-performance-toolkit'));
    const cacheCompressionOkLabel = @json(__('OK', 'pivot-performance-toolkit'));
    const cacheNotUsedLabel = @json(__('NOT USED', 'pivot-performance-toolkit'));
    const cssLabel = @json(__('CSS', 'pivot-performance-toolkit'));
    const jsLabel = @json(__('JavaScript', 'pivot-performance-toolkit'));
    const imageLabel = @json(__('Image', 'pivot-performance-toolkit'));
    const errorLabel = @json(__('Error', 'pivot-performance-toolkit'));

    const testButton = document.getElementById('pivot-performance-toolkit-run-cache-test');
    if (testButton) {
        testButton.addEventListener('click', runCacheTest);
    }

    async function runCacheTest() {
        const status = document.getElementById('pivot-performance-toolkit-test-status');
        const results = document.getElementById('pivot-performance-toolkit-test-results');
        const errors = document.getElementById('pivot-performance-toolkit-test-errors');
        const resultsBody = document.getElementById('pivot-performance-toolkit-test-results-body');
        const summaryText = document.getElementById('pivot-performance-toolkit-test-summary-text');

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
            document.getElementById('pivot-performance-toolkit-test-error-text').textContent = error.message;
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
                    assets.push({ url: href, type: cssLabel, contentType: 'text/css' });
                }
            });

            doc.querySelectorAll('script[src]').forEach((script) => {
                const src = script.getAttribute('src');
                if (src && src.includes('.js') && !src.includes('//')) {
                    assets.push({ url: src, type: jsLabel, contentType: 'application/javascript' });
                }
            });

            const img = doc.querySelector('img');
            if (img) {
                const src = img.getAttribute('src');
                if (src && !src.includes('//')) {
                    assets.push({ url: src, type: imageLabel, contentType: 'image' });
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

            const cacheControl = response.headers.get('Cache-Control') || cacheNotSetLabel;
            const contentEncoding = response.headers.get('Content-Encoding') || cacheNoneLabel;
            const contentLength = response.headers.get('Content-Length') || cacheUnknownLabel;
            const etag = response.headers.get('ETag');

            return {
                url: asset.url.split('/').pop(),
                type: asset.type,
                cacheControl: cacheControl,
                encoding: contentEncoding,
                size: contentLength,
                etag: etag ? cacheYesLabel : cacheNoneLabel,
                status: response.status,
            };
        } catch (e) {
            clearTimeout(timeoutId);
            return {
                url: asset.url.split('/').pop(),
                type: asset.type,
                cacheControl: cacheErrorPrefixLabel + ' ' + e.message,
                encoding: cacheNaLabel,
                size: cacheNaLabel,
                etag: cacheNaLabel,
                status: errorLabel,
            };
        }
    }

    function addResultRow(tbody, result) {
        const row = tbody.insertRow();
        const cacheStatus = result.cacheControl !== cacheNotSetLabel && result.cacheControl !== errorLabel ? cacheGoodLabel : cacheCheckLabel;
        const compressionStatus = result.encoding !== cacheNoneLabel && result.encoding !== cacheNaLabel ? cacheCompressionOkLabel + ' ' + result.encoding : cacheNotUsedLabel;

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

        const good = results.filter((r) => r.cacheControl !== cacheNotSetLabel && r.cacheControl !== errorLabel && !r.cacheControl.includes(cacheErrorPrefixLabel)).length;
        const compressed = results.filter((r) => r.encoding !== cacheNoneLabel && r.encoding !== cacheNaLabel).length;

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



