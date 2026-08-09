<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@include('cards.overview.optimization-overview')

@include('cards.overview.recommendations', array(
	'recommendations' => $recommendations,
))

@include('cards.overview.performance-test', array(
	'options' => $performance_test_options,
	'last_score' => $last_score,
	'has_result' => $has_result,
))

