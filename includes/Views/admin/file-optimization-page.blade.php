<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
	@if ($settings_updated)
		<div class="notice notice-success is-dismissible">
			<p>{{ __('Settings saved successfully.', 'pivot-performance-toolkit') }}</p>
		</div>
	@endif

	@include('cards.optimization.file.quick')
	@include('cards.optimization.file.exclusions')
	@include('cards.optimization.file.http11')
</div>


<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--sidebar">

@include('cards.optimization.file.info.about')

@include('cards.optimization.file.info.recommendations')





</div>
