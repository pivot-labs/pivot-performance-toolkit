<?php
/**
 * Lazy load image handler.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Media;

use PerformanceToolkit\Contracts\ModuleInterface;
use PerformanceToolkit\Core\Settings;

final class LazyLoad implements ModuleInterface {

	private Settings $settings;

	private ImageOptimizerDetector $optimizer_detector;

	public function __construct( Settings $settings, ImageOptimizerDetector $optimizer_detector ) {
		$this->settings           = $settings;
		$this->optimizer_detector = $optimizer_detector;
	}

	public function register(): void {
		add_filter( 'the_content', array( $this, 'addLazyLoadingToImages' ), 20 );
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
