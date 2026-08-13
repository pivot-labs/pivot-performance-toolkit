<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pivot-performance-toolkit-col pivot-performance-toolkit-col--main">
	@if ($settings_notice !== '' && $settings_message !== '')
		<div class="notice {{ $settings_notice === 'success' ? 'notice-success' : 'notice-error' }} is-dismissible">
			<p>{{ $settings_message }}</p>
		</div>
	@endif

	@include('cards.settings.website-profile')

	@if ($tools_notice !== '' && $tools_message !== '')
		<div class="notice {{ $tools_notice === 'success' ? 'notice-success' : 'notice-error' }} is-dismissible">
			<p>{{ $tools_message }}</p>
		</div>
	@endif

	@include('cards.settings.uninstall-policy')
</div>


