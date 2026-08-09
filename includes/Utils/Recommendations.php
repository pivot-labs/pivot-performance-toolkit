<?php
/**
 * Centralized recommendations engine.
 *
 * Consolidates every environment-based recommendation the plugin makes
 * (website profile, HTTP-protocol-driven combine advice, per-setting
 * optimization suggestions, object-cache status, cache-directory
 * writability) into one place. Every admin surface that shows a
 * recommendation pulls from here rather than evaluating its own copy of the
 * same logic, so they can never drift out of sync with each other.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Utils;

use PivotPerformanceToolkit\Cache\ObjectCacheManager;
use PivotPerformanceToolkit\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Recommendations {

	private Settings $settings;

	private ObjectCacheManager $object_cache_manager;

	public function __construct( Settings $settings, ObjectCacheManager $object_cache_manager ) {
		$this->settings             = $settings;
		$this->object_cache_manager = $object_cache_manager;
	}

	/**
	 * @param string[] $categories Restrict to these categories; empty means all.
	 * @return array<int, array{id: string, category: string, severity: string, title: string, description: string, action: array{label: string, url: string}|null, signals: string[]}>
	 */
	public function collect( array $categories = array() ): array {
		$items = array_merge(
			$this->optimizationSettingRules(),
			$this->protocolRules(),
			$this->profileRule(),
			$this->cacheWritableRule(),
			$this->objectCacheRule()
		);

		if ( array() === $categories ) {
			return $items;
		}

		return array_values(
			array_filter(
				$items,
				static fn( array $item ): bool => in_array( $item['category'], $categories, true )
			)
		);
	}

	/**
	 * One rule per boolean optimization setting — only surfaced while the
	 * setting is actually off. Replaces a previous hardcoded "enable these"
	 * list that recommended the same three settings regardless of whether
	 * they were already on, and that separately relisted two of them a
	 * second time under different labels ("Minify Inline CSS/JS") further
	 * down the same card.
	 *
	 * @return array<int, array{id: string, category: string, severity: string, title: string, description: string, action: array{label: string, url: string}|null, signals: string[]}>
	 */
	private function optimizationSettingRules(): array {
		$rules = array(
			'defer_scripts'    => array(
				'title'       => __( 'Defer JavaScript', 'pivot-performance-toolkit' ),
				'description' => __( 'Deferring non-critical scripts lets the page render before they finish loading.', 'pivot-performance-toolkit' ),
			),
			'minify_html'      => array(
				'title'       => __( 'Minify HTML', 'pivot-performance-toolkit' ),
				'description' => __( 'Stripping comments and collapsing whitespace reduces the size of every page response.', 'pivot-performance-toolkit' ),
			),
			'minify_css'       => array(
				'title'       => __( 'Minify inline CSS', 'pivot-performance-toolkit' ),
				'description' => __( 'Removing whitespace and comments from inline <style> blocks reduces page weight.', 'pivot-performance-toolkit' ),
			),
			'minify_js'        => array(
				'title'       => __( 'Minify inline JavaScript', 'pivot-performance-toolkit' ),
				'description' => __( 'Removing comments and blank lines from inline <script> blocks reduces page weight.', 'pivot-performance-toolkit' ),
			),
			'lazy_load_images' => array(
				'title'       => __( 'Lazy-load images', 'pivot-performance-toolkit' ),
				'description' => __( 'Deferring offscreen images until they scroll into view speeds up initial page load.', 'pivot-performance-toolkit' ),
			),
		);

		$items = array();

		foreach ( $rules as $key => $meta ) {
			if ( $this->settings->getBool( $key ) ) {
				continue;
			}

			$items[] = array(
				'id'          => $key,
				'category'    => 'optimization',
				'severity'    => 'suggested',
				'title'       => $meta['title'],
				'description' => $meta['description'],
				'action'      => array(
					'label' => __( 'Enable', 'pivot-performance-toolkit' ),
					'url'   => self::fileOptimizationUrl(),
				),
				'signals'     => array(),
			);
		}

		return $items;
	}

	/**
	 * File combination is a tradeoff, not a strict "more is better" setting
	 * like the ones above — it only helps on HTTP/1.1, and can actively hurt
	 * cache efficiency on HTTP/2+. This is state-aware in both directions:
	 * it recommends enabling combine on HTTP/1.1 if it's off, and recommends
	 * *disabling* it on HTTP/2+ if it's currently on — the previous card
	 * only ever described the protocol, it never checked whether combine
	 * was already configured against that advice.
	 *
	 * @return array<int, array{id: string, category: string, severity: string, title: string, description: string, action: array{label: string, url: string}|null, signals: string[]}>
	 */
	private function protocolRules(): array {
		$protocol    = HttpProtocolDetector::detect();
		$combine_css = $this->settings->getBool( 'combine_css' );
		$combine_js  = $this->settings->getBool( 'combine_js' );

		if ( $protocol['is_http11'] ) {
			if ( $combine_css && $combine_js ) {
				return array();
			}

			return array(
				array(
					'id'          => 'combine_http11',
					'category'    => 'optimization',
					'severity'    => 'suggested',
					'title'       => __( 'Combine CSS/JS files', 'pivot-performance-toolkit' ),
					'description' => __( 'Your server appears to be using HTTP/1.1, where combining files into fewer requests can improve load time.', 'pivot-performance-toolkit' ),
					'action'      => array(
						'label' => __( 'Review', 'pivot-performance-toolkit' ),
						'url'   => self::fileOptimizationUrl(),
					),
					'signals'     => array(),
				),
			);
		}

		if ( $protocol['is_modern'] && ( $combine_css || $combine_js ) ) {
			return array(
				array(
					'id'          => 'combine_http2_plus',
					'category'    => 'optimization',
					'severity'    => 'warning',
					'title'       => __( 'Consider disabling file combination', 'pivot-performance-toolkit' ),
					'description' => __( 'Your server appears to support HTTP/2 or HTTP/3, where combining files is generally not beneficial and can reduce cache efficiency.', 'pivot-performance-toolkit' ),
					'action'      => array(
						'label' => __( 'Review', 'pivot-performance-toolkit' ),
						'url'   => self::fileOptimizationUrl(),
					),
					'signals'     => array(),
				),
			);
		}

		return array();
	}

	/**
	 * @return array<int, array{id: string, category: string, severity: string, title: string, description: string, action: array{label: string, url: string}|null, signals: string[]}>
	 */
	private function profileRule(): array {
		$detected = ProfileDetector::detectRecommendation();

		if ( array() === $detected['signals'] || $detected['profile'] === $this->settings->getString( 'website_profile' ) ) {
			return array();
		}

		return array(
			array(
				'id'          => 'website_profile',
				'category'    => 'profile',
				'severity'    => 'info',
				/* translators: %s: recommended website profile label, e.g. "WooCommerce" */
				'title'       => sprintf( __( 'Recommended profile: %s', 'pivot-performance-toolkit' ), $detected['label'] ),
				'description' => sprintf(
					/* translators: %s: comma-separated list of detected plugin names */
					__( 'Detected: %s. Switching your website profile can align presets with your actual setup.', 'pivot-performance-toolkit' ),
					implode( ', ', $detected['signals'] )
				),
				'action'      => array(
					'label' => __( 'Review', 'pivot-performance-toolkit' ),
					'url'   => self::settingsUrl(),
				),
				'signals'     => $detected['signals'],
			),
		);
	}

	/**
	 * @return array<int, array{id: string, category: string, severity: string, title: string, description: string, action: array{label: string, url: string}|null, signals: string[]}>
	 */
	private function cacheWritableRule(): array {
		$status = FilesystemCheck::getCachedStatus();

		if ( ! empty( $status['writable'] ) ) {
			return array();
		}

		return array(
			array(
				'id'          => 'cache_writable',
				'category'    => 'cache',
				'severity'    => 'warning',
				'title'       => __( 'Cache directory is not writable', 'pivot-performance-toolkit' ),
				'description' => __( 'One or more cache directories could not be written to — page caching and minified/combined assets will not work until this is fixed.', 'pivot-performance-toolkit' ),
				'action'      => array(
					'label' => __( 'Review', 'pivot-performance-toolkit' ),
					'url'   => self::cacheUrl(),
				),
				'signals'     => array(),
			),
		);
	}

	/**
	 * @return array<int, array{id: string, category: string, severity: string, title: string, description: string, action: array{label: string, url: string}|null, signals: string[]}>
	 */
	private function objectCacheRule(): array {
		if ( $this->object_cache_manager->isDropinInstalled() ) {
			return array();
		}

		return array(
			array(
				'id'          => 'object_cache',
				'category'    => 'object_cache',
				'severity'    => 'suggested',
				'title'       => __( 'Enable object caching', 'pivot-performance-toolkit' ),
				'description' => __( 'No object cache is currently active. Enabling one reduces repeated database queries across requests.', 'pivot-performance-toolkit' ),
				'action'      => array(
					'label' => __( 'Review', 'pivot-performance-toolkit' ),
					'url'   => self::cacheUrl(),
				),
				'signals'     => array(),
			),
		);
	}

	private static function fileOptimizationUrl(): string {
		return self::tabUrl( 'optimization', 'pivot-performance-toolkit-file-optimization' );
	}

	private static function settingsUrl(): string {
		return self::tabUrl( 'settings', 'pivot-performance-toolkit-settings' );
	}

	private static function cacheUrl(): string {
		return self::tabUrl( 'caching', 'pivot-performance-toolkit-cache' );
	}

	private static function tabUrl( string $section, string $tab ): string {
		return add_query_arg(
			array(
				'page'    => 'pivot-performance-toolkit',
				'section' => $section,
				'tab'     => $tab,
			),
			admin_url( 'admin.php' )
		);
	}
}
