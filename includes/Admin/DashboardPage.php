<?php
/**
 * Dashboard admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Utils\FilesystemCheck;

final class DashboardPage extends BladeAdminPage {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function slug(): string {
		return 'pivot-performance-toolkit';
	}

	public function menuTitle(): string {
		return __( 'Dashboard', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Dashboard', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'layout-dashboard';
	}


	public function view(): string {
		return 'admin.dashboard-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		$last_result = get_option( 'pivot_performance_toolkit_last_performance_result', array() );
		$last_result = is_array( $last_result ) ? $last_result : array();
		$last_score  = PerformanceTest::calculateOverallScoreFromResult( $last_result );

		return array(
			'options'                  => $this->settings->all(),
			'fs_status'                => FilesystemCheck::getCachedStatus(),
			'performance_test_options' => PerformanceTest::getTestableContentOptions(),
			'last_score'               => $last_score,
			'has_result'               => PerformanceTest::hasStoredResult( $last_result ),
		);
	}
}
