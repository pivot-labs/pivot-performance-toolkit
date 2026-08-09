<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Integrations;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Integrations\CloudflareIntegration;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class CloudflareIntegrationTest extends TestCase {

	/** @var array<string, mixed> */
	private array $option_overrides = array();

	protected function setUp(): void {
		parent::setUp();

		$this->option_overrides = array();

		Functions\when( 'get_option' )->alias(
			function () {
				return $this->option_overrides;
			}
		);
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);
	}

	private function configuredIntegration( bool $auto_purge = true ): CloudflareIntegration {
		$this->option_overrides = array(
			'cdn_provider'          => 'cloudflare',
			'cloudflare_api_token'  => 'test-token',
			'cloudflare_zone_id'    => 'test-zone',
			'cloudflare_auto_purge' => $auto_purge,
		);

		return new CloudflareIntegration( new Settings() );
	}

	/**
	 * Regression test: previously this only checked wp_is_post_revision()/
	 * wp_is_post_autosave() directly; now it goes through the same
	 * CachePurgeEvents::isRealPostChange() both classes share. Not stubbing
	 * wp_remote_request at all here — if the guard failed to short-circuit,
	 * purgeCache() would try to call it and Brain\Monkey would error on the
	 * undefined expectation, failing the test.
	 */
	public function test_handle_content_change_skips_autosaves(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( true );

		$this->configuredIntegration()->handleContentChange( 123 );

		$this->addToAssertionCount( 1 );
	}

	public function test_handle_content_change_skips_when_auto_purge_disabled(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );

		$this->configuredIntegration( false )->handleContentChange( 123 );

		$this->addToAssertionCount( 1 );
	}

	public function test_handle_generic_cache_invalidation_skips_when_auto_purge_disabled(): void {
		$this->configuredIntegration( false )->handleGenericCacheInvalidation();

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Confirms the positive path actually reaches the Cloudflare API call —
	 * without this, the two guard-only tests above could pass for the wrong
	 * reason (e.g. a typo that always skips, autosave or not).
	 */
	public function test_handle_generic_cache_invalidation_purges_when_configured(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '{"success":true}' );
		Functions\when( '__' )->returnArg();

		$called_url = null;

		Functions\when( 'wp_remote_request' )->alias(
			function ( string $url, array $args ) use ( &$called_url ) {
				$called_url = $url;

				return array();
			}
		);

		$this->configuredIntegration()->handleGenericCacheInvalidation();

		self::assertStringContainsString( '/purge_cache', (string) $called_url );
	}
}
