<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
    @include('cards.optimization.performance.performance-test', array(
        'options' => $options,
        'last_score' => $last_score,
        'has_result' => $has_result,
    ))
</div>

