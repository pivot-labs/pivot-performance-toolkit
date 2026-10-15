<?php
/**
 * Regression coverage for bin/patch-vendor-heredoc.php: WP.org's review
 * disallows HEREDOC/NOWDOC syntax (their stated reasoning is that it hides
 * unescaped variables from their scanner). Strauss regenerates
 * includes/Vendor/ from scratch on every `composer install`/`update`, so
 * these checks exist to catch two separate failure modes: (1) the patch
 * script silently not running, and (2) a future Strauss/Composer version
 * bump changing vendor source enough that the patch's exact-match search
 * text no longer applies (patch-vendor-heredoc.php itself already fails
 * loudly for that case — this is the "did anyone notice and re-run it"
 * backstop).
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Build;

use PivotPerformanceToolkit\Tests\Unit\TestCase;
use PivotPerformanceToolkit\Vendor\Illuminate\View\DynamicComponent;

final class VendorHeredocPatchTest extends TestCase {

	private const VENDOR_DIR = __DIR__ . '/../../../includes/Vendor';

	/**
	 * Regression guard for the file bin/patch-vendor-heredoc.php patches
	 * (illuminate/view's DynamicComponent) — it must be heredoc-free in every
	 * environment (dev and dist alike).
	 */
	public function test_patched_files_contain_no_heredoc_or_nowdoc_syntax(): void {
		$patchedFiles = array(
			self::VENDOR_DIR . '/illuminate/view/DynamicComponent.php',
		);

		foreach ( $patchedFiles as $path ) {
			$content = file_get_contents( $path );
			self::assertIsString( $content );
			self::assertStringNotContainsString( '<<<', $content, "Found HEREDOC/NOWDOC syntax in {$path} — re-run bin/patch-vendor-heredoc.php, or update it if vendor source changed." );
		}
	}

	/**
	 * Confirms DynamicComponent (the illuminate/view class flagged for
	 * NOWDOC usage) still loads, constructs, and produces a render()
	 * closure whose template contains every placeholder token the rest of
	 * the class's substitution logic depends on — i.e. the patched $template
	 * string wasn't silently truncated or mis-escaped.
	 */
	public function test_dynamic_component_template_placeholders_intact(): void {
		$component = new DynamicComponent( 'test-component' );
		$render = $component->render();

		self::assertInstanceOf( \Closure::class, $render );

		$reflection = new \ReflectionFunction( $render );
		$boundTemplate = $reflection->getStaticVariables()['template'] ?? null;

		self::assertIsString( $boundTemplate );

		foreach ( array( '{{ component }}', '{{ props }}', '{{ bindings }}', '{{ attributes }}', '{{ slots }}', '{{ defaultSlot }}' ) as $placeholder ) {
			self::assertStringContainsString( $placeholder, $boundTemplate, "Missing placeholder {$placeholder} in DynamicComponent's patched template." );
		}

		self::assertStringStartsWith( '<?php extract(', $boundTemplate );
		self::assertStringContainsString( 'EXTR_SKIP', $boundTemplate );
	}
}
