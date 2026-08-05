<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main col-span-full">
	@if ($settings_updated && !isset($_GET['pivot_performance_toolkit_notice']))
		<div class="notice notice-success is-dismissible">
			<p>{{ __('Settings saved successfully.', 'pivot-performance-toolkit') }}</p>
		</div>
	@endif

	<section class="pivot-performance-toolkit-card" data-pivot-performance-toolkit-assets-detector data-ajax-action="{{ esc_attr((string) $ajax_detect_action) }}" data-ajax-nonce="{{ esc_attr((string) $ajax_detect_nonce) }}">
		<h2 class="pivot-performance-toolkit-card-title">{{ __('Assets Detector', 'pivot-performance-toolkit') }}</h2>
		<div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1.8fr)_minmax(280px,1fr)] md:items-start">
			<div id="pivot-performance-toolkit-assets-detector-controls">
				<p class="mt-0">{{ $assets_detector_message }}</p>

				<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:12px 0;">
					<label for="pivot-performance-toolkit-assets-target-url" class="screen-reader-text">{{ __('Select content to scan', 'pivot-performance-toolkit') }}</label>
					<select id="pivot-performance-toolkit-assets-target-url" data-pivot-performance-toolkit-assets-select style="min-width:320px;flex:1;">
						<option value="{{ esc_url(home_url('/')) }}">{{ __('Homepage', 'pivot-performance-toolkit') }}</option>
						@if (!empty($content_options['pages']))
							<optgroup label="{{ esc_attr__('Pages', 'pivot-performance-toolkit') }}">
								@foreach ($content_options['pages'] as $item)
									<option value="{{ esc_url($item['url']) }}">{{ esc_html($item['label']) }}</option>
								@endforeach
							</optgroup>
						@endif
						@if (!empty($content_options['posts']))
							<optgroup label="{{ esc_attr__('Posts', 'pivot-performance-toolkit') }}">
								@foreach ($content_options['posts'] as $item)
									<option value="{{ esc_url($item['url']) }}">{{ esc_html($item['label']) }}</option>
								@endforeach
							</optgroup>
						@endif
					</select>
					<button type="button" class="button button-primary" data-pivot-performance-toolkit-assets-run>{{ __('Detect Assets', 'pivot-performance-toolkit') }}</button>
				</div>

				<p data-pivot-performance-toolkit-assets-status style="margin:8px 0 10px;">{{ __('Idle', 'pivot-performance-toolkit') }}</p>
				<p data-pivot-performance-toolkit-assets-summary style="margin:0 0 12px; color:#4b5563;"></p>
			</div>

			<div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
				<h2 class="m-0 text-sm font-semibold text-gray-900">{{ __('How it works', 'pivot-performance-toolkit') }}</h2>
				<p class="mt-2 mb-0 text-sm text-gray-600">{{ __('The detector requests the selected URL, reads the returned HTML, and lists script, stylesheet, and image URLs found on that page.', 'pivot-performance-toolkit') }}</p>
			</div>
		</div>


		<div data-pivot-performance-toolkit-assets-filters style="display:none;margin-bottom:12px;gap:6px;flex-wrap:wrap;align-items:center;">
			<span class="text-xs font-semibold text-gray-500" style="margin-right:4px;">{{ __('Filter:', 'pivot-performance-toolkit') }}</span>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="all" aria-pressed="true">{{ __('All', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="Theme">{{ __('Theme', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="Plugin">{{ __('Plugin', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="External">{{ __('External', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="Core">{{ __('Core (WP)', 'pivot-performance-toolkit') }}</button>
			<button type="button" class="button" data-pivot-performance-toolkit-filter="Other">{{ __('Other', 'pivot-performance-toolkit') }}</button>
		</div>

		<table class="widefat striped" data-pivot-performance-toolkit-assets-table style="display:none;">
			<colgroup>
				<col style="width: 80px;">
				<col style="width: auto;">
				<col style="width: 80px;">
			</colgroup>
			<thead>
				<tr>
					<th scope="col">{{ __('Type', 'pivot-performance-toolkit') }}</th>
					<th scope="col">{{ __('Asset URL', 'pivot-performance-toolkit') }}</th>
					<th scope="col">{{ __('Category', 'pivot-performance-toolkit') }}</th>
				</tr>
			</thead>
			<tbody data-pivot-performance-toolkit-assets-rows></tbody>
		</table>
	</section>
</div>

<script>
(function () {
	var root = document.querySelector('[data-pivot-performance-toolkit-assets-detector]');
	if (!root) {
		return;
	}

	var select    = root.querySelector('[data-pivot-performance-toolkit-assets-select]');
	var runBtn    = root.querySelector('[data-pivot-performance-toolkit-assets-run]');
	var status    = root.querySelector('[data-pivot-performance-toolkit-assets-status]');
	var summary   = root.querySelector('[data-pivot-performance-toolkit-assets-summary]');
	var table     = root.querySelector('[data-pivot-performance-toolkit-assets-table]');
	var rowsWrap  = root.querySelector('[data-pivot-performance-toolkit-assets-rows]');
	var filtersBar = root.querySelector('[data-pivot-performance-toolkit-assets-filters]');

	if (!select || !runBtn || !status || !summary || !table || !rowsWrap) {
		return;
	}

	var activeFilter = 'all';
	var typeCounts = { css: 0, javascript: 0, fonts: 0, images: 0, other: 0 };
	var summaryLabels = {
		css: '<?php echo esc_js( __( 'CSS', 'pivot-performance-toolkit' ) ); ?>',
		javascript: '<?php echo esc_js( __( 'JavaScript', 'pivot-performance-toolkit' ) ); ?>',
		fonts: '<?php echo esc_js( __( 'Fonts', 'pivot-performance-toolkit' ) ); ?>',
		images: '<?php echo esc_js( __( 'Images', 'pivot-performance-toolkit' ) ); ?>',
		other: '<?php echo esc_js( __( 'Other', 'pivot-performance-toolkit' ) ); ?>'
	};
	var detectedLabel = '<?php echo esc_js( __( 'Detected:', 'pivot-performance-toolkit' ) ); ?>';

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
			filtersBar.querySelectorAll('[data-pivot-performance-toolkit-filter]').forEach(function (btn) {
				var isAll = btn.getAttribute('data-pivot-performance-toolkit-filter') === 'all';
				btn.setAttribute('aria-pressed', isAll ? 'true' : 'false');
				btn.classList.toggle('button-primary', isAll);
			});
		}
	}

	function applyFilter(filter) {
		activeFilter = filter;
		rowsWrap.querySelectorAll('tr[data-pivot-performance-toolkit-category]').forEach(function (tr) {
			var cat = tr.getAttribute('data-pivot-performance-toolkit-category') || '';
			tr.style.display = (filter === 'all' || cat === filter) ? '' : 'none';
		});
		if (filtersBar) {
			filtersBar.querySelectorAll('[data-pivot-performance-toolkit-filter]').forEach(function (btn) {
				var isActive = btn.getAttribute('data-pivot-performance-toolkit-filter') === filter;
				btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
				btn.classList.toggle('button-primary', isActive);
			});
		}
	}

	function addRow(type, url, category) {
		var tr = document.createElement('tr');
		tr.setAttribute('data-pivot-performance-toolkit-category', category);

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
			var btn = e.target.closest('[data-pivot-performance-toolkit-filter]');
			if (!btn) { return; }
			applyFilter(btn.getAttribute('data-pivot-performance-toolkit-filter') || 'all');
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
			setStatus('<?php echo esc_js( __( 'Please select a URL first.', 'pivot-performance-toolkit' ) ); ?>', true);
			return;
		}

		runBtn.disabled = true;
		clearRows();
		setStatus('<?php echo esc_js( __( 'Detecting assets...', 'pivot-performance-toolkit' ) ); ?>', false);

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
						: '<?php echo esc_js( __( 'Detection failed.', 'pivot-performance-toolkit' ) ); ?>';
					throw new Error(msg);
				}

				var rows = payload.data && Array.isArray(payload.data.rows) ? payload.data.rows : [];
				var summaryText = payload.data && payload.data.summary_text ? String(payload.data.summary_text) : '';

				if (rows.length === 0) {
					setStatus('<?php echo esc_js( __( 'Detection complete. No assets found.', 'pivot-performance-toolkit' ) ); ?>', false);
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
				setStatus('<?php echo esc_js( __( 'Detection complete.', 'pivot-performance-toolkit' ) ); ?>', false);
			})
			.catch(function (error) {
				setStatus(error && error.message ? error.message : '<?php echo esc_js( __( 'Detection failed.', 'pivot-performance-toolkit' ) ); ?>', true);
			})
			.finally(function () {
				runBtn.disabled = false;
			});
	});
}());
</script>
