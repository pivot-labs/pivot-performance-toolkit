<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
    @if ($tools_notice !== '' && $tools_message !== '')
        <div class="notice {{ $tools_notice === 'success' ? 'notice-success' : 'notice-error' }} is-dismissible">
            <p>{{ $tools_message }}</p>
        </div>
    @endif

    @include('cards.tools.import-export')
</div>

