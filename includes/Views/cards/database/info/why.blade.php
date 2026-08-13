<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-info-card :title="__('Why optimize your database?', 'pivot-performance-toolkit')">
	@php
		$benefits = array(
			__('Improve overall site performance', 'pivot-performance-toolkit'),
			__('Reduce database size', 'pivot-performance-toolkit'),
			__('Speed up queries and page loads', 'pivot-performance-toolkit'),
			__('Remove unnecessary clutter', 'pivot-performance-toolkit'),
		);
	@endphp

	<p style="margin:0 0 16px;color:#646970">{{ __('Regular database optimization can:', 'pivot-performance-toolkit') }}</p>
	<x-info-list :items="$benefits" />
	<p style="margin:0;color:#646970">{{ __('Recommended: Optimize your database weekly.', 'pivot-performance-toolkit') }}</p>

	<form method="post" action="{{ esc_url(admin_url('admin-post.php')) }}" style="margin-top:16px;" data-disable-on-submit>
		<input type="hidden" name="action" value="{{ esc_attr($cleanup_action) }}" />
		<input type="hidden" name="pivot_performance_toolkit_task" value="optimize" />
		<span class="inline-flex items-center gap-2">
			@php
				wp_nonce_field('pivot_performance_toolkit_database_cleanup');
				submit_button(__('Optimize all tables', 'pivot-performance-toolkit'), 'secondary', 'submit', false);
			@endphp
			<span class="spinner" style="float: none; margin: 0;"></span>
		</span>
	</form>
</x-info-card>