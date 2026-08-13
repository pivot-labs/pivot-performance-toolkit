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

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

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
