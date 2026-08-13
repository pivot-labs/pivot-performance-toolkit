<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div role="tabpanel" aria-labelledby="pivot-performance-toolkit-tab-htaccess" id="pivot-performance-toolkit-panel-htaccess">
	<h3>{{ __('Apache .htaccess Configuration', 'pivot-performance-toolkit') }}</h3>
	<p>{{ __('Add this to your .htaccess file in the WordPress root directory. Most shared hosting uses Apache.', 'pivot-performance-toolkit') }}</p>

	@php
		$htaccess_applied = $htaccess_applied ?? false;
	@endphp

	<div class="pivot-performance-toolkit-card-notices mb-4" aria-live="polite"></div>

	<div
		class="pivot-performance-toolkit-htaccess-toggle mb-4 flex items-center justify-between gap-4 rounded-xl border p-4 {{ $htaccess_applied ? 'border-green-200 bg-green-50' : 'border-slate-200 bg-slate-50' }}"
		data-htaccess-toggle
		data-state="{{ $htaccess_applied ? 'applied' : 'not-applied' }}"
		data-apply-action="{{ esc_attr($apply_action ?? '') }}"
		data-apply-nonce="{{ esc_attr((string) ($apply_nonce ?? '')) }}"
		data-remove-action="{{ esc_attr($remove_action ?? '') }}"
		data-remove-nonce="{{ esc_attr((string) ($remove_nonce ?? '')) }}"
		data-label-applied="{{ esc_attr__('Applied to .htaccess', 'pivot-performance-toolkit') }}"
		data-label-not-applied="{{ esc_attr__('Not yet applied', 'pivot-performance-toolkit') }}"
		data-description-applied="{{ esc_attr__('Pivot Performance Toolkit is managing these cache headers directly in .htaccess.', 'pivot-performance-toolkit') }}"
		data-description-not-applied="{{ esc_attr__('Apply automatically, or copy the snippet below and add it yourself.', 'pivot-performance-toolkit') }}"
		data-button-label-apply="{{ esc_attr__('Apply Automatically', 'pivot-performance-toolkit') }}"
		data-button-label-remove="{{ esc_attr__('Remove', 'pivot-performance-toolkit') }}"
	>
		<div class="min-w-0">
			<p class="m-0 text-sm font-semibold {{ $htaccess_applied ? 'text-green-800' : 'text-slate-900' }}" data-htaccess-title>
				{{ $htaccess_applied ? __('Applied to .htaccess', 'pivot-performance-toolkit') : __('Not yet applied', 'pivot-performance-toolkit') }}
			</p>
			<p class="m-0 mt-1 text-sm {{ $htaccess_applied ? 'text-green-700' : 'text-slate-500' }}" data-htaccess-description>
				{{ $htaccess_applied
					? __('Pivot Performance Toolkit is managing these cache headers directly in .htaccess.', 'pivot-performance-toolkit')
					: __('Apply automatically, or copy the snippet below and add it yourself.', 'pivot-performance-toolkit') }}
			</p>
		</div>

		<button
			type="button"
			class="inline-flex min-h-9 shrink-0 items-center rounded-md px-4 text-sm font-medium {{ $htaccess_applied ? 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' : 'border border-blue-600 bg-blue-600 text-white hover:bg-blue-700' }}"
			data-htaccess-submit
		>
			{{ $htaccess_applied ? __('Remove', 'pivot-performance-toolkit') : __('Apply Automatically', 'pivot-performance-toolkit') }}
		</button>
	</div>

	<div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
		<div class="flex items-center justify-between gap-3">
			<strong class="text-sm text-slate-900">{{ __('Copy-friendly snippet:', 'pivot-performance-toolkit') }}</strong>
			<button type="button" class="pivot-performance-toolkit-copy-snippet inline-flex min-h-8 items-center rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50" id="pivot-performance-toolkit-copy-htaccess" data-text="{{ esc_attr($htaccess_snippet) }}">
				{{ __('Copy', 'pivot-performance-toolkit') }}
			</button>
		</div>
		<div class="pivot-performance-toolkit-snippet-wrapper mt-3" id="pivot-performance-toolkit-snippet-htaccess-wrapper">
			<pre class="pivot-performance-toolkit-snippet-pre m-0 overflow-x-auto rounded-md border border-slate-200 bg-white p-3 text-xs leading-6"><code>{{ $htaccess_snippet }}</code></pre>
		</div>
		<div class="mt-2 text-center">
			<button type="button" class="pivot-performance-toolkit-snippet-toggle inline-flex min-h-8 items-center rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50" data-target="pivot-performance-toolkit-snippet-htaccess-wrapper">
				{{ __('Expand Full Configuration', 'pivot-performance-toolkit') }}
			</button>
		</div>
	</div>

	<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
		<p class="m-0 mb-2 text-sm font-semibold text-amber-900">{{ __('Applying manually instead:', 'pivot-performance-toolkit') }}</p>
		<ol class="m-0 list-decimal space-y-1 pl-5 text-sm text-amber-800">
			<li>{{ __('Connect via FTP/SFTP to your server', 'pivot-performance-toolkit') }}</li>
			<li>{{ __('Navigate to your WordPress root (where wp-content, wp-admin, wp-includes are)', 'pivot-performance-toolkit') }}</li>
			<li>{{ __('Open .htaccess (may be hidden file)', 'pivot-performance-toolkit') }}</li>
			<li>{{ __('Add the above snippet (or replace entire file if new)', 'pivot-performance-toolkit') }}</li>
			<li>{{ __('Save and clear your site cache', 'pivot-performance-toolkit') }}</li>
		</ol>
	</div>
</div>