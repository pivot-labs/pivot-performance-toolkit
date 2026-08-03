<?php
/**
 * Card showcase admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CardShowcasePage extends BladeAdminPage {

	public function slug(): string {
		return 'pivot-performance-toolkit-card-showcase';
	}

	public function menuTitle(): string {
		return __( 'Card Showcase', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Card Showcase', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'layout-dashboard';
	}

	public function view(): string {
		return 'admin.card-system-showcase';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		return array();
	}
}
