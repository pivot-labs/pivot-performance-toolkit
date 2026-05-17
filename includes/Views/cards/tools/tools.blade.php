<x-card :title="__('Tools', 'performance-toolkit')" id="ptk-tools">

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



</x-card>

