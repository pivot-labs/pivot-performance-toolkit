<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-error is-dismissible">
	<p>
		<strong>{{ esc_html__( 'Pivot Performance Toolkit – wp-config.php Update Failed', 'pivot-performance-toolkit' ) }}</strong>
	</p>
	<p>
		{{ $message }}
	</p>
</div>
