<?php
/**
 * Coverage for TableSort: nonce-gated table-sort state and the sort links it builds.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Admin\TableSort;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class TableSortTest extends TestCase {

	private const ACTION = 'pivot_performance_toolkit_table_sort';

	protected function setUp(): void {
		parent::setUp();

		$_GET = array();

		Functions\stubs(
			array(
				'wp_unslash'          => null,
				'sanitize_text_field' => null,
				'sanitize_key'        => static fn( $value ) => strtolower( (string) preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) ),
			)
		);
	}

	protected function tearDown(): void {
		$_GET = array();

		parent::tearDown();
	}

	private function allowNonce( bool $valid ): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->alias(
			static fn( string $nonce, string $action ) => $valid && 'good-nonce' === $nonce && self::ACTION === $action ? 1 : false
		);
	}

	public function test_defaults_when_no_sort_is_requested(): void {
		$this->allowNonce( true );

		self::assertSame( array( 'sort_by' => 'size', 'sort_dir' => 'desc' ), TableSort::fromRequest() );
	}

	public function test_honours_a_valid_request_with_a_valid_nonce(): void {
		$this->allowNonce( true );
		$_GET = array( '_wpnonce' => 'good-nonce', 'sort' => 'name', 'sort_dir' => 'asc' );

		self::assertSame( array( 'sort_by' => 'name', 'sort_dir' => 'asc' ), TableSort::fromRequest() );
	}

	public function test_ignores_the_sort_when_the_nonce_is_missing(): void {
		$this->allowNonce( true );
		$_GET = array( 'sort' => 'name', 'sort_dir' => 'asc' );

		self::assertSame( array( 'sort_by' => 'size', 'sort_dir' => 'desc' ), TableSort::fromRequest() );
	}

	public function test_ignores_the_sort_when_the_nonce_is_invalid(): void {
		$this->allowNonce( true );
		$_GET = array( '_wpnonce' => 'forged', 'sort' => 'name', 'sort_dir' => 'asc' );

		self::assertSame( array( 'sort_by' => 'size', 'sort_dir' => 'desc' ), TableSort::fromRequest() );
	}

	public function test_ignores_the_sort_without_the_manage_options_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		$_GET = array( '_wpnonce' => 'good-nonce', 'sort' => 'name', 'sort_dir' => 'asc' );

		self::assertSame( array( 'sort_by' => 'size', 'sort_dir' => 'desc' ), TableSort::fromRequest() );
	}

	public function test_falls_back_per_field_for_values_outside_the_allowlist(): void {
		$this->allowNonce( true );
		$_GET = array( '_wpnonce' => 'good-nonce', 'sort' => '1; DROP TABLE wp_users; --', 'sort_dir' => 'sideways' );

		self::assertSame( array( 'sort_by' => 'size', 'sort_dir' => 'desc' ), TableSort::fromRequest() );
	}

	public function test_next_size_url_toggles_direction_and_carries_a_nonce(): void {
		Functions\when( 'wp_create_nonce' )->alias( static fn( string $action ) => self::ACTION === $action ? 'good-nonce' : 'wrong' );
		Functions\when( 'add_query_arg' )->alias(
			static fn( array $args, string $url ) => $url . '?' . http_build_query( $args )
		);

		self::assertSame(
			'https://example.com/wp-admin/admin.php?sort=size&sort_dir=asc&_wpnonce=good-nonce',
			TableSort::nextSizeUrl( 'https://example.com/wp-admin/admin.php', 'size', 'desc' )
		);
		self::assertSame(
			'https://example.com/wp-admin/admin.php?sort=size&sort_dir=desc&_wpnonce=good-nonce',
			TableSort::nextSizeUrl( 'https://example.com/wp-admin/admin.php', 'size', 'asc' )
		);
	}
}
