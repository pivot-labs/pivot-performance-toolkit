<?php
/**
 * Media optimization admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Media\ImageOptimizerDetector;

final class MediaOptimizationPage extends BladeAdminPage {

	private Settings $settings;

	private ImageOptimizerDetector $optimizer_detector;

	public function __construct( Settings $settings, ImageOptimizerDetector $optimizer_detector ) {
		$this->settings           = $settings;
		$this->optimizer_detector = $optimizer_detector;
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-media-optimization';
	}

	public function menuTitle(): string {
		return __( 'Media Optimization', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Media Optimization', 'pivot-performance-toolkit' );
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
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only "was this just saved" display flag from WordPress core's own settings-updated redirect param; used only in a strict === comparison against a hardcoded literal, never stored or output raw.
			'settings_updated'     => isset( $_GET['settings-updated'] ) && (string) 'true' === wp_unslash( $_GET['settings-updated'] ),
			'active_optimizers'    => $active_optimizers,
			'lazyload_providers'   => $lazyload_providers,
			'external_lazyload_on' => array() !== $lazyload_providers,
			'optimizer_status'     => array() === $active_optimizers
				? __( 'None detected', 'pivot-performance-toolkit' )
				: implode( ', ', $active_optimizers ),
		);
	}
}
