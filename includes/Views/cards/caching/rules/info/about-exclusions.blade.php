<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-info-card :title="__('About Cache Exclusions', 'pivot-performance-toolkit')">
	<p>
		{{ __('Use exclusions to prevent caching of dynamic pages, user-specific content, and sensitive endpoints. This helps ensure correct content is served to your visitors.', 'pivot-performance-toolkit') }}
	</p>
</x-info-card>
