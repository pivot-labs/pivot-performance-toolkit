<?php
/**
 * Admin page view contract.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

interface AdminPageViewInterface {

	/**
	 * Blade view name for the page content area.
	 */
	public function view(): string;

	/**
	 * Data passed to the Blade view.
	 *
	 * @return array<string, mixed>
	 */
	public function viewData(): array;
}
