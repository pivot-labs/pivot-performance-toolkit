<?php
/**
 * Anonymous request from the site to one of its own pages.
 *
 * Used to fill the page cache (cache preload, and the performance test's
 * warm-up). Every URL passed in must already be on this site's own host; the
 * callers check that before they get here.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LoopbackRequest {

	/**
	 * @return array{ok: bool, error: string}
	 */
	public static function fetch( string $url ): array {
		$timeout = (float) apply_filters( 'pivot_performance_toolkit_preload_request_timeout', 8 );
		$args    = array(
			'timeout'     => max( 1.0, $timeout ),
			'redirection' => 3,
			'headers'     => array(
				'X-Pivot-Performance-Toolkit-Preload' => '1',
			),
			'user-agent'  => 'Pivot Performance Toolkit Cache Preload',
			'sslverify'   => ! self::isLocalOrNonProductionSite( $url ),
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			// Local HTTPS setups commonly fail on loopback cert verification.
			// $fallback_url is the same URL on the same host (only the scheme changes), and
			// callers only pass URLs on this site's host, so this never contacts a third
			// party. Documented in readme.txt, "External services".
			if ( str_starts_with( $url, 'https://' ) ) {
				$fallback_url      = 'http://' . substr( $url, 8 );
				$fallback_response = wp_remote_get( $fallback_url, $args );

				if ( ! is_wp_error( $fallback_response ) ) {
					$fallback_code = (int) wp_remote_retrieve_response_code( $fallback_response );

					if ( $fallback_code >= 200 && $fallback_code < 400 ) {
						return array(
							'ok'    => true,
							'error' => '',
						);
					}

					return array(
						'ok'    => false,
						/* translators: 1: HTTP status code, 2: fallback URL that was requested */
						'error' => sprintf( __( 'HTTP fallback returned %1$d for %2$s', 'pivot-performance-toolkit' ), $fallback_code, $fallback_url ),
					);
				}
			}

			return array(
				'ok'    => false,
				'error' => sprintf( '%s (%s)', (string) $error_message, $url ),
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code >= 200 && $status_code < 400 ) {
			return array(
				'ok'    => true,
				'error' => '',
			);
		}

		return array(
			'ok'    => false,
			/* translators: 1: HTTP status code, 2: URL that was requested */
			'error' => sprintf( __( 'HTTP %1$d for %2$s', 'pivot-performance-toolkit' ), $status_code, $url ),
		);
	}

	private static function isLocalOrNonProductionSite( string $url ): bool {
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		if ( 'production' !== $environment ) {
			return true;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true )
			|| str_ends_with( $host, '.test' )
			|| str_ends_with( $host, '.local' );
	}
}
