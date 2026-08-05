<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-info-card :title="__('Best Practices for Exclusions', 'pivot-performance-toolkit')">
	<x-list :items="[
		__('Exclude cart, checkout, and account pages', 'pivot-performance-toolkit'),
		__('Exclude AJAX endpoints and API routes', 'pivot-performance-toolkit'),
		__('Bypass cache for session-related cookies', 'pivot-performance-toolkit'),
		__('Keep exclusions as specific as possible', 'pivot-performance-toolkit'),
	]" />
</x-info-card>
