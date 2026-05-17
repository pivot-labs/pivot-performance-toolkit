
<x-card :title="__('Minified CSS/JS cache', 'performance-toolkit')" id="ptk-tools">



{{-- Minified CSS/JS cache --}}
<div class="ptk-field">
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

</x-card>