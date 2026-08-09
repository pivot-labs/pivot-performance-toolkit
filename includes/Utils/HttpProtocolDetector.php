<?php
/**
 * Detects HTTP protocol version for current/admin context.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HttpProtocolDetector {

	private const LOOPBACK_TRANSIENT = 'pivot_performance_toolkit_http_protocol';

	/**
	 * @return array{version:string,is_http11:bool,is_modern:bool,source:string}
	 */
	public static function detect(): array {
		$cached = get_transient( self::LOOPBACK_TRANSIENT );
		if ( self::isValidResult( $cached ) ) {
			return $cached;
		}

		$from_server = self::detectFromServerGlobals();
		if ( '' !== $from_server['version'] ) {
			return self::buildResult( $from_server['version'], $from_server['source'] );
		}

		$from_loopback = self::detectFromLoopbackRequest();
		if ( '' !== $from_loopback['version'] ) {
			$result = self::buildResult( $from_loopback['version'], $from_loopback['source'] );

			// Only the loopback result is cached: it's the expensive path (a real HTTP
			// request). Failures are intentionally left uncached so the next page load
			// retries instead of being stuck with a negative result for an hour.
			set_transient( self::LOOPBACK_TRANSIENT, $result, HOUR_IN_SECONDS );

			return $result;
		}

		return self::buildResult( 'unknown', 'none' );
	}

	/**
	 * @param mixed $value
	 * @phpstan-assert-if-true array{version:string,is_http11:bool,is_modern:bool,source:string} $value
	 */
	private static function isValidResult( $value ): bool {
		return is_array( $value )
			&& isset( $value['version'], $value['is_http11'], $value['is_modern'], $value['source'] )
			&& is_string( $value['version'] )
			&& is_bool( $value['is_http11'] )
			&& is_bool( $value['is_modern'] )
			&& is_string( $value['source'] );
	}

	/**
	 * @return array{version:string,source:string}
	 */
	private static function detectFromServerGlobals(): array {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- these are only used in !empty() presence checks; the returned 'version'/'source' values below are always hardcoded literals, never the raw $_SERVER content.
		// Cloudflare (and similar reverse proxies) frequently connect to the
		// origin over HTTP/1.1 even while serving HTTP/2 or HTTP/3 to every
		// visitor — origin-side signals (SERVER_PROTOCOL, a loopback request)
		// only see that origin-facing hop, never the actual visitor-facing
		// protocol. CF-Ray/CF-Connecting-IP are set by Cloudflare on every
		// proxied request regardless of plan, and Cloudflare has served
		// HTTP/2 by default to all proxied zones for years, so their
		// presence alone is a more reliable "at least HTTP/2" signal than
		// anything origin-side can provide — check it first.
		if ( ! empty( $_SERVER['HTTP_CF_RAY'] ) || ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return array(
				'version' => '2',
				'source'  => 'cloudflare-proxy',
			);
		}

		if ( ! empty( $_SERVER['HTTP3'] ) && 'off' !== strtolower( (string) wp_unslash( $_SERVER['HTTP3'] ) ) ) {
			return array(
				'version' => '3',
				'source'  => 'server-http3',
			);
		}

		if ( ! empty( $_SERVER['HTTP2'] ) && 'off' !== strtolower( (string) wp_unslash( $_SERVER['HTTP2'] ) ) ) {
			return array(
				'version' => '2',
				'source'  => 'server-http2',
			);
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( array( 'SERVER_PROTOCOL', 'REQUEST_PROTOCOL' ) as $key ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only used with preg_match() below; the returned version is regex-constrained to [0-9.]+, so raw content never reaches the caller.
			$raw = isset( $_SERVER[ $key ] ) ? (string) wp_unslash( $_SERVER[ $key ] ) : '';
			if ( '' === $raw ) {
				continue;
			}

			if ( preg_match( '/HTTP\/([0-9.]+)/i', $raw, $matches ) ) {
				return array(
					'version' => self::normalizeVersion( $matches[1] ),
					'source'  => 'server-' . strtolower( $key ),
				);
			}
		}

		return array(
			'version' => '',
			'source'  => '',
		);
	}

	/**
	 * @return array{version:string,source:string}
	 */
	private static function detectFromLoopbackRequest(): array {
		if ( ! function_exists( 'wp_remote_head' ) || ! function_exists( 'home_url' ) ) {
			return array(
				'version' => '',
				'source'  => '',
			);
		}

		$response = wp_remote_head(
			home_url( '/' ),
			array(
				'timeout'     => 3,
				'redirection' => 0,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'version' => '',
				'source'  => '',
			);
		}

		$http_response = isset( $response['http_response'] ) ? $response['http_response'] : null;
		if ( ! is_object( $http_response ) || ! method_exists( $http_response, 'get_response_object' ) ) {
			return array(
				'version' => '',
				'source'  => '',
			);
		}

		$response_object = $http_response->get_response_object();
		if ( ! is_object( $response_object ) || ! isset( $response_object->protocol_version ) ) {
			return array(
				'version' => '',
				'source'  => '',
			);
		}

		$version = self::normalizeVersion( (string) $response_object->protocol_version );
		if ( '' === $version ) {
			return array(
				'version' => '',
				'source'  => '',
			);
		}

		return array(
			'version' => $version,
			'source'  => 'loopback',
		);
	}

	private static function normalizeVersion( string $raw ): string {
		$value = trim( $raw );
		if ( '' === $value ) {
			return '';
		}

		if ( '1' === $value ) {
			return '1.1';
		}

		return $value;
	}

	/**
	 * @return array{version:string,is_http11:bool,is_modern:bool,source:string}
	 */
	private static function buildResult( string $version, string $source ): array {
		$is_http11 = in_array( $version, array( '1.1', '1.0' ), true );
		$is_modern = in_array( $version, array( '2', '2.0', '3', '3.0' ), true );

		return array(
			'version'   => $version,
			'is_http11' => $is_http11,
			'is_modern' => $is_modern,
			'source'    => $source,
		);
	}
}
