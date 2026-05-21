<?php
/**
 * Media optimization admin page.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Media\ImageOptimizerDetector;

final class MediaOptimizationPage extends BladeAdminPage {

	private Settings $settings;

	private ImageOptimizerDetector $optimizer_detector;

	public function __construct( Settings $settings, ImageOptimizerDetector $optimizer_detector ) {
		$this->settings           = $settings;
		$this->optimizer_detector = $optimizer_detector;
	}

	public function slug(): string {
		return 'performance-toolkit-media-optimization';
	}

	public function menuTitle(): string {
		return __( 'Media Optimization', 'performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Performance Toolkit Media Optimization', 'performance-toolkit' );
	}

	public function iconKey(): string {
		return 'image';
	}

	public function view(): string {
		return 'admin.media-optimization-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		$active_optimizers  = $this->optimizer_detector->activeOptimizers();
		$lazyload_providers = $this->optimizer_detector->activeLazyLoadProviders();

		return array(
			'options'              => $this->settings->all(),
			'option_key'           => $this->settings->optionKey(),
			'settings_updated'     => isset( $_GET['settings-updated'] ) && (string) 'true' === wp_unslash( $_GET['settings-updated'] ),
			'active_optimizers'    => $active_optimizers,
			'lazyload_providers'   => $lazyload_providers,
			'external_lazyload_on' => array() !== $lazyload_providers,
			'optimizer_status'     => array() === $active_optimizers
				? __( 'None detected', 'performance-toolkit' )
				: implode( ', ', $active_optimizers ),
		);
	}
}
