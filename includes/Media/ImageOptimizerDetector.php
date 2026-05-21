<?php
/**
 * Image optimizer plugin detector.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Media;

final class ImageOptimizerDetector {

	/**
	 * @return string[]
	 */
	public function activeOptimizers(): array {
		$active_plugins = $this->activePluginBasenames();

		$detected = array();

		if ( $this->pluginIsActive( 'shortpixel-image-optimiser/wp-shortpixel.php', $active_plugins )
			|| defined( 'SHORTPIXEL_PIXEL' )
			|| class_exists( 'ShortPixelAI' ) ) {
			$detected[] = 'ShortPixel';
		}

		if ( $this->pluginIsActive( 'imagify/imagify.php', $active_plugins )
			|| defined( 'IMAGIFY_VERSION' )
			|| function_exists( 'imagify_get_instance' ) ) {
			$detected[] = 'Imagify';
		}

		if ( $this->pluginIsActive( 'wp-smushit/wp-smush.php', $active_plugins )
			|| defined( 'WP_SMUSH_VERSION' )
			|| class_exists( 'Smush\\Core\\Plugin' ) ) {
			$detected[] = 'Smush';
		}

		if ( $this->pluginIsActive( 'ewww-image-optimizer/ewww-image-optimizer.php', $active_plugins )
			|| defined( 'EWWW_IMAGE_OPTIMIZER_VERSION' ) ) {
			$detected[] = 'EWWW Image Optimizer';
		}

		if ( $this->pluginIsActive( 'optimole-wp/optimole-wp.php', $active_plugins )
			|| defined( 'OPTML_VERSION' )
			|| class_exists( 'Optimole\\Sdk\\Optml' ) ) {
			$detected[] = 'Optimole';
		}

		return array_values( array_unique( $detected ) );
	}

	public function hasExternalLazyLoadEnabled(): bool {
		return $this->activeLazyLoadProviders() !== array();
	}

	/**
	 * @return string[]
	 */
	public function activeLazyLoadProviders(): array {
		$active_plugins = $this->activePluginBasenames();
		$providers      = array();

		if ( $this->isSmushLazyLoadEnabled( $active_plugins ) ) {
			$providers[] = 'Smush';
		}

		if ( $this->isEwwwLazyLoadEnabled( $active_plugins ) ) {
			$providers[] = 'EWWW Image Optimizer';
		}

		if ( $this->isOptimoleLazyLoadEnabled( $active_plugins ) ) {
			$providers[] = 'Optimole';
		}

		/**
		 * Allow extensions to add lazy-load providers we may not detect yet.
		 *
		 * @param string[] $providers
		 */
		$providers = apply_filters( 'performance_toolkit_active_lazyload_providers', $providers );

		if ( ! is_array( $providers ) ) {
			return array();
		}

		return array_values( array_unique( array_map( 'strval', $providers ) ) );
	}

	/**
	 * @return string[]
	 */
	private function activePluginBasenames(): array {
		$active = get_option( 'active_plugins', array() );

		if ( ! is_array( $active ) ) {
			$active = array();
		}

		if ( is_multisite() ) {
			$network_active = get_site_option( 'active_sitewide_plugins', array() );

			if ( is_array( $network_active ) ) {
				$active = array_merge( $active, array_keys( $network_active ) );
			}
		}

		return array_map( 'strval', $active );
	}

	/**
	 * @param string[] $active_plugins
	 */
	private function pluginIsActive( string $basename, array $active_plugins ): bool {
		return in_array( $basename, $active_plugins, true );
	}

	/**
	 * @param string[] $active_plugins
	 */
	private function isSmushLazyLoadEnabled( array $active_plugins ): bool {
		if ( ! $this->pluginIsActive( 'wp-smushit/wp-smush.php', $active_plugins ) ) {
			return false;
		}

		$settings = get_option( 'wp-smush-settings', array() );

		return is_array( $settings ) && ! empty( $settings['lazy_load'] );
	}

	/**
	 * @param string[] $active_plugins
	 */
	private function isEwwwLazyLoadEnabled( array $active_plugins ): bool {
		if ( ! $this->pluginIsActive( 'ewww-image-optimizer/ewww-image-optimizer.php', $active_plugins ) ) {
			return false;
		}

		return (bool) get_option( 'ewww_image_optimizer_lazy_load', false );
	}

	/**
	 * @param string[] $active_plugins
	 */
	private function isOptimoleLazyLoadEnabled( array $active_plugins ): bool {
		if ( ! $this->pluginIsActive( 'optimole-wp/optimole-wp.php', $active_plugins ) ) {
			return false;
		}

		$settings = get_option( 'optml_settings', array() );

		if ( ! is_array( $settings ) ) {
			return false;
		}

		if ( array_key_exists( 'lazyload', $settings ) ) {
			return ! empty( $settings['lazyload'] );
		}

		// Optimole usually manages lazy-load by default if active.
		return true;
	}
}
