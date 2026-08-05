<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
    @include('cards.optimization.media.media-optimization')
</div>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--sidebar">
    @include('cards.optimization.media.info.lazy-load')
</div>