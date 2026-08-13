<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
    @include('cards.caching.browser.test')

    @include('cards.caching.browser.detection', array(
        'server_software' => $server_software,
    ))
    @include('cards.caching.browser.compression-settings', array(
        'htaccess_snippet'  => $htaccess_snippet,
        'nginx_snippet'     => $nginx_snippet,
        'server_software'   => $server_software,
    ))
</div>

<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--sidebar">
    @include('cards.caching.browser.info.browser-caching')
    @include('cards.caching.browser.info.testing')
    @include('cards.caching.browser.info.cache-busting')

</div>
