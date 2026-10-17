<?php
/**
 * Guards the claim that every dynamic value in the admin templates is escaped when it is
 * rendered.
 *
 * BladeEngine::display() echoes finished HTML without escaping it again, because the
 * templates already escaped each value as they rendered. That is only true while every
 * template keeps to a few rules, so this test enforces them across includes/Views:
 *
 *  1. `{{ }}` is always safe (Blade compiles it to an HTML-escaping call).
 *  2. A raw `{!! !!}` block must start with an escaping function, be an icon from
 *     LucideIcons::render(), or be on the short, justified allowlist below.
 *  3. `href` / `src` / `action` / `formaction` values must go through esc_url().
 *  4. Every echo / print / printf in a PHP block may only print literals, a cast integer, or
 *     values wrapped in an escaping function.
 *  5. Templates never read request input ($_GET, $_POST, ...).
 *
 * If this fails, escape the value at the point of output (preferred); only extend an
 * allowlist when the value is genuinely already-escaped markup, and say why.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Build;

use PHPUnit\Framework\Attributes\DataProvider;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class TemplateEscapingTest extends TestCase {

	private const VIEWS_DIR = __DIR__ . '/../../../includes/Views';

	/**
	 * Functions whose return value is safe to print in the context they are used for.
	 */
	private const ESCAPING_FUNCTIONS = array(
		'esc_html',
		'esc_attr',
		'esc_url',
		'esc_textarea',
		'esc_js',
		'esc_html__',
		'esc_attr__',
		'esc_html_x',
		'esc_attr_x',
		'wp_kses',
		'wp_kses_post',
		'absint',
		'intval',
		'number_format_i18n',
	);

	/**
	 * Functions that simply format their arguments; the arguments themselves are still checked.
	 */
	private const FORMATTING_FUNCTIONS = array( 'sprintf', 'vsprintf', 'printf', 'vprintf' );

	/**
	 * Raw `{!! !!}` expressions that are not an escaping call, keyed by template (relative to
	 * includes/Views) and expression. Each one is markup that was already escaped when it was
	 * built, with the reason recorded here.
	 *
	 * Icon variables are handled separately: a `{!! $var !!}` is accepted when the same
	 * template assigns `$var` from LucideIcons::render() (static SVG shipped with the plugin).
	 */
	private const RAW_OUTPUT_ALLOWLIST = array(
		'admin/shell.blade.php'            => array(
			'$content' => 'Output of a legacy AdminPageInterface::renderContent() page, captured by AdminShell::render(). Those pages escape their own output.',
		),
	);

	/**
	 * @return array<int, string> Template paths relative to includes/Views.
	 */
	private static function templates(): array {
		$found    = array();
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( self::VIEWS_DIR, \FilesystemIterator::SKIP_DOTS ) );

		foreach ( $iterator as $file ) {
			if ( $file instanceof \SplFileInfo && $file->isFile() && str_ends_with( $file->getFilename(), '.blade.php' ) ) {
				$found[] = ltrim( str_replace( '\\', '/', substr( $file->getPathname(), strlen( self::VIEWS_DIR ) ) ), '/' );
			}
		}

		sort( $found );

		return $found;
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function template_provider(): array {
		$cases = array();

		foreach ( self::templates() as $template ) {
			$cases[ $template ] = array( $template );
		}

		return $cases;
	}

	private static function read( string $template ): string {
		$contents = file_get_contents( self::VIEWS_DIR . '/' . $template );
		self::assertIsString( $contents, "Could not read {$template}" );

		return $contents;
	}

	public function test_templates_were_found(): void {
		self::assertGreaterThan( 50, count( self::templates() ), 'The scan found suspiciously few templates; has includes/Views moved?' );
	}

	public function test_blade_echo_helper_html_escapes(): void {
		$helper = 'pivotperformancetoolkit_vendor_e';

		// Loaded by the main plugin file in production; the unit-test bootstrap only loads the root autoloader.
		require_once self::VIEWS_DIR . '/../Vendor/autoload.php';

		self::assertTrue( function_exists( $helper ), 'Blade {{ }} is compiled to this helper (see BladeEngine::setupViewFactory()).' );
		self::assertSame( '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $helper( '<script>alert("x")</script>' ) );
	}

	// ── Rule 2: raw {!! !!} blocks ──────────────────────────────────────

	#[DataProvider( 'template_provider' )]
	public function test_raw_output_blocks_are_escaped_or_allowlisted( string $template ): void {
		$contents = self::read( $template );

		preg_match_all( '/\{!!(.*?)!!\}/s', $contents, $matches );

		foreach ( $matches[1] as $expression ) {
			$expression = trim( (string) $expression );

			self::assertTrue(
				self::isAcceptableRawExpression( $template, $contents, $expression ),
				"Unescaped raw output in {$template}: {!! {$expression} !!}. Wrap it in esc_html()/esc_attr()/esc_url()/wp_kses_post(), or, if it is already-escaped markup, add it to RAW_OUTPUT_ALLOWLIST with the reason."
			);
		}
	}

	private static function isAcceptableRawExpression( string $template, string $contents, string $expression ): bool {
		if ( preg_match( '/^\(?\s*(?:\(int\)\s*)?\\\\?([a-z_0-9]+)\s*\(/i', $expression, $call ) && in_array( strtolower( $call[1] ), self::ESCAPING_FUNCTIONS, true ) ) {
			return true;
		}

		// sprintf() / vsprintf() of escaped pieces: the format and every argument must themselves be escaped.
		if ( preg_match( '/^(?:' . implode( '|', self::FORMATTING_FUNCTIONS ) . ')\s*\(/i', $expression ) && array() === self::unescapedParts( $expression ) ) {
			return true;
		}

		// Static icon markup: LucideIcons::render( … ) directly, or a variable assigned from it in this template.
		if ( preg_match( '/^\\\\?(?:PivotPerformanceToolkit\\\\Admin\\\\)?LucideIcons::render\(/', $expression ) ) {
			return true;
		}

		if ( preg_match( '/^\$([a-zA-Z_][a-zA-Z0-9_]*)$/', $expression, $variable ) ) {
			$assigned = '/\$' . preg_quote( $variable[1], '/' ) . '\s*=\s*\\\\?(?:PivotPerformanceToolkit\\\\Admin\\\\)?LucideIcons::render\(/';

			if ( preg_match( $assigned, $contents ) ) {
				return true;
			}
		}

		return isset( self::RAW_OUTPUT_ALLOWLIST[ $template ][ $expression ] );
	}

	public function test_every_allowlist_entry_is_still_used(): void {
		foreach ( self::RAW_OUTPUT_ALLOWLIST as $template => $entries ) {
			$contents = self::read( $template );

			foreach ( array_keys( $entries ) as $expression ) {
				self::assertStringContainsString( '{!! ' . $expression . ' !!}', $contents, "Stale allowlist entry for {$template}: {$expression}. Remove it." );
			}
		}
	}

	// ── Rule 3: URL attributes ──────────────────────────────────────────

	#[DataProvider( 'template_provider' )]
	public function test_url_attributes_are_passed_through_esc_url( string $template ): void {
		$contents = self::read( $template );

		preg_match_all( '/(?<![\w-])(?:href|src|action|formaction)\s*=\s*"([^"]*)"/i', $contents, $attributes );

		foreach ( $attributes[1] as $value ) {
			if ( ! str_contains( $value, '{{' ) && ! str_contains( $value, '{!!' ) && ! str_contains( $value, '<?' ) ) {
				continue;
			}

			// Every dynamic piece of the value must be an esc_url() call (a mixed literal + esc_url() is fine).
			preg_match_all( '/\{\{(.*?)\}\}|\{!!(.*?)!!\}|<\?(?:php\s+echo|=)(.*?)\?>/s', $value, $pieces, PREG_SET_ORDER );

			foreach ( $pieces as $piece ) {
				$expression = trim( $piece[1] . ( $piece[2] ?? '' ) . ( $piece[3] ?? '' ) );

				self::assertMatchesRegularExpression(
					'/^esc_url(?:_raw)?\s*\(/',
					$expression,
					"URL attribute in {$template} is not passed through esc_url(): {$value}"
				);
			}
		}
	}

	// ── Rule 4: echo / print / printf in PHP blocks ─────────────────────

	#[DataProvider( 'template_provider' )]
	public function test_php_output_statements_only_print_escaped_values( string $template ): void {
		$contents = self::read( $template );

		foreach ( self::phpBlocks( $contents ) as $code ) {
			foreach ( self::outputStatements( $code ) as $statement ) {
				$problems = self::unescapedParts( $statement );

				self::assertSame(
					array(),
					$problems,
					"Unescaped value(s) printed in {$template}: " . implode( ', ', $problems ) . ' in `' . trim( $statement ) . '`'
				);
			}
		}
	}

	/**
	 * @return array<int, string>
	 */
	private static function phpBlocks( string $contents ): array {
		$blocks = array();

		if ( preg_match_all( '/@php\b(.*?)@endphp/s', $contents, $found ) ) {
			$blocks = array_merge( $blocks, $found[1] );
		}

		if ( preg_match_all( '/<\?php\b(.*?)(?:\?>|\z)/s', $contents, $found ) ) {
			$blocks = array_merge( $blocks, $found[1] );
		}

		if ( preg_match_all( '/<\?=(.*?)\?>/s', $contents, $found ) ) {
			foreach ( $found[1] as $expression ) {
				$blocks[] = 'echo ' . $expression . ';';
			}
		}

		return $blocks;
	}

	/**
	 * Splits a PHP snippet into its output statements: everything from `echo` / `print` /
	 * `printf` up to the terminating semicolon.
	 *
	 * @return array<int, string>
	 */
	private static function outputStatements( string $code ): array {
		$tokens     = token_get_all( '<?php ' . $code );
		$statements = array();
		$current    = null;
		$depth      = 0;

		foreach ( $tokens as $token ) {
			$id   = is_array( $token ) ? $token[0] : null;
			$text = is_array( $token ) ? $token[1] : $token;

			if ( null === $current ) {
				$starts = T_ECHO === $id || T_PRINT === $id || ( T_STRING === $id && in_array( strtolower( $text ), array( 'printf', 'vprintf', 'print_r' ), true ) );

				if ( $starts ) {
					$current = '';
					$depth   = 0;

					if ( T_STRING === $id ) {
						$current = $text;
					}
				}

				continue;
			}

			if ( '(' === $text || '[' === $text ) {
				++$depth;
			} elseif ( ')' === $text || ']' === $text ) {
				--$depth;
			}

			if ( ';' === $text && $depth <= 0 ) {
				$statements[] = $current;
				$current      = null;
				continue;
			}

			$current .= $text;
		}

		if ( null !== $current && '' !== trim( $current ) ) {
			$statements[] = $current;
		}

		return $statements;
	}

	/**
	 * Returns the parts of an output statement that print something other than a literal, a
	 * cast number, or the result of an escaping function.
	 *
	 * @return array<int, string>
	 */
	private static function unescapedParts( string $statement ): array {
		$tokens      = token_get_all( '<?php ' . $statement . ';' );
		$problems    = array();
		$depth       = 0;
		$safe_levels = array();
		$pending     = false;
		$after_cast  = false;
		$count       = count( $tokens );

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			$id    = is_array( $token ) ? $token[0] : null;
			$text  = is_array( $token ) ? $token[1] : $token;

			if ( T_WHITESPACE === $id || T_OPEN_TAG === $id ) {
				continue;
			}

			if ( '(' === $text ) {
				++$depth;

				if ( $pending ) {
					$safe_levels[] = $depth;
					$pending       = false;
				}

				continue;
			}

			if ( ')' === $text ) {
				if ( array() !== $safe_levels && end( $safe_levels ) === $depth ) {
					array_pop( $safe_levels );
				}

				--$depth;
				continue;
			}

			$in_safe_region = array() !== $safe_levels;

			if ( in_array( $id, array( T_INT_CAST, T_DOUBLE_CAST, T_BOOL_CAST ), true ) ) {
				$after_cast = true;
				continue;
			}

			if ( T_EMPTY === $id || T_ISSET === $id ) {
				$pending = true; // Conditions, never printed.
				continue;
			}

			if ( T_STRING === $id ) {
				$next = $tokens[ $i + 1 ] ?? null;
				$next = ( is_array( $next ) && T_WHITESPACE === $next[0] ) ? ( $tokens[ $i + 2 ] ?? null ) : $next;

				if ( '(' === $next ) {
					$name = strtolower( $text );

					if ( in_array( $name, self::ESCAPING_FUNCTIONS, true ) ) {
						$pending = true;
					} elseif ( in_array( $name, self::FORMATTING_FUNCTIONS, true ) ) {
						$pending = false; // Arguments are checked individually.
					} elseif ( ! $in_safe_region ) {
						$problems[] = $text . '()';
					}
				}

				continue;
			}

			if ( T_VARIABLE === $id ) {
				if ( ! $in_safe_region && ! $after_cast ) {
					$problems[] = $text;
				}

				$after_cast = false;
			}
		}

		return $problems;
	}

	// ── Rule 5: no request input in templates ───────────────────────────

	#[DataProvider( 'template_provider' )]
	public function test_templates_do_not_read_request_input( string $template ): void {
		self::assertDoesNotMatchRegularExpression(
			'/\$_(?:GET|POST|REQUEST|COOKIE|SERVER|FILES)\b/',
			self::read( $template ),
			"{$template} reads request input directly; pass a validated value in from the page class instead."
		);
	}

	// ── The checker itself ──────────────────────────────────────────────

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function statement_provider(): array {
		return array(
			'escaped variable'                  => array( "echo esc_html( \$title );", true ),
			'escaped, nested call'              => array( "echo esc_attr( (string) \$options['x'] );", true ),
			'literal only'                      => array( "echo '<hr />';", true ),
			'concatenation with escaping'       => array( "echo '<input value=\"' . esc_attr( \$v ) . '\" />';", true ),
			'ternary on empty() with literals'  => array( "echo '<i value=\"' . ( ! empty( \$o['a'] ) ? '1' : '0' ) . '\" />';", true ),
			'cast integer'                      => array( "echo (int) \$count;", true ),
			'printf with escaped args'          => array( "printf( esc_html__( '%d files', 'x' ), esc_html( (string) \$n ) );", true ),
			'bare variable'                     => array( "echo \$title;", false ),
			'variable concatenated unescaped'   => array( "echo '<b>' . \$name . '</b>';", false ),
			'unescaped translation as format'   => array( "printf( __( 'Hi %s', 'x' ), esc_html( \$n ) );", false ),
			'variable in double-quoted string'  => array( "echo \"<b>\$name</b>\";", false ),
			'ternary branch prints a variable'  => array( "echo ( ! empty( \$a ) ? \$b : '' );", false ),
			'unknown function result'           => array( "echo some_helper( \$x );", false ),
		);
	}

	#[DataProvider( 'statement_provider' )]
	public function test_the_checker_flags_what_it_should( string $statement, bool $is_safe ): void {
		$statements = self::outputStatements( $statement );

		self::assertNotSame( array(), $statements, 'The statement should be recognised as output.' );

		$problems = array();

		foreach ( $statements as $found ) {
			$problems = array_merge( $problems, self::unescapedParts( $found ) );
		}

		if ( $is_safe ) {
			self::assertSame( array(), $problems );
		} else {
			self::assertNotSame( array(), $problems );
		}
	}
}
