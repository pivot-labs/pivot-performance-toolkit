<?php
/**
 * Guards bin/patch-vendor-view-trim.php: the bundled illuminate/view must not
 * contain an ob_start() that is closed in a different function, except for the
 * component/slot support the templates need, and the templates must not use
 * the directives that were removed.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Build;

use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class VendorViewTrimTest extends TestCase {

	private const ROOT = __DIR__ . '/../../..';

	/**
	 * Files in the bundled view library allowed to call ob_start(). PhpEngine pairs it
	 * with ob_get_clean() in the same function. Component and slot buffering is done by
	 * the plugin's own Views/ClosureComponents instead (see ClosureComponentsTest).
	 */
	private const ALLOWED = array(
		'illuminate/view/Engines/PhpEngine.php',
	);

	public function test_only_expected_vendor_files_call_ob_start(): void {
		$found = array();

		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( (string) realpath( self::ROOT . '/includes/Vendor' ), \FilesystemIterator::SKIP_DOTS ) );

		foreach ( $iterator as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}

			if ( str_contains( (string) file_get_contents( $file->getPathname() ), 'ob_start(' ) ) {
				$found[] = substr( str_replace( '\\', '/', $file->getPathname() ), strlen( str_replace( '\\', '/', realpath( self::ROOT . '/includes/Vendor' ) ) ) + 1 );
			}
		}

		sort( $found );
		$expected = self::ALLOWED;
		sort( $expected );

		self::assertSame( $expected, $found, 'Re-run `composer strauss` (it applies bin/patch-vendor-view-trim.php).' );
	}

	public function test_templates_do_not_use_removed_directives(): void {
		$pattern = '/@(section|endsection|show|push|endpush|prepend|endprepend|pushOnce|prependOnce|stack|yield|extends|fragment|endfragment|lang|endlang|hasSection|sectionMissing)\b/';

		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( self::ROOT . '/includes/Views', \FilesystemIterator::SKIP_DOTS ) );

		foreach ( $iterator as $file ) {
			if ( ! str_ends_with( $file->getFilename(), '.blade.php' ) ) {
				continue;
			}

			self::assertSame(
				0,
				preg_match( $pattern, (string) file_get_contents( $file->getPathname() ) ),
				$file->getFilename() . ' uses a Blade directive that the bundled view library no longer supports.'
			);
		}
	}
}
