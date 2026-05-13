@props(['iconUrl'])

<div class="wrap ptk-wrap">
    <h1 class="ptk-page-title">
        <img src="{{ esc_url($iconUrl) }}" alt="" class="ptk-page-title-icon" />
        <span>{{ __('Performance Toolkit', 'performance-toolkit') }}</span>
    </h1>
    <hr class="wp-header-end">
    <div class="ptk-shell">
        <main class="ptk-content">
            {{ $slot }}
        </main>
    </div>
</div>

