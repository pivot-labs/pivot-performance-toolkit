<?php
/**
 * Lazy load image handler.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Core\Settings;

final class LazyLoad implements ModuleInterface {

	private Settings $settings;

	private ImageOptimizerDetector $optimizer_detector;

	public function __construct( Settings $settings, ImageOptimizerDetector $optimizer_detector ) {
		$this->settings           = $settings;
		$this->optimizer_detector = $optimizer_detector;
	}

	public function register(): void {
		// Run late (priority PHP_INT_MAX) so this always operates on the final HTML
		// after any image-optimization plugin (Imagify, ShortPixel, EWWW, etc.) has
		// already rewritten <img>/<picture> markup — avoids a hook-order race where
		// a loading="lazy" attribute added here gets dropped by a later rewrite.
		add_filter( 'the_content', array( $this, 'addLazyLoadingToImages' ), PHP_INT_MAX );
	}

	public function addLazyLoadingToImages( string $content ): string {
		if ( $this->optimizer_detector->hasExternalLazyLoadEnabled() ) {
			return $content;
		}

		if ( ! $this->settings->getBool( 'lazy_load_images' ) || is_admin() || ! str_contains( $content, '<img' ) ) {
			return $content;
		}

		return preg_replace( '/<img(?![^>]*loading=)([^>]*)>/i', '<img loading="lazy"$1>', $content ) ?? $content;
	}
}
