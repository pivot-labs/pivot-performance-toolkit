<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Database;

use Brain\Monkey\Functions;
use Mockery;
use Mockery\MockInterface;
use PivotPerformanceToolkit\Database\DatabaseOptimizer;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class DatabaseOptimizerTest extends TestCase {

	private DatabaseOptimizer $optimizer;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_sql' )->returnArg();

		$this->optimizer = new DatabaseOptimizer();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );

		parent::tearDown();
	}

	/**
	 * $wpdb is accessed via `global $wpdb` inside every method under test,
	 * so the mock has to live in $GLOBALS rather than be passed as a
	 * constructor/method argument — this matches how WordPress itself makes
	 * $wpdb available everywhere.
	 */
	private function mockWpdb(): MockInterface {
		$wpdb           = Mockery::mock();
		$wpdb->posts    = 'wp_posts';
		$wpdb->comments = 'wp_comments';
		$wpdb->options  = 'wp_options';

		// A close-enough real implementation (not a canned return) so the
		// SQL/placeholder values assembled by callers can still be inspected
		// by tests that need to — e.g. confirming deleteExpiredTransients()
		// targets the exact option names it means to delete.
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing(
			static function ( string $query, ...$args ): string {
				return vsprintf( str_replace( array( '%s', '%d' ), array( "'%s'", '%d' ), $query ), $args );
			}
		)->byDefault();

		$GLOBALS['wpdb'] = $wpdb;

		return $wpdb;
	}

	// ── Count methods ───────────────────────────────────────────────────

	public function test_count_revisions_casts_result_to_int(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( '7' );

		self::assertSame( 7, $this->optimizer->countRevisions() );
	}

	public function test_count_expired_transients_queries_against_timeout_rows(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_var' )
			->once()
			->with( Mockery::pattern( '/_transient_timeout_/' ) )
			->andReturn( '3' );

		self::assertSame( 3, $this->optimizer->countExpiredTransients() );
	}

	// ── deleteAutoDrafts (direct query, no per-row loop) ───────────────

	public function test_delete_auto_drafts_returns_affected_row_count(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'query' )->once()->andReturn( 4 );

		self::assertSame( 4, $this->optimizer->deleteAutoDrafts() );
	}

	public function test_delete_auto_drafts_returns_zero_on_query_failure(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'query' )->once()->andReturn( false );

		self::assertSame( 0, $this->optimizer->deleteAutoDrafts() );
	}

	// ── Per-row delete loops: count only reflects actual successes ────

	public function test_delete_revisions_counts_every_row_regardless_of_return_value(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_col' )->once()->andReturn( array( '10', '11', '12' ) );

		// wp_delete_post_revision()'s return value is intentionally not
		// checked by the source (unlike the comment/post loops below) —
		// verify all three still get attempted and counted.
		Functions\expect( 'wp_delete_post_revision' )->times( 3 );

		self::assertSame( 3, $this->optimizer->deleteRevisions() );
	}

	public function test_delete_trashed_posts_only_counts_successful_deletions(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_col' )->once()->andReturn( array( '1', '2', '3' ) );

		Functions\expect( 'wp_delete_post' )->times( 3 )->andReturnUsing(
			static fn( int $id, bool $force_delete ): bool => 2 !== $id
		);

		self::assertSame( 2, $this->optimizer->deleteTrashedPosts() );
	}

	public function test_delete_spam_comments_only_counts_successful_deletions(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_col' )->once()->andReturn( array( '5', '6' ) );

		Functions\when( 'wp_delete_comment' )->justReturn( false );

		self::assertSame( 0, $this->optimizer->deleteSpamComments() );
	}

	// ── deleteExpiredTransients: the actual bug fixed this session ────

	/**
	 * Regression test for the bug fixed this session: delete_transient() is
	 * cache-aware and, with an external/persistent object cache active
	 * (this plugin's own object-cache.php drop-in), calls wp_cache_delete()
	 * instead of touching wp_options — meaning these stale DB rows (which
	 * were never in the object cache to begin with) never actually get
	 * deleted, even though the call reports success. This must instead
	 * issue a direct DELETE against wp_options for exactly the two rows
	 * (value + timeout) each expired key owns, and also clear any
	 * object-cache copy so a stale cached value can't outlive its DB row.
	 */
	public function test_delete_expired_transients_deletes_option_rows_directly(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_col' )
			->once()
			->with( Mockery::pattern( '/_transient_timeout_/' ) )
			->andReturn( array( 'my_key' ) );

		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM wp_options WHERE option_name IN ('_transient_my_key', '_transient_timeout_my_key')" )
			->andReturn( 2 );

		Functions\expect( 'wp_cache_delete' )->once()->with( 'my_key', 'transient' );
		Functions\expect( 'wp_cache_delete' )->once()->with( 'my_key', 'transient_timeout' );

		self::assertSame( 1, $this->optimizer->deleteExpiredTransients() );
	}

	public function test_delete_expired_transients_does_not_count_a_row_that_failed_to_delete(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_col' )->once()->andReturn( array( 'gone_already' ) );
		$wpdb->shouldReceive( 'query' )->once()->andReturn( 0 );

		Functions\when( 'wp_cache_delete' )->justReturn( true );

		self::assertSame( 0, $this->optimizer->deleteExpiredTransients() );
	}

	// ── optimizeTables ──────────────────────────────────────────────────

	public function test_optimize_tables_runs_optimize_for_every_table_and_escapes_names(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_col' )->once()->with( 'SHOW TABLES' )->andReturn( array( 'wp_posts', 'wp_options' ) );

		$wpdb->shouldReceive( 'query' )->once()->with( 'OPTIMIZE TABLE `wp_posts`' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'OPTIMIZE TABLE `wp_options`' );

		self::assertSame( 2, $this->optimizer->optimizeTables() );
	}

	// ── getDatabaseStats ────────────────────────────────────────────────

	public function test_get_database_stats_casts_row_values_to_int(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn(
			(object) array(
				'db_size'         => '1048576',
				'myisam_overhead' => '2048',
			)
		);

		self::assertSame(
			array(
				'size_bytes'            => 1048576,
				'myisam_overhead_bytes' => 2048,
			),
			$this->optimizer->getDatabaseStats()
		);
	}

	public function test_get_database_stats_defaults_to_zero_when_row_is_null(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( null );

		self::assertSame(
			array(
				'size_bytes'            => 0,
				'myisam_overhead_bytes' => 0,
			),
			$this->optimizer->getDatabaseStats()
		);
	}

	// ── getTableStats: sort column allowlist ───────────────────────────

	/**
	 * $order_by is interpolated directly into the SQL (ORDER BY can't use
	 * $wpdb->prepare() placeholders), so it must only ever come from the
	 * hardcoded column allowlist — never $sort_by itself — regardless of
	 * what string is passed in.
	 */
	public function test_get_table_stats_rejects_unknown_sort_column(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_results' )
			->once()
			->with( Mockery::pattern( '/ORDER BY tbl_size DESC/' ) )
			->andReturn( array() );

		self::assertSame( array(), $this->optimizer->getTableStats( '1; DROP TABLE wp_users; --', 'desc' ) );
	}

	public function test_get_table_stats_maps_known_sort_column_and_ascending_direction(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_results' )
			->once()
			->with( Mockery::pattern( '/ORDER BY tbl_name ASC/' ) )
			->andReturn( array() );

		self::assertSame( array(), $this->optimizer->getTableStats( 'name', 'asc' ) );
	}

	public function test_get_table_stats_maps_result_rows(): void {
		$wpdb = $this->mockWpdb();
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn(
			array(
				(object) array(
					'tbl_name'     => 'wp_posts',
					'tbl_engine'   => 'InnoDB',
					'tbl_rows'     => '120',
					'tbl_size'     => '4096',
					'tbl_overhead' => '0',
				),
			)
		);

		self::assertSame(
			array(
				array(
					'name'           => 'wp_posts',
					'engine'         => 'InnoDB',
					'rows'           => 120,
					'size_bytes'     => 4096,
					'overhead_bytes' => 0,
				),
			),
			$this->optimizer->getTableStats()
		);
	}

	// ── formatBytes ──────────────────────────────────────────────────────

	public function test_format_bytes_below_one_kilobyte(): void {
		self::assertSame( '512 B', DatabaseOptimizer::formatBytes( 512 ) );
	}

	public function test_format_bytes_in_kilobytes(): void {
		self::assertSame( '2.00 KB', DatabaseOptimizer::formatBytes( 2048 ) );
	}

	public function test_format_bytes_in_megabytes(): void {
		self::assertSame( '5.00 MB', DatabaseOptimizer::formatBytes( 5 * 1048576 ) );
	}
}
