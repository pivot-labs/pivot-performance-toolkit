<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@if ($cache_purged)
    <div class="notice notice-success is-dismissible">
        <p>{{ __('Pivot Performance Toolkit cache was purged.', 'pivot-performance-toolkit') }}</p>
    </div>
@endif

@if ($page_cache_purged)
    <div class="notice notice-success is-dismissible">
        <p>{{ __("This page's cache was purged.", 'pivot-performance-toolkit') }}</p>
    </div>
@endif

<style>
    #wpadminbar .pivot-performance-toolkit-disabled {
        opacity: 0.5;
        pointer-events: none;
        cursor: not-allowed;
    }
    #wpadminbar .pivot-performance-toolkit-cache-status {
        font-weight: 600;
    }
    #wpadminbar .pivot-performance-toolkit-cache-status-hit {
        color: #7bd88f;
    }
    #wpadminbar .pivot-performance-toolkit-cache-status-miss {
        color: #ffce6a;
    }
    #wpadminbar .pivot-performance-toolkit-cache-status-bypass {
        color: #a7aaad;
    }
</style>

