<?php
/**
 * Resolves an enqueued asset URL to a readable local file path.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LocalAssetResolver {

	/**
	 * Resolves $url to a local, readable file path if it is a same-origin
	 * asset with the given extension, or null if it's external, missing,
	 * unreadable, or would resolve outside ABSPATH.
	 */
	public static function resolve( string $url, string $extension ): ?string {
		$path = (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?? '' );

		if ( '' === $path || ! str_ends_with( strtolower( $path ), '.' . strtolower( $extension ) ) ) {
			return null;
		}

		$url_host  = (string) ( wp_parse_url( $url, PHP_URL_HOST ) ?? '' );
		$home_host = (string) ( wp_parse_url( home_url(), PHP_URL_HOST ) ?? '' );

		if ( '' !== $url_host && 0 !== strcasecmp( $url_host, $home_host ) ) {
			return null;
		}

		$absolute = ABSPATH . ltrim( $path, '/' );
		$real     = realpath( $absolute );
		$root     = realpath( ABSPATH );

		if ( false === $real || false === $root || ! str_starts_with( $real, $root ) ) {
			return null;
		}

		if ( ! is_file( $real ) || ! is_readable( $real ) ) {
			return null;
		}

		return $real;
	}
}
