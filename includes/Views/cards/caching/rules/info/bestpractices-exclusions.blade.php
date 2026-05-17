<x-info-card :title="__('Best Practices for Exclusions', 'performance-toolkit')">
	<x-list :items="[
		__('Exclude cart, checkout, and account pages', 'performance-toolkit'),
		__('Exclude AJAX endpoints and API routes', 'performance-toolkit'),
		__('Bypass cache for session-related cookies', 'performance-toolkit'),
		__('Keep exclusions as specific as possible', 'performance-toolkit'),
	]" />
</x-info-card>
