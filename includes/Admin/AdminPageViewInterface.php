<?php
/**
 * Admin page view contract.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
