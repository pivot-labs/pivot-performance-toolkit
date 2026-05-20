<div class="ptk-col ptk-col--main">
    @include('cards.caching.page.cache')
    @include('cards.caching.page.object-cache')
</div>

<div class="ptk-col ptk-col--sidebar">
    @include('cards.caching.page.info.about')
    @include('cards.caching.page.quick-actions', array(
        'clear_action' => $clear_action,
        'clear_minified_action' => $clear_minified_action,
        'ajax_clear_action' => $ajax_clear_action,
        'ajax_clear_minified_action' => $ajax_clear_minified_action,
        'ajax_refresh_usage_action' => $ajax_refresh_usage_action,
        'ajax_clear_nonce' => $ajax_clear_nonce,
        'ajax_clear_minified_nonce' => $ajax_clear_minified_nonce,
        'ajax_refresh_usage_nonce' => $ajax_refresh_usage_nonce,
        'cache_cleared_message' => $cache_cleared_message,
        'minified_cache_cleared_message' => $minified_cache_cleared_message,
        'preload_not_implemented_message' => $preload_not_implemented_message,
    ))
</div>
