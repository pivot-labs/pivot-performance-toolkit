/**
 * Pivot Performance Toolkit - front-end performance probe.
 *
 * Loaded on the front end while the dashboard's "Run test" is measuring a page. It
 * collects navigation/paint timings and resource counts and sends them back to the
 * plugin's REST endpoint, identified by a one-time token that the test run supplies
 * through a cookie or window.name.
 *
 * The REST URL arrives from PHP through wp_localize_script() as
 * window.pivotPerformanceToolkitPerfProbe.collectUrl, or in a data-collect-url attribute
 * (see below).
 */
(function () {
    // Normally supplied by wp_localize_script(). When the page comes straight from the page
    // cache (served by the advanced-cache.php drop-in, before WordPress loads) the drop-in
    // adds this script with the URL in a data-collect-url attribute instead.
    var script = document.currentScript;
    var collectUrl =
        window.pivotPerformanceToolkitPerfProbe &&
        window.pivotPerformanceToolkitPerfProbe.collectUrl
            ? window.pivotPerformanceToolkitPerfProbe.collectUrl
            : (script && script.getAttribute('data-collect-url')) || '';
    if (!collectUrl) {
        return;
    }
    function getToken() {
        var token = '';
        try {
            token = String(window.name || '').trim();
        } catch (e) {}
        if (token && token.length >= 20) {
            return token;
        }
        try {
            var m = document.cookie.match(/(?:^|;\s*)pivot_performance_toolkit_perf_probe=([^;]+)/);
            if (m) {
                token = String(m[1] || '').trim();
                if (token.length >= 20) {
                    return token;
                }
            }
        } catch (e) {}
        return '';
    }
    var fcp = 0;
    var lcp = 0;
    if ('PerformanceObserver' in window) {
        try {
            var paintObserver = new PerformanceObserver(function (list) {
                var entries = list.getEntries();
                entries.forEach(function (entry) {
                    if (entry.name === 'first-contentful-paint') {
                        fcp = fcp || entry.startTime || 0;
                    }
                });
            });
            paintObserver.observe({ type: 'paint', buffered: true });
            var po = new PerformanceObserver(function (list) {
                var entries = list.getEntries();
                if (entries.length) {
                    lcp = entries[entries.length - 1].startTime || lcp;
                }
            });
            po.observe({ type: 'largest-contentful-paint', buffered: true });
        } catch (e) {}
    }
    function num(value) {
        var n = Number(value);
        return Number.isFinite(n) ? Math.round(n * 100) / 100 : 0;
    }
    function getFcpMetric() {
        var paints = performance.getEntriesByType('paint') || [];
        var paintEntry = paints.find(function (p) {
            return p.name === 'first-contentful-paint';
        });
        var namedEntry = performance.getEntriesByName('first-contentful-paint', 'paint')[0] || null;
        var entry = paintEntry || namedEntry;
        return num(entry && entry.startTime ? entry.startTime : fcp);
    }
    function getJsMetrics() {
        var scripts = document.querySelectorAll('script[src]') || [];
        var count = scripts.length;
        var totalSize = 0;
        var resources = performance.getEntriesByType('resource') || [];
        resources.forEach(function (r) {
            if (r.name && (r.name.endsWith('.js') || r.initiatorType === 'script')) {
                var size = r.transferSize || r.encodedBodySize || 0;
                totalSize += size;
            }
        });
        return { total_js_count: count, total_js_size_bytes: totalSize };
    }
    function getCssMetrics() {
        var links = document.querySelectorAll('link[rel="stylesheet"]') || [];
        var count = links.length;
        var totalSize = 0;
        var resources = performance.getEntriesByType('resource') || [];
        resources.forEach(function (r) {
            if (r.name && (r.name.endsWith('.css') || r.initiatorType === 'link')) {
                var size = r.transferSize || r.encodedBodySize || 0;
                totalSize += size;
            }
        });
        return { total_css_count: count, total_css_size_bytes: totalSize };
    }
    function getImageMetrics() {
        var images = document.querySelectorAll('img') || [];
        var count = images.length;
        var totalSize = 0;
        var resources = performance.getEntriesByType('resource') || [];
        resources.forEach(function (r) {
            var name = String(r && r.name ? r.name : '').toLowerCase();
            var type = String(r && r.initiatorType ? r.initiatorType : '').toLowerCase();
            if (
                type === 'img' ||
                /\.(avif|bmp|gif|heic|heif|ico|jpe?g|png|svg|webp|tif|tiff)(\?|#|$)/i.test(name)
            ) {
                var size = r.transferSize || r.encodedBodySize || 0;
                totalSize += size;
            }
        });
        return { total_image_count: count, total_image_size_bytes: totalSize };
    }
    function collect() {
        var nav = performance.getEntriesByType('navigation')[0] || null;
        if (!fcp) {
            fcp = getFcpMetric();
        }
        if (!lcp) {
            var lcpEntries = performance.getEntriesByType('largest-contentful-paint') || [];
            if (lcpEntries.length) {
                lcp = lcpEntries[lcpEntries.length - 1].startTime || 0;
            }
        }
        var jsMetrics = getJsMetrics();
        var cssMetrics = getCssMetrics();
        var imageMetrics = getImageMetrics();
        var resources = performance.getEntriesByType('resource') || [];
        var metrics = {
            ttfb_ms: num(nav && nav.responseStart ? nav.responseStart : 0),
            fcp_ms: num(fcp),
            lcp_ms: num(lcp),
            dom_content_loaded_ms: num(
                nav && nav.domContentLoadedEventEnd ? nav.domContentLoadedEventEnd : 0,
            ),
            load_event_ms: num(nav && nav.loadEventEnd ? nav.loadEventEnd : 0),
            total_resource_count: resources.length,
            total_js_count: jsMetrics.total_js_count,
            total_js_size_bytes: jsMetrics.total_js_size_bytes,
            total_css_count: cssMetrics.total_css_count,
            total_css_size_bytes: cssMetrics.total_css_size_bytes,
            total_image_count: imageMetrics.total_image_count,
            total_image_size_bytes: imageMetrics.total_image_size_bytes,
        };
        return metrics;
    }
    function getCacheHit() {
        return fetch(window.location.href, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
        })
            .then(function (response) {
                var cacheStatus = String(response.headers.get('x-pivot-cache') || '').toUpperCase();
                return cacheStatus === 'HIT' ? 1 : 0;
            })
            .catch(function () {
                return 0;
            });
    }
    function send() {
        var token = getToken();
        if (!token) {
            return;
        }
        var sentKey = 'pivot_performance_toolkit_perf_sent_' + token;
        try {
            if (window.sessionStorage && window.sessionStorage.getItem(sentKey) === '1') {
                return;
            }
        } catch (e) {}
        return getCacheHit()
            .then(function (cacheHit) {
                document.cookie =
                    'pivot_performance_toolkit_perf_probe=;path=/;SameSite=Lax;max-age=0;expires=Thu, 01 Jan 1970 00:00:00 GMT';
                var metrics = collect();
                metrics.page_cache_hit = cacheHit;
                var payload = { token: token, pageUrl: window.location.href, metrics: metrics };
                return fetch(collectUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    keepalive: true,
                    credentials: 'omit',
                }).then(function () {
                    try {
                        if (window.sessionStorage) {
                            window.sessionStorage.setItem(sentKey, '1');
                        }
                    } catch (e) {}
                    try {
                        window.name = '';
                    } catch (e) {}
                });
            })
            .catch(function () {});
    }
    window.addEventListener('load', function () {
        window.setTimeout(send, 300);
    });
})();
