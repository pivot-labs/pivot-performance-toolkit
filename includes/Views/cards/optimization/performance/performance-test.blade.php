<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-performance-snapshot
	:options="$options"
	:last_score="(int) ($last_score ?? 0)"
	:has_result="(bool) ($has_result ?? false)"
	:show_table="true"
	:show_open_full_test="false"
/>


