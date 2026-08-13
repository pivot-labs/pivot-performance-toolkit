<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Admin\PerformanceTest;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class PerformanceTestTest extends TestCase {

	/** @var array<string, mixed> */
	private array $options = array();

	/** @var array<string, mixed> */
	private array $transients = array();

	protected function setUp(): void {
		parent::setUp();

		$this->options    = array();
		$this->transients = array();

		Functions\when( 'get_option' )->alias(
			function ( string $key, $default = false ) {
				return $this->options[ $key ] ?? $default;
			}
		);
		Functions\when( 'update_option' )->alias(
			function ( string $key, $value ) {
				$this->options[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'get_transient' )->alias(
			function ( string $key ) {
				return $this->transients[ $key ] ?? false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			function ( string $key, $value ) {
				$this->transients[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'wp_date' )->justReturn( '2026-01-01 00:00:00 UTC' );
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'remove_query_arg' )->alias( static fn( array $args, string $url ): string => $url );
		Functions\when( 'rest_ensure_response' )->returnArg();
	}

	private function performanceTest(): PerformanceTest {
		return new PerformanceTest( new Settings() );
	}

	/**
	 * The token is the entire access-control boundary for the deliberately
	 * public /performance-tests/collect endpoint (permission_callback is
	 * __return_true — see the comment on that registration). No matching
	 * transient means the request is rejected before touching any data.
	 */
	public function test_collect_rejects_missing_token(): void {
		$request = new \WP_REST_Request( array() );

		$result = $this->performanceTest()->restCollectTest( $request );

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertSame( 'pivot_performance_toolkit_missing_token', $result->get_error_code() );
	}

	public function test_collect_rejects_unknown_or_expired_token(): void {
		$request = new \WP_REST_Request( array( 'token' => 'does-not-exist' ) );

		$result = $this->performanceTest()->restCollectTest( $request );

		self::assertInstanceOf( \WP_Error::class, $result );
		self::assertSame( 'pivot_performance_toolkit_unknown_token', $result->get_error_code() );
	}

	/**
	 * A valid, currently-pending token (as restStartTest() would have
	 * issued) is accepted, and the stored result only ever contains the
	 * allowlisted numeric metric keys — never arbitrary attacker-supplied
	 * data, regardless of what extra keys the request includes.
	 */
	public function test_collect_accepts_valid_token_and_sanitizes_metrics(): void {
		$this->transients['pivot_performance_toolkit_test_valid-token'] = array(
			'status'     => 'pending',
			'created_at' => '2026-01-01T00:00:00+00:00',
			'target_url' => 'https://example.com/',
		);

		$request = new \WP_REST_Request(
			array(
				'token'   => 'valid-token',
				'pageUrl' => 'https://example.com/?utm_source=x',
				'metrics' => array(
					'lcp_ms'                => '1234.5678',
					'total_js_count'        => '7',
					'not_an_allowlisted_key' => '<script>alert(1)</script>',
				),
			)
		);

		$this->performanceTest()->restCollectTest( $request );

		$stored = $this->options['pivot_performance_toolkit_last_performance_result'];

		self::assertSame( 1234.57, $stored['metrics']['lcp_ms'] );
		self::assertSame( 7, $stored['metrics']['total_js_count'] );
		self::assertArrayNotHasKey( 'not_an_allowlisted_key', $stored['metrics'] );
	}

	/**
	 * Negative/garbage numeric input is clamped, not passed through —
	 * confirms sanitizeMetrics() bounds every value rather than trusting
	 * whatever the (unauthenticated) request sends.
	 */
	public function test_collect_clamps_negative_metric_values_to_zero(): void {
		$this->transients['pivot_performance_toolkit_test_valid-token'] = array(
			'status'     => 'pending',
			'created_at' => '2026-01-01T00:00:00+00:00',
			'target_url' => 'https://example.com/',
		);

		$request = new \WP_REST_Request(
			array(
				'token'   => 'valid-token',
				'metrics' => array(
					'ttfb_ms'              => '-500',
					'total_resource_count' => '-3',
				),
			)
		);

		$this->performanceTest()->restCollectTest( $request );

		$stored = $this->options['pivot_performance_toolkit_last_performance_result'];

		self::assertSame( 0.0, $stored['metrics']['ttfb_ms'] );
		self::assertSame( 0, $stored['metrics']['total_resource_count'] );
	}
}
