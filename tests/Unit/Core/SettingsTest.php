<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Core;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class SettingsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults = array() ): array {
				return array_merge( $defaults, (array) $args );
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			static fn( string $value ): string => trim( strip_tags( $value ) )
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			static fn( string $value ): string => trim( str_replace( "\r", '', strip_tags( $value ) ) )
		);
		Functions\when( 'sanitize_key' )->alias(
			static fn( string $value ): string => (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) )
		);
	}

	/**
	 * The bug this guards against: a caller (e.g. an AJAX handler saving one
	 * quick-toggle) submits a $raw array containing only the key it means to
	 * change. Every other key must come from the freshly-read stored option
	 * ($base), not from hardcoded defaults — otherwise a partial save
	 * silently reverts every other setting to its default.
	 */
	public function test_sanitize_falls_back_to_stored_value_for_keys_absent_from_raw(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'cdn_provider'         => 'cloudflare',
				'cloudflare_api_token' => 'stored-token',
				'cloudflare_zone_id'   => 'stored-zone',
				'cache_ttl'            => 900,
			)
		);

		$settings = new Settings();
		$result   = $settings->sanitize( array( 'defer_scripts' => '1' ) );

		self::assertSame( 'cloudflare', $result['cdn_provider'] );
		self::assertSame( 'stored-token', $result['cloudflare_api_token'] );
		self::assertSame( 'stored-zone', $result['cloudflare_zone_id'] );
		self::assertSame( 900, $result['cache_ttl'] );
		self::assertTrue( $result['defer_scripts'] );
	}

	public function test_sanitize_uses_submitted_value_when_present_in_raw(): void {
		Functions\when( 'get_option' )->justReturn(
			array( 'cdn_provider' => '' )
		);

		$settings = new Settings();
		$result   = $settings->sanitize(
			array(
				'cdn_provider'         => 'cloudflare',
				'cloudflare_api_token' => 'new-token',
				'cloudflare_zone_id'   => 'new-zone',
			)
		);

		self::assertSame( 'cloudflare', $result['cdn_provider'] );
		self::assertSame( 'new-token', $result['cloudflare_api_token'] );
		self::assertSame( 'new-zone', $result['cloudflare_zone_id'] );
	}

	/**
	 * cdn_provider is an allowlist, not free text — anything other than the
	 * exact literal "cloudflare" must be normalized to "" rather than passed
	 * through, since downstream code (CloudflareIntegration::isConfigured())
	 * gates real API calls on this value.
	 */
	public function test_sanitize_rejects_unknown_cdn_provider(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$settings = new Settings();

		self::assertSame( '', $settings->sanitize( array( 'cdn_provider' => 'fastly' ) )['cdn_provider'] );
		self::assertSame( '', $settings->sanitize( array( 'cdn_provider' => '' ) )['cdn_provider'] );
		self::assertSame( 'cloudflare', $settings->sanitize( array( 'cdn_provider' => 'cloudflare' ) )['cdn_provider'] );
	}

	/**
	 * Boolean settings must distinguish "key absent from $raw" (keep the
	 * stored value) from "key present but empty/unchecked" (explicitly set
	 * false) — an unchecked HTML checkbox simply omits its name from the
	 * POST body unless a hidden fallback field is also present.
	 */
	public function test_sanitize_boolean_keys_use_array_key_exists_not_isset(): void {
		Functions\when( 'get_option' )->justReturn( array( 'defer_scripts' => true ) );

		$settings = new Settings();

		self::assertTrue(
			$settings->sanitize( array() )['defer_scripts'],
			'Key absent from $raw must keep the stored value.'
		);

		self::assertFalse(
			$settings->sanitize( array( 'defer_scripts' => '0' ) )['defer_scripts'],
			'Key present but falsy must be explicitly set to false, not fall back to stored.'
		);
	}

	public function test_sanitize_enforces_minimum_cache_ttl(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$settings = new Settings();

		self::assertSame( 60, $settings->sanitize( array( 'cache_ttl' => 10 ) )['cache_ttl'] );
		self::assertSame( 120, $settings->sanitize( array( 'cache_ttl' => 120 ) )['cache_ttl'] );
	}

	public function test_sanitize_enforces_minimum_max_cache_size(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$settings = new Settings();

		self::assertSame( 1, $settings->sanitize( array( 'max_cache_size_mb' => -5 ) )['max_cache_size_mb'] );
	}
}
