<div class="ptk-col ptk-col--main">
    @include('cards.caching.browser-cache-test', array(
        'home_url' => $home_url,
    ))

    @include('cards.caching.browser-cache-detection', array(
        'server_software' => $server_software,
    ))
    @include('cards.caching.browser-cache-compression-settings', array(
        'htaccess_snippet'  => $htaccess_snippet,
        'nginx_snippet'     => $nginx_snippet,
        'server_software'   => $server_software,
    ))
</div>

<div class="ptk-col ptk-col--sidebar">
    @include('cards.caching.info-browser-caching')
    @include('cards.caching.info-browser-cache-testing')
    @include('cards.caching.info-cache-busting')

</div>
