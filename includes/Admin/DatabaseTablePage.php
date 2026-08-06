<?php
/**
 * Database table admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Database\DatabaseOptimizer;

final class DatabaseTablePage extends BladeAdminPage {

	private DatabaseOptimizer $optimizer;

	public function __construct( DatabaseOptimizer $optimizer ) {
		$this->optimizer = $optimizer;
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-database-table';
	}

	public function menuTitle(): string {
		return __( 'Table Breakdown', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Database Table Breakdown', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'database';
	}

	public function view(): string {
		return 'admin.database-table-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only table-sort query args, not a state-changing action; both are sanitized via sanitize_key() and validated against a hardcoded whitelist below.
		$sort_by  = isset( $_GET['sort'] ) ? sanitize_key( (string) wp_unslash( $_GET['sort'] ) ) : 'size';
		$sort_dir = isset( $_GET['sort_dir'] ) ? sanitize_key( (string) wp_unslash( $_GET['sort_dir'] ) ) : 'desc';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $sort_by, array( 'name', 'engine', 'rows', 'size' ), true ) ) {
			$sort_by = 'size';
		}

		if ( ! in_array( $sort_dir, array( 'asc', 'desc' ), true ) ) {
			$sort_dir = 'desc';
		}

		$table_stats   = $this->optimizer->getTableStats( $sort_by, $sort_dir );
		$show_overhead = false;

		foreach ( $table_stats as $table ) {
			if ( strtoupper( (string) ( $table['engine'] ?? '' ) ) === 'MYISAM' ) {
				$show_overhead = true;
				break;
			}
		}

		return array(
			'table_stats'   => $table_stats,
			'show_overhead' => $show_overhead,
			'sort_by'       => $sort_by,
			'sort_dir'      => $sort_dir,
			'overview_url'  => add_query_arg(
				array(
					'page'    => 'pivot-performance-toolkit',
					'section' => 'database',
					'tab'     => 'pivot-performance-toolkit-database',
				),
				admin_url( 'admin.php' )
			),
			'tables_url'    => add_query_arg(
				array(
					'page'    => 'pivot-performance-toolkit',
					'section' => 'database',
					'tab'     => 'pivot-performance-toolkit-database-table',
				),
				admin_url( 'admin.php' )
			),
		);
	}
}
