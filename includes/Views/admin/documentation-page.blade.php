<x-card :title="__('Documentation', 'performance-toolkit')" id="ptk-documentation">
    <p>{{ __('Full guides, setup instructions, and troubleshooting are available in the external documentation site.', 'performance-toolkit') }}</p>
    <p>
        <a
            class="button button-primary"
            href="{{ esc_url($docs_url) }}"
            target="_blank"
            rel="noopener noreferrer"
        >
            {{ __('Open Documentation', 'performance-toolkit') }}
        </a>
    </p>
</x-card>

