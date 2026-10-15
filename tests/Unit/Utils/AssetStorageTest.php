<?php
/**
 * Coverage for AssetStorage: generated assets live in the plugin's folder in
 * the uploads directory, resolved through wp_upload_dir().
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Utils;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Tests\Unit\TestCase;
use PivotPerformanceToolkit\Utils\AssetStorage;

final class AssetStorageTest extends TestCase {

	private string $base;

	protected function setUp(): void {
		parent::setUp();

		$this->base = sys_get_temp_dir() . '/ptk-asset-storage-' . uniqid();
		mkdir( $this->base . '/pivot-performance-toolkit/minified-assets', 0777, true );
		mkdir( $this->base . '/pivot-performance-toolkit/combined-assets', 0777, true );

		Functions\when( 'untrailingslashit' )->alias( static fn( string $v ): string => rtrim( $v, '/\\' ) );
		Functions\when( 'wp_delete_file' )->alias( static fn( string $f ): bool => unlink( $f ) );
		Functions\when( 'wp_upload_dir' )->justReturn(
			array(
				'basedir' => $this->base,
				'baseurl' => 'https://example.com/uploads',
				'error'   => false,
			)
		);
	}

	protected function tearDown(): void {
		foreach ( glob( $this->base . '/pivot-performance-toolkit/*/*' ) ?: array() as $file ) {
			unlink( $file );
		}

		rmdir( $this->base . '/pivot-performance-toolkit/minified-assets' );
		rmdir( $this->base . '/pivot-performance-toolkit/combined-assets' );
		rmdir( $this->base . '/pivot-performance-toolkit' );
		rmdir( $this->base );

		parent::tearDown();
	}

	public function test_dir_and_url_come_from_wp_upload_dir(): void {
		self::assertSame( $this->base . '/pivot-performance-toolkit/minified-assets', AssetStorage::dir( AssetStorage::MINIFIED ) );
		self::assertSame(
			'https://example.com/uploads/pivot-performance-toolkit/combined-assets/a.css',
			AssetStorage::url( AssetStorage::COMBINED, 'a.css' )
		);
	}

	public function test_unavailable_uploads_directory_yields_null(): void {
		Functions\when( 'wp_upload_dir' )->justReturn( array( 'error' => 'not writable' ) );

		self::assertNull( AssetStorage::dir( AssetStorage::MINIFIED ) );
		self::assertNull( AssetStorage::url( AssetStorage::MINIFIED, 'a.min.js' ) );
		self::assertSame( array(), AssetStorage::files( AssetStorage::MINIFIED ) );
	}

	public function test_stats_and_clear_only_touch_generated_files(): void {
		$min = $this->base . '/pivot-performance-toolkit/minified-assets';
		$com = $this->base . '/pivot-performance-toolkit/combined-assets';

		file_put_contents( $min . '/a.min.css', 'xx' );
		file_put_contents( $min . '/b.min.js', 'yyy' );
		file_put_contents( $min . '/keep.txt', 'z' );
		file_put_contents( $com . '/c.css', 'wwww' );

		self::assertSame( array( 'count' => 2, 'bytes' => 5 ), AssetStorage::stats( AssetStorage::MINIFIED ) );
		self::assertSame( array( 'count' => 1, 'bytes' => 4 ), AssetStorage::stats( AssetStorage::COMBINED ) );

		self::assertSame( 3, AssetStorage::clear() );
		self::assertFileExists( $min . '/keep.txt' );
		self::assertFileDoesNotExist( $com . '/c.css' );
	}
}
