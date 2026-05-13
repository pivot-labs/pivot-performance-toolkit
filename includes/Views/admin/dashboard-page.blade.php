<x-card title="{{ __('Optimization Overview', 'performance-toolkit') }}" id="ptk-dashboard">
    @if (empty($fs_status['writable']))
        <div style="margin-bottom: 16px; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
            <p style="margin: 0;">
                <strong>{{ __('Warning:', 'performance-toolkit') }}</strong>
                {{ __('Cache directory not writable. Caching is disabled. See System Status for details.', 'performance-toolkit') }}
            </p>
        </div>
    @endif

    <div class="ptk-stats-grid">
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Page cache', 'performance-toolkit') }}</span>
            <strong>{{ !empty($options['enable_page_cache']) ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit') }}</strong>
        </div>
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Script defer', 'performance-toolkit') }}</span>
            <strong>{{ !empty($options['defer_scripts']) ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit') }}</strong>
        </div>
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Image lazy loading', 'performance-toolkit') }}</span>
            <strong>{{ !empty($options['lazy_load_images']) ? __('Enabled', 'performance-toolkit') : __('Disabled', 'performance-toolkit') }}</strong>
        </div>
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Cache directory', 'performance-toolkit') }}</span>
            <strong style="color: {{ !empty($fs_status['writable']) ? '#28a745' : '#dc3545' }};">
                {{ !empty($fs_status['writable']) ? __('Writable', 'performance-toolkit') : __('Read-only', 'performance-toolkit') }}
            </strong>
        </div>
    </div>
</x-card>

