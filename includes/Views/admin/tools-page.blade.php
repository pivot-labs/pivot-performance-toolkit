<x-card title="{{ __('Tools', 'performance-toolkit') }}" id="ptk-tools">

    @if ($cleared)
        <div class="notice notice-success is-dismissible">
            <p>
                @php
                    printf(
                        /* translators: %d: number of deleted files */
                        esc_html__('Cleared %d minified asset file(s).', 'performance-toolkit'),
                        esc_html((string) $removed_files)
                    );
                @endphp
            </p>
        </div>
    @endif

    @if ($tools_notice !== '' && $tools_message !== '')
        <div class="notice {{ $tools_notice === 'success' ? 'notice-success' : 'notice-error' }} is-dismissible">
            <p>{{ $tools_message }}</p>
        </div>
    @endif

    {{-- Minified CSS/JS cache --}}
    <div class="ptk-field">
        <h3 style="margin:0 0 8px;">{{ __('Minified CSS/JS cache', 'performance-toolkit') }}</h3>
        <p>
            @php
                printf(
                    /* translators: 1: file count, 2: formatted size */
                    esc_html__('%1$d file(s), %2$s total.', 'performance-toolkit'),
                    esc_html((string) $stats['count']),
                    esc_html($stats['size_formatted'])
                );
            @endphp
        </p>
        <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
            <input type="hidden" name="action" value="{{ esc_attr($clear_minified_action) }}" />
            @php wp_nonce_field('ptk_clear_minified_assets'); @endphp
            @php submit_button(__('Clear minified CSS/JS cache', 'performance-toolkit'), 'secondary', 'submit', false); @endphp
        </form>
    </div>

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

    {{-- Uninstall cleanup policy --}}
    <div class="ptk-field" style="margin-top:18px;">
        <h3 style="margin:0 0 8px;">{{ __('Uninstall cleanup policy', 'performance-toolkit') }}</h3>
        <p>{{ __('Choose whether plugin settings and cache data should be removed when the plugin is deleted from WordPress.', 'performance-toolkit') }}</p>
        <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
            <input type="hidden" name="action" value="{{ esc_attr($set_uninstall_policy_action) }}" />
            @php wp_nonce_field('ptk_set_uninstall_policy'); @endphp
            <label style="display:block;margin:6px 0 10px;">
                <input type="checkbox" name="ptk_remove_data_on_uninstall" value="1" {{ $cleanup_on_uninstall ? 'checked' : '' }} />
                <span>{{ __('Remove all Performance Toolkit data on uninstall', 'performance-toolkit') }}</span>
            </label>
            <p style="margin-top:-4px;color:#646970;">
                {{ __('If enabled, deleting the plugin removes its settings and cache files. If disabled, data is preserved for reinstall.', 'performance-toolkit') }}
            </p>
            @php submit_button(__('Save uninstall policy', 'performance-toolkit'), 'secondary', 'submit', false); @endphp
        </form>
    </div>

    {{-- Reset to safe defaults --}}
    <div class="ptk-field" style="margin-top:18px; padding:12px; background-color:#fef5f5; border-left:4px solid #d63638;">
        <h3 style="margin:0 0 8px; color:#d63638;">{{ __('Reset to safe defaults', 'performance-toolkit') }}</h3>
        <p>{{ __('Reset all Performance Toolkit settings to their recommended safe defaults. This action cannot be undone.', 'performance-toolkit') }}</p>
        <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
            <input type="hidden" name="action" value="{{ esc_attr($reset_to_defaults_action) }}" />
            @php wp_nonce_field('ptk_reset_to_defaults'); @endphp
            <label style="display:block;margin:6px 0 10px;">
                <input type="checkbox" name="ptk_confirm_reset" value="1" required />
                <span>{{ __('I understand this will reset all settings and cannot be undone', 'performance-toolkit') }}</span>
            </label>
            @php submit_button(__('Reset to safe defaults', 'performance-toolkit'), 'delete', 'submit', false); @endphp
        </form>
    </div>

</x-card>

