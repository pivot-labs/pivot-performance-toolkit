@if ($cache_purged)
    <div class="notice notice-success is-dismissible">
        <p>{{ __('Performance Toolkit cache was purged.', 'performance-toolkit') }}</p>
    </div>
@endif

@if ($page_cache_purged)
    <div class="notice notice-success is-dismissible">
        <p>{{ __("This page's cache was purged.", 'performance-toolkit') }}</p>
    </div>
@endif

<style>
    #wpadminbar .ptk-disabled {
        opacity: 0.5;
        pointer-events: none;
        cursor: not-allowed;
    }
    #wpadminbar .ptk-cache-status {
        font-weight: 600;
    }
    #wpadminbar .ptk-cache-status-hit {
        color: #7bd88f;
    }
    #wpadminbar .ptk-cache-status-miss {
        color: #ffce6a;
    }
    #wpadminbar .ptk-cache-status-bypass {
        color: #a7aaad;
    }
</style>

