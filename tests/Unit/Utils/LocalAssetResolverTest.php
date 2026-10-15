<?php
/**
 * Coverage for LocalAssetResolver: URL paths are mapped to files through the
 * WordPress URL prefixes, not by assuming the site root mirrors the URL.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Utils;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Tests\Unit\TestCase;
use PivotPerformanceToolkit\Utils\LocalAssetResolver;

final class LocalAssetResolverTest extends TestCase {

	private string $content_dir;
	private string $uploads_dir;

	protected function setUp(): void {
		parent::setUp();

		$this->content_dir = WP_CONTENT_DIR;
		$this->uploads_dir = WP_CONTENT_DIR . '/uploads-elsewhere';

		foreach ( array( $this->content_dir . '/themes/t', $this->uploads_dir . '/2026' ) as $dir ) {
			if ( ! is_dir( $dir ) ) {
				mkdir( $dir, 0777, true );
			}
		}

		file_put_contents( $this->content_dir . '/themes/t/style.css', 'a{}' );
		file_put_contents( $this->uploads_dir . '/2026/up.css', 'b{}' );

		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'trailingslashit' )->alias( static fn( string $v ): string => rtrim( $v, '/\\' ) . '/' );
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'site_url' )->justReturn( 'https://example.test/cms' );
		Functions\when( 'content_url' )->justReturn( 'https://example.test/assets' );
		Functions\when( 'wp_upload_dir' )->justReturn(
			array(
				'baseurl' => 'https://example.test/files',
				'basedir' => $this->uploads_dir,
				'error'   => false,
			)
		);
	}

	public function test_resolves_through_a_relocated_content_url(): void {
		$expected = realpath( $this->content_dir . '/themes/t/style.css' );

		self::assertSame( $expected, LocalAssetResolver::resolve( 'https://example.test/assets/themes/t/style.css', 'css' ) );
		self::assertSame( $expected, LocalAssetResolver::resolve( '/assets/themes/t/style.css', 'css' ) );
	}

	public function test_resolves_through_a_relocated_uploads_url(): void {
		self::assertSame(
			realpath( $this->uploads_dir . '/2026/up.css' ),
			LocalAssetResolver::resolve( 'https://example.test/files/2026/up.css', 'css' )
		);
	}

	public function test_a_url_that_does_not_mirror_the_directory_layout_is_not_found(): void {
		// The default /wp-content/ prefix is not this site's content URL.
		self::assertNull( LocalAssetResolver::resolve( 'https://example.test/wp-content/themes/t/style.css', 'css' ) );
	}

	public function test_rejects_other_hosts_wrong_extensions_and_traversal(): void {
		self::assertNull( LocalAssetResolver::resolve( 'https://cdn.other.test/assets/themes/t/style.css', 'css' ) );
		self::assertNull( LocalAssetResolver::resolve( 'https://example.test/assets/themes/t/style.css', 'js' ) );
		self::assertNull( LocalAssetResolver::resolve( 'https://example.test/assets/../../../etc/hosts.css', 'css' ) );
	}
}
