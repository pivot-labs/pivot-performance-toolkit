<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Cache;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Cache\PageCache;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class PageCacheTest extends TestCase {

	private string $cache_dir;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);
		Functions\when( 'wp_delete_file' )->alias(
			static fn( string $file ): bool => unlink( $file )
		);

		$this->cache_dir = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit';

		if ( ! is_dir( $this->cache_dir ) ) {
			mkdir( $this->cache_dir, 0777, true );
		}

		file_put_contents( $this->cache_dir . '/somepage.html', '<html>cached</html>' );
	}

	protected function tearDown(): void {
		$this->removeDir( WP_CONTENT_DIR );

		parent::tearDown();
	}

	private function removeDir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( glob( $dir . '/*' ) ?: array() as $item ) {
			is_dir( $item ) ? $this->removeDir( $item ) : unlink( $item );
		}

		rmdir( $dir );
	}

	/**
	 * Regression test for the bug fixed this session: save_post (and the
	 * other POST_HOOKS) fire on every autosave — without this guard, the
	 * entire cache was being wiped roughly every 60 seconds while anyone
	 * had a post open in the editor.
	 */
	public function test_purge_all_for_post_change_skips_autosaves(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( true );

		$page_cache = new PageCache( new Settings() );
		$page_cache->purgeAllForPostChange( 123 );

		self::assertFileExists( $this->cache_dir . '/somepage.html' );
	}

	public function test_purge_all_for_post_change_skips_revisions(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( true );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );

		$page_cache = new PageCache( new Settings() );
		$page_cache->purgeAllForPostChange( 123 );

		self::assertFileExists( $this->cache_dir . '/somepage.html' );
	}

	public function test_purge_all_for_post_change_purges_on_a_real_edit(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );

		$page_cache = new PageCache( new Settings() );
		$page_cache->purgeAllForPostChange( 123 );

		self::assertFileDoesNotExist( $this->cache_dir . '/somepage.html' );
	}
}
