<?php
/**
 * PHPUnit bootstrap for unit tests. These tests never load WordPress —
 * WordPress functions are stubbed per-test via Brain\Monkey (see
 * tests/Unit/TestCase.php), keeping the suite fast and dependency-free.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Scratch dir for tests that exercise real file writes (e.g. Combine's
// combined-asset cache) — gitignored, safe to wipe between runs.
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', __DIR__ . '/tmp/wp-content' );
}

if ( ! defined( 'PIVOT_PERFORMANCE_TOOLKIT_VERSION' ) ) {
	define( 'PIVOT_PERFORMANCE_TOOLKIT_VERSION', '0.0.0-test' );
}

if ( ! defined( 'PIVOT_PERFORMANCE_TOOLKIT_URL' ) ) {
	define( 'PIVOT_PERFORMANCE_TOOLKIT_URL', 'https://example.com/wp-content/plugins/pivot-performance-toolkit/' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// The HTML API (WP_HTML_Tag_Processor) is real WordPress core code that the output
// optimizers use to rewrite tags. It is loaded from a WordPress checkout when one is
// available: set WP_CORE_DIR to its root, or run the tests from inside a WordPress
// install. Tests that need it skip themselves otherwise (see tests/Unit/TestCase.php).
( static function (): void {
	$candidates = array( getenv( 'WP_CORE_DIR' ) ?: '' );
	$dir        = getcwd();

	for ( $i = 0; $i < 6 && is_string( $dir ) && '' !== $dir; $i++, $dir = dirname( $dir ) ) {
		$candidates[] = $dir;
	}

	foreach ( $candidates as $root ) {
		if ( '' === $root || ! is_file( $root . '/wp-includes/html-api/class-wp-html-tag-processor.php' ) ) {
			continue;
		}

		require_once $root . '/wp-includes/utf8.php';

		require_once $root . '/wp-includes/class-wp-token-map.php';
		require_once $root . '/wp-includes/html-api/html5-named-character-references.php';

		foreach ( array( 'class-wp-html-attribute-token', 'class-wp-html-span', 'class-wp-html-text-replacement', 'class-wp-html-decoder', 'class-wp-html-tag-processor' ) as $file ) {
			require_once $root . '/wp-includes/html-api/' . $file . '.php';
		}

		return;
	}
} )();

// Minimal stand-ins for the two WP core REST classes exercised by
// PerformanceTest's REST callbacks — just enough surface for tests to
// construct a request and inspect an error response, not a full WP core stub.
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		/** @var array<string, mixed> */
		private array $params;

		/**
		 * @param array<string, mixed> $params
		 */
		public function __construct( array $params = array() ) {
			$this->params = $params;
		}

		/**
		 * @return mixed
		 */
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private string $code;
		private string $message;

		/** @var array<string, mixed> */
		private array $data;

		/**
		 * @param array<string, mixed> $data
		 */
		public function __construct( string $code = '', string $message = '', $data = array() ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = is_array( $data ) ? $data : array( 'data' => $data );
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_error_data(): array {
			return $this->data;
		}
	}
}
