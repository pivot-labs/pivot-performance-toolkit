<?php
/**
 * Optimization performance test admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PerformancePage extends BladeAdminPage {

	public function slug(): string {
		return 'pivot-performance-toolkit-performance';
	}

	public function menuTitle(): string {
		return __( 'Performance', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Performance', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'activity';
	}

	public function view(): string {
		return 'admin.performance-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		$last_result = get_option( 'pivot_performance_toolkit_last_performance_result', array() );
		$last_result = is_array( $last_result ) ? $last_result : array();
		$last_score  = PerformanceTest::calculateOverallScoreFromResult( $last_result );

		return array(
			'options'    => self::getTestableContentOptions(),
			'last_score' => $last_score,
			'has_result' => PerformanceTest::hasStoredResult( $last_result ),
		);
	}

	/**
	 * @return array{pages:array<int,array{label:string,url:string}>,posts:array<int,array{label:string,url:string}>}
	 */
	private static function getTestableContentOptions(): array {
		$types  = array(
			'pages' => 'page',
			'posts' => 'post',
		);
		$result = array(
			'pages' => array(),
			'posts' => array(),
		);

		$front_page_id = 0;
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );
		}

		foreach ( $types as $bucket => $post_type ) {
			$exclude = ( 'pages' === $bucket && $front_page_id > 0 ) ? array( $front_page_id ) : array();
			$items   = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'exclude'        => $exclude,
				)
			);

			if ( ! is_array( $items ) || empty( $items ) ) {
				continue;
			}

			foreach ( $items as $post_id ) {
				$url = get_permalink( (int) $post_id );

				if ( ! is_string( $url ) || '' === $url ) {
					continue;
				}

				$title = get_the_title( (int) $post_id );

				$label = is_string( $title ) && '' !== trim( $title ) ? $title : sprintf(
					/* translators: %d: post ID. */
					__( 'Untitled #%d', 'pivot-performance-toolkit' ),
					(int) $post_id
				);

				$result[ $bucket ][] = array(
					'label' => $label,
					'url'   => $url,
				);
			}

			usort(
				$result[ $bucket ],
				static function ( array $a, array $b ): int {
					return strcasecmp( (string) $a['label'], (string) $b['label'] );
				}
			);
		}

		return $result;
	}
}
