<?php
/**
 * Optimization performance test admin page.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class PerformancePage extends BladeAdminPage {

	public function slug(): string {
		return 'performance-toolkit-performance';
	}

	public function menuTitle(): string {
		return __( 'Performance', 'performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Performance Toolkit Performance', 'performance-toolkit' );
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
		$last_result = get_option( 'ptk_last_performance_result', array() );
		$last_score  = PerformanceTest::calculateOverallScoreFromResult( is_array( $last_result ) ? $last_result : array() );

		return array(
			'options'    => self::getTestableContentOptions(),
			'last_score' => $last_score,
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

				$result[ $bucket ][] = array(
					'label' => is_string( $title ) && '' !== trim( $title ) ? $title : sprintf( __( 'Untitled #%d', 'performance-toolkit' ), (int) $post_id ),
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

