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

	/**
	 * @return array{version:string,is_http11:bool,is_modern:bool,source:string}
	 */
	public static function detect(): array {
		$from_server = self::detectFromServerGlobals();
		if ( '' !== $from_server['version'] ) {
			return self::buildResult( $from_server['version'], $from_server['source'] );
		}

		$from_loopback = self::detectFromLoopbackRequest();
		if ( '' !== $from_loopback['version'] ) {
			return self::buildResult( $from_loopback['version'], $from_loopback['source'] );
		}

		return self::buildResult( 'unknown', 'none' );
	}

	/**
	 * @return array{version:string,source:string}
	 */
	private static function detectFromServerGlobals(): array {
		if ( ! empty( $_SERVER['HTTP3'] ) && 'off' !== strtolower( (string) $_SERVER['HTTP3'] ) ) {
			return array(
				'version' => '3',
				'source'  => 'server-http3',
			);
		}

		if ( ! empty( $_SERVER['HTTP2'] ) && 'off' !== strtolower( (string) $_SERVER['HTTP2'] ) ) {
			return array(
				'version' => '2',
				'source'  => 'server-http2',
			);
		}

		foreach ( array( 'SERVER_PROTOCOL', 'REQUEST_PROTOCOL' ) as $key ) {
			$raw = isset( $_SERVER[ $key ] ) ? (string) $_SERVER[ $key ] : '';
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
