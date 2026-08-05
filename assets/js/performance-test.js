(function () {
    'use strict';

    var DEFAULT_SCORE_WEIGHTS = {
        page_load_time: 25,
        lcp: 25,
        ttfb: 15,
        fcp: 10,
        resource_count: 10,
        js_size: 7,
        css_size: 4,
        image_size: 4
    };

    var DEFAULT_SCORE_MULTIPLIERS = {
        excellent: 1.0,
        good: 0.8,
        needs_improvement: 0.5,
        poor: 0.0
    };

    var SCORE_METRIC_MAP = {
        page_load_time: 'load_event_ms',
        lcp: 'lcp_ms',
        ttfb: 'ttfb_ms',
        fcp: 'fcp_ms',
        resource_count: 'total_resource_count',
        js_size: 'total_js_size_bytes',
        css_size: 'total_css_size_bytes',
        image_size: 'total_image_size_bytes'
    };

    var TEST_I18N = {};

    function tr(key, fallback) {
        var value = TEST_I18N && TEST_I18N[key];
        return (typeof value === 'string' && value !== '') ? value : fallback;
    }

    function bySelector(root, selector) {
        return root ? root.querySelector(selector) : null;
    }

    function byAllSelector(root, selector) {
        return root ? root.querySelectorAll(selector) : [];
    }

    function setStatus(el, text, isError) {
        if (!el) {
            return;
        }

        el.textContent = text;
        el.style.color = isError ? '#b91c1c' : '#1f2937';
    }

    function formatMs(value) {
        var n = Number(value);
        if (!Number.isFinite(n) || n <= 0) {
            return '-';
        }

        if (n >= 1000) {
            return (n / 1000).toFixed(2) + ' s';
        }

        return n.toFixed(2) + ' ms';
    }

    function setMetric(container, key, value) {
        var targets = byAllSelector(container, '[data-pivot-performance-toolkit-metric="' + key + '"]');

        targets.forEach(function (target) {
            target.textContent = value;
        });
    }

    function setCacheHitMetricTone(container, value) {
        var targets = byAllSelector(container, '[data-pivot-performance-toolkit-metric="page_cache_hit"]');
        var n = Number(value);

        targets.forEach(function (target) {
            target.classList.remove('text-emerald-600', 'text-red-600', 'text-gray-500');

            if (!Number.isFinite(n)) {
                target.classList.add('text-gray-500');
                return;
            }

            target.classList.add(n > 0 ? 'text-emerald-600' : 'text-red-600');
        });
    }

    function setCacheHitMessage(container, value) {
        var target = bySelector(container, '[data-pivot-performance-toolkit-cache-message]');
        var n = Number(value);

        if (!target) {
            return;
        }

        if (Number.isFinite(n) && n > 0) {
            target.textContent = tr('cacheServed', 'Your page is being served from cache.');
            return;
        }

        if (Number.isFinite(n)) {
            target.textContent = tr('cacheNotServed', 'Your page is not being served from cache.');
            return;
        }

        target.textContent = '';
    }

    function setCacheStatusCardTone(container, value) {
        var target = bySelector(container, '#pivot-performance-toolkit-page-cache-status');
        var n = Number(value);

        if (!target) {
            return;
        }

        target.classList.remove('bg-emerald-50', 'border-emerald-200', 'bg-red-50', 'border-red-200', 'bg-white', 'border-gray-200');

        if (Number.isFinite(n) && n > 0) {
            target.classList.add('bg-emerald-50', 'border-emerald-200');
            return;
        }

        if (Number.isFinite(n)) {
            target.classList.add('bg-red-50', 'border-red-200');
            return;
        }

        target.classList.add('bg-white', 'border-gray-200');
    }

    function scoreBand(score) {
        var n = Number(score);

        if (!Number.isFinite(n)) {
            n = 0;
        }

        if (n >= 90) {
            return {
                label: tr('bandExcellent', 'Excellent'),
                color: '#10b981',
                glow: 'rgba(16, 185, 129, .35)'
            };
        }

        if (n >= 75) {
            return {
                label: tr('bandGood', 'Good'),
                color: '#2563eb',
                glow: 'rgba(37, 99, 235, .35)'
            };
        }

        if (n >= 50) {
            return {
                label: tr('bandNeedsImprovement', 'Needs Improvement'),
                color: '#d97706',
                glow: 'rgba(217, 119, 6, .35)'
            };
        }

        return {
            label: tr('bandPoor', 'Poor'),
            color: '#dc2626',
            glow: 'rgba(220, 38, 38, .35)'
        };
    }

    function setScoreDonut(container, score) {
        var target = bySelector(container, '[data-pivot-performance-toolkit-score-donut]');
        var normalized = Math.max(0, Math.min(100, Math.round(Number(score) || 0)));
        var band = scoreBand(normalized);

        if (!target) {
            return;
        }

        target.setAttribute('score', String(normalized));
        target.style.setProperty('--score', String(normalized));
        target.style.setProperty('--donut-color', band.color);
        target.style.setProperty('--donut-glow', band.glow);
        target.setAttribute('role', 'img');
        target.setAttribute('aria-label', tr('scoreAriaPrefix', 'Score') + ' ' + normalized + ', ' + band.label);

        var valueEl = bySelector(target, '.label strong');
        var labelEl = bySelector(target, '.label span');

        if (valueEl) {
            valueEl.textContent = String(normalized);
        }

        if (labelEl) {
            labelEl.textContent = band.label;
        }
    }

    function normalizeStatus(value) {
        var raw = typeof value === 'string' ? value.trim() : '';

        if (!raw || raw === '-') {
            return {
                label: '-',
                tone: 'muted'
            };
        }

        var lower = raw.toLowerCase();

        if (lower === 'excellent') {
            return { label: tr('statusExcellent', 'Excellent'), tone: 'emerald' };
        }

        if (lower === 'hit') {
            return { label: tr('statusHit', 'HIT'), tone: 'emerald' };
        }

        if (lower === 'good') {
            return { label: tr('statusGood', 'Good'), tone: 'blue' };
        }

        if (lower === 'okay') {
            return { label: tr('statusOkay', 'Okay'), tone: 'blue' };
        }

        if (lower === 'needs improvement') {
            return { label: tr('statusNeedsImprovement', 'Needs improvement'), tone: 'amber' };
        }

        if (lower === 'slow') {
            return { label: tr('statusSlow', 'Slow'), tone: 'amber' };
        }

        if (lower === 'moderate') {
            return { label: tr('statusModerate', 'Moderate'), tone: 'blue' };
        }

        if (lower === 'poor') {
            return { label: tr('statusPoor', 'Poor'), tone: 'red' };
        }

        if (lower === 'miss') {
            return { label: tr('statusMiss', 'MISS'), tone: 'red' };
        }

        if (lower === 'heavy') {
            return { label: tr('statusHeavy', 'Heavy'), tone: 'red' };
        }

        if (lower === 'high') {
            return { label: tr('statusHigh', 'High'), tone: 'red' };
        }

        return {
            label: raw,
            tone: 'muted'
        };
    }

    function normalizeScoreBucket(value) {
        var normalized = normalizeStatus(value);
        var lower = normalized.label.toLowerCase();

        if ('-' === lower) {
            return 'poor';
        }

        if (lower === 'excellent') {
            return 'excellent';
        }

        if (lower === 'good' || lower === 'okay') {
            return 'good';
        }

        if (lower === 'needs improvement' || lower === 'moderate' || lower === 'slow') {
            return 'needs_improvement';
        }

        return 'poor';
    }

    function statusToneClasses(tone) {
        if (tone === 'emerald') {
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        }

        if (tone === 'blue') {
            return 'bg-blue-100 text-blue-800 border-blue-200';
        }

        if (tone === 'amber') {
            return 'bg-amber-100 text-amber-800 border-amber-200';
        }

        if (tone === 'red') {
            return 'bg-red-100 text-red-800 border-red-200';
        }

        return 'bg-gray-100 text-gray-500 border-gray-200';
    }

    function renderStatusPill(value) {
        var normalized = normalizeStatus(value);
        var pill = document.createElement('span');
        var lower = normalized.label.toLowerCase();

        pill.className = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold leading-none whitespace-nowrap ' + statusToneClasses(normalized.tone);

        if (lower === 'hit' || lower === 'miss') {
            var icon = document.createElement('span');
            var text = document.createElement('span');

            icon.className = 'mr-1';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = lower === 'hit' ? String.fromCharCode(10003) : String.fromCharCode(10005);
            text.textContent = normalized.label;

            pill.appendChild(icon);
            pill.appendChild(text);

            return pill;
        }

        pill.textContent = normalized.label;

        return pill;
    }

    function renderCacheStatusIcon(value) {
        var normalized = normalizeStatus(value);
        var lower = normalized.label.toLowerCase();
        var isHit = lower === 'hit';
        var wrapper = document.createElement('span');
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

        wrapper.className = 'inline-flex items-center';
        wrapper.style.color = isHit ? '#10b981' : '#dc2626';
        wrapper.setAttribute('title', isHit ? tr('cacheHit', 'HIT') : tr('cacheMiss', 'MISS'));
        wrapper.setAttribute('aria-label', isHit ? tr('cacheHit', 'HIT') : tr('cacheMiss', 'MISS'));

        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '1.75');
        svg.setAttribute('stroke-linecap', 'round');
        svg.setAttribute('stroke-linejoin', 'round');
        svg.setAttribute('aria-hidden', 'true');
        svg.style.width = '35px';
        svg.style.height = '35px';

        circle.setAttribute('cx', '12');
        circle.setAttribute('cy', '12');
        circle.setAttribute('r', '9');
        path.setAttribute('d', isHit ? 'm9 12 2 2 4-4' : 'm15 9-6 6m0-6 6 6');

        svg.appendChild(circle);
        svg.appendChild(path);
        wrapper.appendChild(svg);

        return wrapper;
    }

    function setMetricStatus(container, key, value) {
        var targets = byAllSelector(container, '[data-pivot-performance-toolkit-metric-status="' + key + '"]');

        targets.forEach(function (target) {
            if (key === 'page_cache_hit') {
                target.replaceChildren(renderCacheStatusIcon(value));
                return;
            }

            target.replaceChildren(renderStatusPill(value));
        });
    }

    function getScoreWeights(cfg) {
        return (cfg && cfg.scoreWeights && typeof cfg.scoreWeights === 'object') ? cfg.scoreWeights : DEFAULT_SCORE_WEIGHTS;
    }

    function getScoreMultipliers(cfg) {
        return (cfg && cfg.scoreMultipliers && typeof cfg.scoreMultipliers === 'object') ? cfg.scoreMultipliers : DEFAULT_SCORE_MULTIPLIERS;
    }

    function classifyOverallRating(score) {
        var n = Number(score);

        if (!Number.isFinite(n) || n < 0) {
            return '-';
        }

        if (n >= 90) {
            return 'excellent';
        }

        if (n >= 75) {
            return 'good';
        }

        if (n >= 50) {
            return 'needs improvement';
        }

        return 'poor';
    }

    function calculateOverallScore(metrics, cfg) {
        var weights = getScoreWeights(cfg);
        var multipliers = getScoreMultipliers(cfg);
        var weightedTotal = 0;
        var totalWeight = 0;
        var keys = Object.keys(SCORE_METRIC_MAP);

        for (var i = 0; i < keys.length; i += 1) {
            var section = keys[i];
            var metricKey = SCORE_METRIC_MAP[section];
            var weight = Number(weights[section]);
            var status = classifyMetric(metricKey, metrics[metricKey], cfg || null);
            var bucket = normalizeScoreBucket(status);
            var multiplier = multipliers[bucket];

            if (!Number.isFinite(weight) || weight <= 0) {
                continue;
            }

            if (!Number.isFinite(multiplier)) {
                multiplier = 0;
            }

            totalWeight += weight;
            weightedTotal += weight * multiplier;
        }

        if (totalWeight <= 0) {
            return 0;
        }

        return Math.round((weightedTotal / totalWeight) * 100);
    }

    function formatBytes(bytes) {
        var n = Number(bytes);
        if (!Number.isFinite(n) || n < 0) {
            return '-';
        }

        if (n >= 1048576) {
            return (n / 1048576).toFixed(2) + ' MB';
        }

        if (n >= 1024) {
            return (n / 1024).toFixed(2) + ' KB';
        }

        return n.toFixed(0) + ' B';
    }

    function formatCacheHit(value) {
        return Number(value) > 0 ? 'HIT' : 'MISS';
    }

    function classifyCount(value) {
        var n = Number(value);

        if (!Number.isFinite(n) || n <= 0) {
            return '-';
        }

        if (n <= 20) {
            return 'good';
        }

        if (n <= 40) {
            return 'needs improvement';
        }

        if (n <= 70) {
            return 'slow';
        }

        return 'poor';
    }

    function classifyResourceCount(value, bands) {
        var n = Number(value);

        if (!Number.isFinite(n) || n <= 0) {
            return '-';
        }

        if (!Array.isArray(bands) || !bands.length) {
            return classifyCount(value);
        }

        for (var i = 0; i < bands.length; i += 1) {
            var band = bands[i] || {};
            var max = band.max;

            if (max === null || typeof max === 'undefined' || n <= Number(max)) {
                return band.label || '-';
            }
        }

        return bands[bands.length - 1] && bands[bands.length - 1].label ? bands[bands.length - 1].label : '-';
    }

    function classifyMetric(key, value, cfg) {
        if (key === 'ttfb_ms') {
            var ttfbValue = Number(value);

            if (!Number.isFinite(ttfbValue) || ttfbValue <= 0) {
                return '-';
            }

            if (ttfbValue <= 200) {
                return 'excellent';
            }

            if (ttfbValue <= 600) {
                return 'okay';
            }

            if (ttfbValue <= 1000) {
                return 'slow';
            }

            return 'poor';
        }

        if (key === 'fcp_ms') {
            var fcpValue = Number(value);

            if (!Number.isFinite(fcpValue) || fcpValue <= 0) {
                return '-';
            }

            if (fcpValue < 1000) {
                return 'excellent';
            }

            if (fcpValue <= 1800) {
                return 'good';
            }

            if (fcpValue <= 3000) {
                return 'needs improvement';
            }

            return 'poor';
        }

        if (key === 'lcp_ms') {
            var lcpValue = Number(value);

            if (!Number.isFinite(lcpValue) || lcpValue <= 0) {
                return '-';
            }

            if (lcpValue < 1200) {
                return 'excellent';
            }

            if (lcpValue <= 2500) {
                return 'good';
            }

            if (lcpValue <= 4000) {
                return 'moderate';
            }

            return 'poor';
        }

        if (key === 'dom_content_loaded_ms') {
            var domContentLoadedValue = Number(value);

            if (!Number.isFinite(domContentLoadedValue) || domContentLoadedValue <= 0) {
                return '-';
            }

            if (domContentLoadedValue < 750) {
                return 'excellent';
            }

            if (domContentLoadedValue <= 1500) {
                return 'good';
            }

            if (domContentLoadedValue <= 3000) {
                return 'needs improvement';
            }

            return 'poor';
        }

        if (key === 'load_event_ms') {
            var loadEventValue = Number(value);

            if (!Number.isFinite(loadEventValue) || loadEventValue <= 0) {
                return '-';
            }

            if (loadEventValue < 1000) {
                return 'excellent';
            }

            if (loadEventValue <= 2000) {
                return 'good';
            }

            if (loadEventValue <= 4000) {
                return 'moderate';
            }

            return 'poor';
        }

        if (key === 'total_resource_count') {
            var profile = cfg && typeof cfg.websiteProfile === 'string' && cfg.websiteProfile ? cfg.websiteProfile : 'standard';
            var profileBands = cfg && cfg.profileBands && cfg.profileBands.resourceCount ? cfg.profileBands.resourceCount : {};
            var bands = profileBands[profile] || profileBands.standard || [];

            return classifyResourceCount(value, bands);
        }

        if (key === 'total_image_count') {
            var imageCountValue = Number(value);

            if (!Number.isFinite(imageCountValue) || imageCountValue <= 0) {
                return '-';
            }

            if (imageCountValue < 20) {
                return 'good';
            }

            if (imageCountValue < 50) {
                return 'moderate';
            }

            return 'high';
        }

        if (key === 'total_js_count' || key === 'total_css_count') {
            return classifyCount(value);
        }

        if (key === 'total_css_size_bytes') {
            var cssSizeProfile = cfg && typeof cfg.websiteProfile === 'string' && cfg.websiteProfile ? cfg.websiteProfile : 'standard';
            var cssSizeProfileBands = cfg && cfg.profileBands && cfg.profileBands.cssSize ? cfg.profileBands.cssSize : {};
            var cssSizeBands = cssSizeProfileBands[cssSizeProfile] || cssSizeProfileBands.standard || [];

            return classifyResourceCount(value, cssSizeBands);
        }

        if (key === 'total_js_size_bytes') {
            var jsSizeProfile = cfg && typeof cfg.websiteProfile === 'string' && cfg.websiteProfile ? cfg.websiteProfile : 'standard';
            var jsSizeProfileBands = cfg && cfg.profileBands && cfg.profileBands.jsSize ? cfg.profileBands.jsSize : {};
            var jsSizeBands = jsSizeProfileBands[jsSizeProfile] || jsSizeProfileBands.standard || [];

            return classifyResourceCount(value, jsSizeBands);
        }

        if (key === 'total_image_size_bytes') {
            var imageSizeProfile = cfg && typeof cfg.websiteProfile === 'string' && cfg.websiteProfile ? cfg.websiteProfile : 'standard';
            var imageSizeProfileBands = cfg && cfg.profileBands && cfg.profileBands.imageSize ? cfg.profileBands.imageSize : {};
            var imageSizeBands = imageSizeProfileBands[imageSizeProfile] || imageSizeProfileBands.standard || [];

            return classifyResourceCount(value, imageSizeBands);
        }

        if (key === 'page_cache_hit') {
            return Number(value) > 0 ? 'hit' : 'miss';
        }

        return '-';
    }

    function renderResult(card, result, cfg) {
        if (!card || !result || typeof result !== 'object') {
            return;
        }

        var metrics = (result.metrics && typeof result.metrics === 'object') ? result.metrics : {};
        var overallScore = calculateOverallScore(metrics, cfg || null);
        var overallRating = classifyOverallRating(overallScore);

        setMetric(card, 'overall_score', overallScore + '%');
        setScoreDonut(card, overallScore);
        setMetricStatus(card, 'overall_rating', overallRating);
        setMetric(card, 'ttfb_ms', formatMs(metrics.ttfb_ms));
        setMetric(card, 'fcp_ms', formatMs(metrics.fcp_ms));
        setMetric(card, 'lcp_ms', formatMs(metrics.lcp_ms));
        setMetric(card, 'dom_content_loaded_ms', formatMs(metrics.dom_content_loaded_ms));
        setMetric(card, 'load_event_ms', formatMs(metrics.load_event_ms));
        setMetric(card, 'total_resource_count', (Number(metrics.total_resource_count) || 0) + '');
        setMetric(card, 'total_js_size_bytes', formatBytes(metrics.total_js_size_bytes));
        setMetric(card, 'total_js_count', (Number(metrics.total_js_count) || 0) + '');
        setMetric(card, 'total_css_size_bytes', formatBytes(metrics.total_css_size_bytes));
        setMetric(card, 'total_css_count', (Number(metrics.total_css_count) || 0) + '');
        setMetric(card, 'total_image_size_bytes', formatBytes(metrics.total_image_size_bytes));
        setMetric(card, 'total_image_count', (Number(metrics.total_image_count) || 0) + '');
        setMetric(card, 'page_cache_hit', formatCacheHit(metrics.page_cache_hit));
        setCacheHitMetricTone(card, metrics.page_cache_hit);
        setCacheHitMessage(card, metrics.page_cache_hit);
        setCacheStatusCardTone(card, metrics.page_cache_hit);
        setMetric(card, 'page_url', result.page_url || result.target_url || '-');
        setMetric(card, 'collected_at', result.collected_at || '-');

        [
            'ttfb_ms',
            'fcp_ms',
            'lcp_ms',
            'dom_content_loaded_ms',
            'load_event_ms',
            'total_resource_count',
            'total_image_size_bytes',
            'total_image_count',
            'total_js_size_bytes',
            'total_js_count',
            'total_css_size_bytes',
            'total_css_count',
            'page_cache_hit'
        ].forEach(function (key) {
            setMetricStatus(card, key, classifyMetric(key, metrics[key], cfg || null));
        });
    }

    function withQueryParam(url, key, value) {
        var separator = url.indexOf('?') === -1 ? '?' : '&';
        return url + separator + encodeURIComponent(key) + '=' + encodeURIComponent(value);
    }

    function request(url, method, body, nonce) {
        var options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        };

        if (nonce) {
            options.headers['X-WP-Nonce'] = nonce;
        }

        if (body) {
            options.body = JSON.stringify(body);
        }

        return fetch(url, options).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok) {
                    var message = (payload && payload.message) ? payload.message : tr('requestFailed', 'Request failed.');
                    throw new Error(message);
                }

                return payload;
            });
        });
    }

    function clearProbeCookie() {
        document.cookie = 'pivot_performance_toolkit_perf_probe=; path=/; SameSite=Lax; max-age=0; expires=Thu, 01 Jan 1970 00:00:00 GMT';
    }

    function ensureProbeFrame() {
        var id = 'pivot-performance-toolkit-performance-probe-frame';
        var frame = document.getElementById(id);

        if (!frame) {
            frame = document.createElement('iframe');
            frame.id = id;
            frame.setAttribute('aria-hidden', 'true');
            frame.style.position = 'absolute';
            frame.style.left = '-99999px';
            frame.style.top = '0';
            frame.style.width = '1280px';
            frame.style.height = '800px';
            frame.style.opacity = '0';
            frame.style.pointerEvents = 'none';
            frame.style.border = '0';
            document.body.appendChild(frame);
        }

        return frame;
    }

    function openProbeWindow(url, token) {
        var features = 'width=1280,height=800,left=99999,top=0';

        try {
            return window.open(url, token || 'pivot-performance-toolkit-performance-probe', features);
        } catch (e) {
            return null;
        }
    }

    function buildProbeUrl(baseUrl) {
        try {
            var parsed = new URL(baseUrl, window.location.origin);
            parsed.searchParams.delete('pivot_performance_toolkit_perf_probe');
            parsed.searchParams.delete('pivot_performance_toolkit_perf_token');
            parsed.hash = '';
            return parsed.toString();
        } catch (e) {
            return String(baseUrl)
                .replace(/([?&])pivot_performance_toolkit_perf_probe=[^&]*&?/g, '$1')
                .replace(/([?&])pivot_performance_toolkit_perf_token=[^&]*&?/g, '$1')
                .replace(/#.*$/, '')
                .replace(/[?&]$/, '');
        }
    }

    function init() {
        var cfg = (typeof window.ptkPerfTest === 'object' && window.ptkPerfTest) ? window.ptkPerfTest : null;
        var card = document.querySelector('[data-pivot-performance-toolkit-performance-test]');

        TEST_I18N = (cfg && cfg.i18n && typeof cfg.i18n === 'object') ? cfg.i18n : {};

        if (!cfg || !card) {
            return;
        }

        var runButton = bySelector(card, '[data-pivot-performance-toolkit-run-test]');
        var statusEl = bySelector(card, '[data-pivot-performance-toolkit-status]');
        var urlInput = bySelector(card, '#pivot-performance-toolkit-test-url');

        if (!runButton || !statusEl || !urlInput) {
            return;
        }

        if (cfg.lastResult && typeof cfg.lastResult === 'object' && cfg.lastResult.status === 'complete') {
            renderResult(card, cfg.lastResult, cfg);
        }

        runButton.addEventListener('click', function () {
            var targetUrl = (urlInput.value || cfg.defaultUrl || '').trim();

            if (!targetUrl) {
                setStatus(statusEl, tr('failed', 'Could not run test.'), true);
                return;
            }

            runButton.disabled = true;
            setStatus(statusEl, tr('starting', 'Starting test...'), false);

            request(cfg.restRoot + 'start', 'POST', { targetUrl: targetUrl }, cfg.nonce)
                .then(function (startPayload) {
                    if (!startPayload || !startPayload.token || !startPayload.testUrl) {
                        throw new Error(tr('failed', 'Could not run test.'));
                    }

                    setStatus(statusEl, tr('running', 'Running test...'), false);

                    var token = startPayload.token;

                    // Set the probe cookie so the cache drop-in serves cached HTML to this request
                    // and injects the metrics-collection script into it.
                    document.cookie = 'pivot_performance_toolkit_perf_probe=' + encodeURIComponent(token) + '; path=/; SameSite=Lax; max-age=120';

                    var probeUrl = buildProbeUrl(startPayload.testUrl);
                    var probeWindow = openProbeWindow(probeUrl, token);
                    var frame = null;

                    if (probeWindow && !probeWindow.closed) {
                        probeWindow.focus();
                    } else {
                        frame = ensureProbeFrame();
                        frame.name = token;
                        frame.src = probeUrl;
                    }
                    var startedAt = Date.now();
                    var pollMs = Number(cfg.pollInterval || 1000);
                    var timeoutMs = Number(cfg.timeoutMs || 45000);

                    var timer = window.setInterval(function () {
                        request(withQueryParam(cfg.restRoot + 'result', 'token', token), 'GET', null, cfg.nonce)
                            .then(function (resultPayload) {
                                if (resultPayload && resultPayload.status === 'complete') {
                                    window.clearInterval(timer);
                                    renderResult(card, resultPayload, cfg);
                                    if (probeWindow && !probeWindow.closed) {
                                        probeWindow.close();
                                    }
                                    setStatus(statusEl, tr('done', 'Test complete.'), false);
                                    runButton.disabled = false;
                                    return;
                                }

                                if (Date.now() - startedAt > timeoutMs) {
                                    window.clearInterval(timer);
                                    clearProbeCookie();
                                    if (probeWindow && !probeWindow.closed) {
                                        probeWindow.close();
                                    }
                                    setStatus(statusEl, tr('timeout', 'Timed out waiting for test result.'), true);
                                    runButton.disabled = false;
                                }
                            })
                            .catch(function () {
                                if (Date.now() - startedAt > timeoutMs) {
                                    window.clearInterval(timer);
                                    clearProbeCookie();
                                    if (probeWindow && !probeWindow.closed) {
                                        probeWindow.close();
                                    }
                                    setStatus(statusEl, tr('timeout', 'Timed out waiting for test result.'), true);
                                    runButton.disabled = false;
                                }
                            });
                    }, pollMs);
                })
                .catch(function (error) {
                    setStatus(statusEl, error && error.message ? error.message : tr('failed', 'Could not run test.'), true);
                    runButton.disabled = false;
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());

