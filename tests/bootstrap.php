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

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
