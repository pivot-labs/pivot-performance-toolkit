<x-card :title="__('Import/Export', 'performance-toolkit')" id="ptk-tools">


{{-- Export settings --}}
<div class="ptk-field" style="margin-top:18px;">
    <h3 style="margin:0 0 8px;">{{ __('Export settings', 'performance-toolkit') }}</h3>
    <p>{{ __('Download current Performance Toolkit settings as a JSON file.', 'performance-toolkit') }}</p>
    <p style="margin:8px 0 12px;padding:8px 12px;background-color:#f0f6fc;border-left:3px solid #0969da;color:#24292f;">
        {{ __('Includes all settings plus export metadata such as schema version, export timestamp, and plugin version.', 'performance-toolkit') }}
    </p>
    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
        <input type="hidden" name="action" value="{{ esc_attr($export_settings_action) }}" />
        @php wp_nonce_field('ptk_export_settings'); @endphp
        <label style="display:block;margin:6px 0 10px;">
            <input type="checkbox" name="ptk_include_secrets" value="1" />
            <span>{{ __('Include secret API keys in export', 'performance-toolkit') }}</span>
        </label>
        <p style="margin-top:-4px;color:#b32d2e;">
            {{ __('Warning: exported files with secrets should be stored securely and never committed to version control.', 'performance-toolkit') }}
        </p>
        @php submit_button(__('Export settings', 'performance-toolkit'), 'secondary', 'submit', false); @endphp
    </form>
</div>

{{-- Import settings --}}
<div class="ptk-field" style="margin-top:18px;">
    <h3 style="margin:0 0 8px;">{{ __('Import settings', 'performance-toolkit') }}</h3>
    <p>{{ __('Import settings from a previously exported JSON file.', 'performance-toolkit') }}</p>
    <p style="margin:8px 0 12px;padding:8px 12px;background-color:#f0f6fc;border-left:3px solid #0969da;color:#24292f;">
        {{ __('Imports settings with schema validation and a report showing how many keys were imported, ignored, or preserved.', 'performance-toolkit') }}
    </p>
    <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" enctype="multipart/form-data">
        <input type="hidden" name="action" value="{{ esc_attr($import_settings_action) }}" />
        @php wp_nonce_field('ptk_import_settings'); @endphp
        <input type="file" name="ptk_settings_import_file" accept=".json,application/json" required />
        <div style="margin-top:10px;">
            @php submit_button(__('Import settings', 'performance-toolkit'), 'secondary', 'submit', false); @endphp
        </div>
    </form>
</div>


</x-card>

