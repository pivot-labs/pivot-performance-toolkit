<?php
/**
 * The only place this plugin reads $_SERVER and $_COOKIE.
 *
 * Every value is unslashed (WordPress adds slashes to superglobals) and
 * sanitized before it is returned, so callers never touch raw request input.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RequestInput {

	/**
	 * A $_SERVER value as plain sanitized text ('' when absent).
	 */
	public static function server( string $key ): string {
		return isset( $_SERVER[ $key ] ) && is_scalar( $_SERVER[ $key ] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) )
			: '';
	}

	/**
	 * A $_SERVER value for use in a page-cache key (host, request URI).
	 *
	 * sanitize_text_field() is wrong for these: it removes percent-encoded octets, so
	 * /caf%C3%A9 and /caf%C3%A8 would collapse into one key and one URL could be served
	 * the other's cached page. Instead this keeps every printable ASCII character and
	 * drops the rest (control characters, spaces, raw non-ASCII bytes), which is also
	 * what makes the value safe to hash and compare.
	 *
	 * The page-cache drop-in (includes/Cache/advanced-cache.php) runs before WordPress
	 * has loaded formatting.php and cannot call wp_unslash(); it applies this same
	 * filter inline. Both sides MUST produce the same key or the cache never hits;
	 * tests/Unit/Cache/AdvancedCacheDropinTest.php verifies that.
	 */
	public static function keyPart( string $key, string $fallback ): string {
		if ( ! isset( $_SERVER[ $key ] ) || ! is_scalar( $_SERVER[ $key ] ) ) {
			return $fallback;
		}

		return self::printableAscii( wp_unslash( (string) $_SERVER[ $key ] ) );
	}

	public static function printableAscii( string $value ): string {
		return preg_replace( '/[^\x21-\x7E]/', '', $value ) ?? '';
	}

	public static function isGetRequest(): bool {
		return 'GET' === strtoupper( self::server( 'REQUEST_METHOD' ) );
	}

	public static function isHttps(): bool {
		$https = strtolower( self::server( 'HTTPS' ) );

		return '' !== $https && 'off' !== $https;
	}

	/**
	 * Names of the cookies sent with the request, sanitized.
	 *
	 * @return array<int, string>
	 */
	public static function cookieNames(): array {
		if ( ! isset( $_COOKIE ) || ! is_array( $_COOKIE ) ) {
			return array();
		}

		$names = array();

		foreach ( array_keys( $_COOKIE ) as $name ) {
			$name = sanitize_text_field( (string) $name );

			if ( '' !== $name ) {
				$names[] = $name;
			}
		}

		return $names;
	}

	/**
	 * A cookie value as plain sanitized text ('' when absent).
	 */
	public static function cookie( string $name ): string {
		return isset( $_COOKIE[ $name ] ) && is_scalar( $_COOKIE[ $name ] )
			? sanitize_text_field( wp_unslash( (string) $_COOKIE[ $name ] ) )
			: '';
	}
}
