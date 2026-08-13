<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<x-card-sectioned-split
		:title="__('Export / Import Configuration', 'pivot-performance-toolkit')"
		:description="__('Transfer Pivot Performance Toolkit settings between websites or create reusable configuration backups for staging, migrations, and deployment workflows.', 'pivot-performance-toolkit')"
		icon="arrow-up-down"
		tone="blue"
>
	<x-card-section
			:title="__('Export live configuration', 'pivot-performance-toolkit')"
			:description="__('Download the current plugin settings as a portable JSON bundle before changing environments or trying a more aggressive performance profile.', 'pivot-performance-toolkit')"
			noticeTone="info"
			:noticeTitle="__('Includes metadata', 'pivot-performance-toolkit')"
			:noticeText="__('Exports can include schema version, plugin version, and the export timestamp so support teams can review what was deployed.', 'pivot-performance-toolkit')"
	>
		<div class="space-y-4">

			<form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
				<input type="hidden" name="action" value="{{ esc_attr($export_settings_action) }}" />
				@php wp_nonce_field('pivot_performance_toolkit_export_settings'); @endphp
				<label style="display:block;margin:6px 0 10px;">
					<input type="checkbox" name="pivot_performance_toolkit_include_secrets" value="1" />
					<span>{{ __('Include API tokens and secret integration keys', 'pivot-performance-toolkit') }}</span>
				</label>
				<p style="margin-top:-4px;color:#b32d2e;">
					{{ __('Warning: exported files with secrets should be stored securely and never committed to version control.', 'pivot-performance-toolkit') }}
				</p>
				@php submit_button(__('Export settings', 'pivot-performance-toolkit'), 'secondary', 'submit', false); @endphp
			</form>

		</div>
	</x-card-section>

	<x-card-section
			:title="__('Import configuration package', 'pivot-performance-toolkit')"
			:description="__('Apply a previously exported configuration file during migrations or when restoring a known-good setup on staging or production.', 'pivot-performance-toolkit')"
			noticeTone="danger"
			:noticeTitle="__('Validate before import', 'pivot-performance-toolkit')"
			:noticeText="__('A malformed import can overwrite current settings. Always review the incoming environment and confirm it matches the current site before applying it.', 'pivot-performance-toolkit')"
	>
		<div class="space-y-4">

			<div class="flex flex-col gap-3 sm:flex-row">
				<form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" enctype="multipart/form-data">
					<input type="hidden" name="action" value="{{ esc_attr($import_settings_action) }}" />
					@php wp_nonce_field('pivot_performance_toolkit_import_settings'); @endphp
					<input type="file" name="pivot_performance_toolkit_settings_import_file" accept=".json,application/json" required  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200" />

					<div style="margin-top:10px;">
						@php submit_button(__('Import settings', 'pivot-performance-toolkit'), 'secondary', 'submit', false); @endphp
					</div>
				</form>

			</div>
		</div>
	</x-card-section>
</x-card-sectioned-split>