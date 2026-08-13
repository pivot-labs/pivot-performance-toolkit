<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
	@include('cards.database.table', array(
		'table_stats' => $table_stats,
		'show_overhead' => $show_overhead,
		'sort_by' => $sort_by,
		'sort_dir' => $sort_dir,
	))
</div>


