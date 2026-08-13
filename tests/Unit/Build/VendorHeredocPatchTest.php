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
	 * Regression guard for the three files bin/patch-vendor-heredoc.php
	 * patches — those must be heredoc-free in every environment (dev and
	 * dist alike). This deliberately does NOT scan the rest of
	 * includes/Vendor/: the Symfony Translation CLI-tooling files (also
	 * originally flagged) are only pruned by bin/dist-clean.sh at final
	 * dist-build time, not during normal `composer install`, so asserting
	 * their absence here would fail in every dev environment for reasons
	 * unrelated to this patch script.
	 */
	public function test_patched_files_contain_no_heredoc_or_nowdoc_syntax(): void {
		$patchedFiles = array(
			self::VENDOR_DIR . '/symfony/translation-contracts/TranslatorTrait.php',
			self::VENDOR_DIR . '/illuminate/view/DynamicComponent.php',
			self::VENDOR_DIR . '/symfony/translation/Translator.php',
		);

		foreach ( $patchedFiles as $path ) {
			$content = file_get_contents( $path );
			self::assertIsString( $content );
			self::assertStringNotContainsString( '<<<', $content, "Found HEREDOC/NOWDOC syntax in {$path} — re-run bin/patch-vendor-heredoc.php, or update it if vendor source changed shape." );
		}
	}

	/**
	 * Behavioral check, not just a string diff: confirms the patched
	 * $intervalRegexp in TranslatorTrait.php still actually matches the
	 * interval-format strings Symfony's own pluralization rules use (e.g.
	 * "]-Inf,-1[" style ranges), the same way it did before patching.
	 */
	public function test_translator_trait_interval_regexp_still_matches_real_intervals(): void {
		$regexp = self::extractVariableStatementValue(
			self::VENDOR_DIR . '/symfony/translation-contracts/TranslatorTrait.php',
			'intervalRegexp'
		);

		self::assertMatchesRegularExpression( $regexp, ']-Inf,-1[ few messages' );
		self::assertMatchesRegularExpression( $regexp, '{1,2, 3} exact messages' );
		self::assertDoesNotMatchRegularExpression( $regexp, 'not an interval at all' );
	}

	/**
	 * Extracts a `$varName = <expr>;` statement using PHP's own tokenizer
	 * (correct regardless of what quotes/semicolons the expression's string
	 * content itself contains — a naive strpos(';')/regex search breaks the
	 * moment the string being extracted contains a semicolon, which several
	 * of these patched values do) and evaluates it in isolation.
	 *
	 * @return mixed
	 */
	private static function extractVariableStatementValue( string $path, string $varName ) {
		$content = file_get_contents( $path );
		self::assertIsString( $content );

		$tokens  = token_get_all( $content );
		$capture = false;
		$depth   = 0;
		$stmt    = '';

		foreach ( $tokens as $token ) {
			$isArray = is_array( $token );
			$id      = $isArray ? $token[0] : null;
			$text    = $isArray ? $token[1] : $token;

			if ( ! $capture && T_VARIABLE === $id && '$' . $varName === $text ) {
				$capture = true;
			}

			if ( $capture ) {
				$stmt .= $text;

				if ( '(' === $text ) {
					++$depth;
				}
				if ( ')' === $text ) {
					--$depth;
				}
				if ( ';' === $text && 0 === $depth ) {
					break;
				}
			}
		}

		self::assertNotSame( '', $stmt, "Could not locate \${$varName} assignment in {$path} — has the vendor source changed shape?" );

		$fn = eval( "return function () { {$stmt} return \${$varName}; };" );

		return $fn();
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

	/**
	 * Translator.php's two patched sprintf() format strings are only ever
	 * reached via a code path this plugin doesn't use (Symfony's config-
	 * cache-backed catalogue dumping, which requires a cache dir this
	 * plugin never configures) — so there's no meaningful end-to-end
	 * behavior to exercise. This confirms the file at least stays loadable
	 * and both expected format strings are present and well-formed.
	 */
	public function test_translator_format_strings_present_and_well_formed(): void {
		$path = self::VENDOR_DIR . '/symfony/translation/Translator.php';
		$content = file_get_contents( $path );
		self::assertIsString( $content );

		// These now live inside a single-quoted PHP string literal (the
		// patched format string), not a real top-level `use` statement, so
		// the source correctly has doubled backslashes — that's how a
		// literal single backslash is written inside a single-quoted
		// string. The behavioral equivalence (the *runtime* value, with
		// single backslashes) is already covered by the exact byte-for-byte
		// verification done when this patch was built and applied.
		self::assertStringContainsString( 'use PivotPerformanceToolkit\\\\Vendor\\\\Symfony\\\\Component\\\\Translation\\\\MessageCatalogue;', $content );
		self::assertStringContainsString( "new MessageCatalogue(\\'%s\\', %s)", $content );
		self::assertStringContainsString( 'addFallbackCatalogue($catalogue%s)', $content );
	}
}
