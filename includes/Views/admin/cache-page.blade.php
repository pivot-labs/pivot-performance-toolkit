<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
	@include('cards.caching.page.cache')
	@include('cards.caching.page.object-cache', array(
		'object_cache'                     => $object_cache,
		'ajax_enable_object_cache_action'  => $ajax_enable_object_cache_action,
		'ajax_disable_object_cache_action' => $ajax_disable_object_cache_action,
		'ajax_flush_object_cache_action'   => $ajax_flush_object_cache_action,
		'ajax_enable_object_cache_nonce'   => $ajax_enable_object_cache_nonce,
		'ajax_disable_object_cache_nonce'  => $ajax_disable_object_cache_nonce,
		'ajax_flush_object_cache_nonce'    => $ajax_flush_object_cache_nonce,
	))
</div>

<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--sidebar">
    @include('cards.caching.page.info.about')
    @include('cards.caching.page.quick-actions', array(
        'clear_action' => $clear_action,
        'clear_minified_action' => $clear_minified_action,
        'ajax_clear_action' => $ajax_clear_action,
        'ajax_clear_minified_action' => $ajax_clear_minified_action,
        'ajax_preload_action' => $ajax_preload_action,
        'ajax_clear_nonce' => $ajax_clear_nonce,
        'ajax_clear_minified_nonce' => $ajax_clear_minified_nonce,
        'ajax_preload_nonce' => $ajax_preload_nonce,
        'cache_cleared_message' => $cache_cleared_message,
        'minified_cache_cleared_message' => $minified_cache_cleared_message,
        'preload_cache_message' => $preload_cache_message,
    ))
</div>
