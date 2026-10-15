<?php
/**
 * Base test case: wires Brain\Monkey's WordPress-function mocking into
 * every unit test's setUp/tearDown.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Core helpers that WP_HTML_Tag_Processor calls (the class itself is loaded by tests/bootstrap.php).
		Functions\when( 'wp_kses_uri_attributes' )->justReturn( array( 'action', 'archive', 'background', 'cite', 'classid', 'codebase', 'data', 'formaction', 'href', 'icon', 'longdesc', 'manifest', 'poster', 'profile', 'src', 'usemap', 'xmlns' ) );

		// Request input is read through RequestInput, which sanitizes with these core functions.
		Functions\stubs(
			array(
				'sanitize_text_field' => static fn( $value ): string => trim( strip_tags( (string) $value ) ),
				'wp_unslash'          => static fn( $value ) => is_string( $value ) ? stripslashes( $value ) : $value,
			)
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	protected function requireHtmlApi(): void {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			self::markTestSkipped( 'Needs WordPress core\'s HTML API: set WP_CORE_DIR to a WordPress checkout.' );
		}
	}

	/**
	 * The URL prefixes LocalAssetResolver maps to directories: a default install
	 * served from https://example.com, with ABSPATH as the site root.
	 */
	protected function stubDefaultAssetLocations(): void {
		Functions\when( 'trailingslashit' )->alias( static fn( string $value ): string => rtrim( $value, '/\\' ) . '/' );
		Functions\when( 'untrailingslashit' )->alias( static fn( string $value ): string => rtrim( $value, '/\\' ) );
		Functions\when( 'site_url' )->justReturn( 'https://example.com' );
		Functions\when( 'content_url' )->justReturn( 'https://example.com/wp-content' );
		Functions\when( 'wp_upload_dir' )->justReturn(
			array(
				'baseurl' => 'https://example.com/wp-content/uploads',
				'basedir' => WP_CONTENT_DIR . '/uploads',
				'error'   => false,
			)
		);
	}
}
