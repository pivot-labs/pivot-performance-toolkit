<?php
/**
 * Advanced rules admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Core\Settings;

final class AdvancedRulesPage extends BladeAdminPage {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-advanced-rules';
	}

	public function menuTitle(): string {
		return __( 'Advanced Rules', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Advanced Rules', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'sliders-horizontal';
	}

	public function view(): string {
		return 'admin.advanced-rules-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		$options = $this->settings->all();

		return array(
			'options'          => $options,
			'option_key'       => $this->settings->optionKey(),
			'settings_updated' => FlashNotice::settingsSaved(),
		);
	}
}
