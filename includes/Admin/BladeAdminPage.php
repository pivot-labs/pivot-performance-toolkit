<?php
/**
 * Blade-based admin page base class.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Views\BladeEngine;

abstract class BladeAdminPage implements AdminPageInterface, AdminPageViewInterface {

	public function renderContent(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BladeEngine::view() returns HTML already escaped by Blade's {{ }} at render time; re-escaping here would break the markup.
		echo BladeEngine::view( $this->view(), $this->viewData() );
	}

	/**
	 * @return array<string, mixed>
	 */
	final public function viewData(): array {
		return $this->buildViewData();
	}

	/**
	 * @return array<string, mixed>
	 */
	abstract protected function buildViewData(): array;
}
