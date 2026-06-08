<div class="ptk-col ptk-col--main col-span-full">
    @if ($settings_updated && !isset($_GET['ptk_notice']))
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Settings saved successfully.', 'performance-toolkit') }}</p>
        </div>
    @endif

    <section class="ptk-card" data-ptk-assets-detector data-ajax-action="{{ esc_attr((string) $ajax_detect_action) }}" data-ajax-nonce="{{ esc_attr((string) $ajax_detect_nonce) }}">
        <h3 class="m-0">{{ __('Assets Detector', 'performance-toolkit') }}</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1.8fr)_minmax(280px,1fr)] md:items-start">
            <div id="ptk-assets-detector-controls">
                <p class="mt-0">{{ $assets_detector_message }}</p>

                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:12px 0;">
                    <label for="ptk-assets-target-url" class="screen-reader-text">{{ __('Select content to scan', 'performance-toolkit') }}</label>
                    <select id="ptk-assets-target-url" data-ptk-assets-select style="min-width:320px;flex:1;">
                        <option value="{{ esc_url(home_url('/')) }}">{{ __('Homepage', 'performance-toolkit') }}</option>
                        @if (!empty($content_options['pages']))
                            <optgroup label="{{ esc_attr__('Pages', 'performance-toolkit') }}">
                                @foreach ($content_options['pages'] as $item)
                                    <option value="{{ esc_url($item['url']) }}">{{ esc_html($item['label']) }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if (!empty($content_options['posts']))
                            <optgroup label="{{ esc_attr__('Posts', 'performance-toolkit') }}">
                                @foreach ($content_options['posts'] as $item)
                                    <option value="{{ esc_url($item['url']) }}">{{ esc_html($item['label']) }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <button type="button" class="button button-primary" data-ptk-assets-run>{{ __('Detect Assets', 'performance-toolkit') }}</button>
                </div>

                <p data-ptk-assets-status style="margin:8px 0 10px;">{{ __('Idle', 'performance-toolkit') }}</p>
                <p data-ptk-assets-summary style="margin:0 0 12px; color:#4b5563;"></p>
            </div>

            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h2 class="m-0 text-sm font-semibold text-gray-900">{{ __('How it works', 'performance-toolkit') }}</h2>
                <p class="mt-2 mb-0 text-sm text-gray-600">{{ __('The detector requests the selected URL, reads the returned HTML, and lists script, stylesheet, and image URLs found on that page.', 'performance-toolkit') }}</p>
            </div>
        </div>


        <div data-ptk-assets-filters style="display:none;margin-bottom:12px;gap:6px;flex-wrap:wrap;align-items:center;">
            <span class="text-xs font-semibold text-gray-500" style="margin-right:4px;">{{ __('Filter:', 'performance-toolkit') }}</span>
            <button type="button" class="button" data-ptk-filter="all" aria-pressed="true">{{ __('All', 'performance-toolkit') }}</button>
            <button type="button" class="button" data-ptk-filter="Theme">{{ __('Theme', 'performance-toolkit') }}</button>
            <button type="button" class="button" data-ptk-filter="Plugin">{{ __('Plugin', 'performance-toolkit') }}</button>
            <button type="button" class="button" data-ptk-filter="External">{{ __('External', 'performance-toolkit') }}</button>
            <button type="button" class="button" data-ptk-filter="Core">{{ __('Core (WP)', 'performance-toolkit') }}</button>
            <button type="button" class="button" data-ptk-filter="Other">{{ __('Other', 'performance-toolkit') }}</button>
        </div>

        <table class="widefat striped" data-ptk-assets-table style="display:none;">
            <colgroup>
                <col style="width: 80px;">
                <col style="width: auto;">
                <col style="width: 80px;">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">{{ __('Type', 'performance-toolkit') }}</th>
                    <th scope="col">{{ __('Asset URL', 'performance-toolkit') }}</th>
                    <th scope="col">{{ __('Category', 'performance-toolkit') }}</th>
                </tr>
            </thead>
            <tbody data-ptk-assets-rows></tbody>
        </table>
    </section>
</div>

<script>
(function () {
    var root = document.querySelector('[data-ptk-assets-detector]');
    if (!root) {
        return;
    }

    var select    = root.querySelector('[data-ptk-assets-select]');
    var runBtn    = root.querySelector('[data-ptk-assets-run]');
    var status    = root.querySelector('[data-ptk-assets-status]');
    var summary   = root.querySelector('[data-ptk-assets-summary]');
    var table     = root.querySelector('[data-ptk-assets-table]');
    var rowsWrap  = root.querySelector('[data-ptk-assets-rows]');
    var filtersBar = root.querySelector('[data-ptk-assets-filters]');

    if (!select || !runBtn || !status || !summary || !table || !rowsWrap) {
        return;
    }

    var activeFilter = 'all';
    var typeCounts = { css: 0, javascript: 0, fonts: 0, images: 0, other: 0 };
    var summaryLabels = {
        css: '<?php echo esc_js( __( 'CSS', 'performance-toolkit' ) ); ?>',
        javascript: '<?php echo esc_js( __( 'JavaScript', 'performance-toolkit' ) ); ?>',
        fonts: '<?php echo esc_js( __( 'Fonts', 'performance-toolkit' ) ); ?>',
        images: '<?php echo esc_js( __( 'Images', 'performance-toolkit' ) ); ?>',
        other: '<?php echo esc_js( __( 'Other', 'performance-toolkit' ) ); ?>'
    };
    var detectedLabel = '<?php echo esc_js( __( 'Detected:', 'performance-toolkit' ) ); ?>';

    function updateSummaryDisplay() {
        var parts = [];
        if (typeCounts.css > 0) parts.push(typeCounts.css + ' ' + summaryLabels.css);
        if (typeCounts.javascript > 0) parts.push(typeCounts.javascript + ' ' + summaryLabels.javascript);
        if (typeCounts.fonts > 0) parts.push(typeCounts.fonts + ' ' + summaryLabels.fonts);
        if (typeCounts.images > 0) parts.push(typeCounts.images + ' ' + summaryLabels.images);
        if (typeCounts.other > 0) parts.push(typeCounts.other + ' ' + summaryLabels.other);
        summary.textContent = parts.length > 0 ? detectedLabel + ' ' + parts.join(', ') : '';
    }

    function setStatus(message, isError) {
        status.textContent = message;
        status.style.color = isError ? '#b91c1c' : '#374151';
    }

    function clearRows() {
        rowsWrap.innerHTML = '';
        table.style.display = 'none';
        if (filtersBar) { filtersBar.style.display = 'none'; }
        summary.textContent = '';
        activeFilter = 'all';
        typeCounts = { css: 0, javascript: 0, fonts: 0, images: 0, other: 0 };
        if (filtersBar) {
            filtersBar.querySelectorAll('[data-ptk-filter]').forEach(function (btn) {
                var isAll = btn.getAttribute('data-ptk-filter') === 'all';
                btn.setAttribute('aria-pressed', isAll ? 'true' : 'false');
                btn.classList.toggle('button-primary', isAll);
            });
        }
    }

    function applyFilter(filter) {
        activeFilter = filter;
        rowsWrap.querySelectorAll('tr[data-ptk-category]').forEach(function (tr) {
            var cat = tr.getAttribute('data-ptk-category') || '';
            tr.style.display = (filter === 'all' || cat === filter) ? '' : 'none';
        });
        if (filtersBar) {
            filtersBar.querySelectorAll('[data-ptk-filter]').forEach(function (btn) {
                var isActive = btn.getAttribute('data-ptk-filter') === filter;
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                btn.classList.toggle('button-primary', isActive);
            });
        }
    }

    function addRow(type, url, category) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-ptk-category', category);

        if (activeFilter !== 'all' && category !== activeFilter) {
            tr.style.display = 'none';
        }

        // Count the type
        var typeKey = type.toLowerCase();
        if (typeKey === 'css' || typeKey === 'stylesheet') {
            typeCounts.css++;
        } else if (typeKey === 'javascript' || typeKey === 'script' || typeKey === 'js') {
            typeCounts.javascript++;
        } else if (typeKey === 'font' || typeKey === 'fonts') {
            typeCounts.fonts++;
        } else if (typeKey === 'image' || typeKey === 'img' || typeKey === 'svg') {
            typeCounts.images++;
        } else {
            typeCounts.other++;
        }

        var typeCell = document.createElement('td');
        typeCell.textContent = type;
        tr.appendChild(typeCell);

        var urlCell = document.createElement('td');
        urlCell.style.wordBreak = 'break-all';
        urlCell.style.overflowWrap = 'break-word';
        urlCell.style.maxWidth = '0';
        var link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = url;
        urlCell.appendChild(link);
        tr.appendChild(urlCell);

        var categoryCell = document.createElement('td');
        categoryCell.textContent = category;
        tr.appendChild(categoryCell);

        rowsWrap.appendChild(tr);
    }

    if (filtersBar) {
        filtersBar.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-ptk-filter]');
            if (!btn) { return; }
            applyFilter(btn.getAttribute('data-ptk-filter') || 'all');
        });
    }

    runBtn.addEventListener('click', function () {
        var action    = root.getAttribute('data-ajax-action') || '';
        var nonce     = root.getAttribute('data-ajax-nonce') || '';
        var targetUrl = select.value || '';
        var ajaxUrl   = (typeof window.ajaxurl === 'string' && window.ajaxurl !== '')
            ? window.ajaxurl
            : '/wp-admin/admin-ajax.php';

        if (!targetUrl) {
            setStatus('<?php echo esc_js( __( 'Please select a URL first.', 'performance-toolkit' ) ); ?>', true);
            return;
        }

        runBtn.disabled = true;
        clearRows();
        setStatus('<?php echo esc_js( __( 'Detecting assets...', 'performance-toolkit' ) ); ?>', false);

        var body = new URLSearchParams();
        body.set('action', action);
        body.set('_ajax_nonce', nonce);
        body.set('target_url', targetUrl);

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (payload) {
                if (!payload || !payload.success) {
                    var msg = payload && payload.data && payload.data.message
                        ? String(payload.data.message)
                        : '<?php echo esc_js( __( 'Detection failed.', 'performance-toolkit' ) ); ?>';
                    throw new Error(msg);
                }

                var rows = payload.data && Array.isArray(payload.data.rows) ? payload.data.rows : [];
                var summaryText = payload.data && payload.data.summary_text ? String(payload.data.summary_text) : '';

                if (rows.length === 0) {
                    setStatus('<?php echo esc_js( __( 'Detection complete. No assets found.', 'performance-toolkit' ) ); ?>', false);
                    summary.textContent = summaryText;
                    return;
                }

                rows.forEach(function (row) {
                    if (!row || typeof row !== 'object') { return; }
                    addRow(String(row.type || '-'), String(row.url || '-'), String(row.category || '-'));
                });

                table.style.display = '';
                if (filtersBar) { filtersBar.style.display = 'flex'; }
                updateSummaryDisplay();
                setStatus('<?php echo esc_js( __( 'Detection complete.', 'performance-toolkit' ) ); ?>', false);
            })
            .catch(function (error) {
                setStatus(error && error.message ? error.message : '<?php echo esc_js( __( 'Detection failed.', 'performance-toolkit' ) ); ?>', true);
            })
            .finally(function () {
                runBtn.disabled = false;
            });
    });
}());
</script>
