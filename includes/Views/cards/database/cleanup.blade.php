<x-card :title="__('Cleanup', 'performance-toolkit')">
    <p style="margin:0 0 16px;color:#646970">{{ __('Remove unnecessary data to keep your database lean and fast.', 'performance-toolkit') }}</p>

    <div class="ptk-cleanup-list">
        @foreach ($cleanup_items as $task => $item)
            <div class="ptk-cleanup-item">
                <div class="ptk-cleanup-item-info">
                    <div class="ptk-cleanup-item-label">{{ $item['label'] }}</div>
                    <div class="ptk-cleanup-item-desc">{{ $item['desc'] }}</div>
                </div>
                <span class="ptk-cleanup-badge {{ $item['count'] > 0 ? 'has-items' : '' }}">
                    {{ number_format_i18n((int) $item['count']) }}
                </span>
                <form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}">
                    <input type="hidden" name="action" value="{{ esc_attr($cleanup_action) }}" />
                    <input type="hidden" name="ptk_task" value="{{ esc_attr($task) }}" />
                    @php
                        wp_nonce_field('ptk_database_cleanup');
                    @endphp
                    <button
                        type="submit"
                        class="button button-secondary ptk-cleanup-btn"
                        {{ (int) $item['count'] === 0 ? 'disabled' : '' }}
                    >
                        {{ __('Clean', 'performance-toolkit') }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>
</x-card>

