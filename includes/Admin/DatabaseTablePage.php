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
		$sort     = TableSort::fromRequest();
		$sort_by  = $sort['sort_by'];
		$sort_dir = $sort['sort_dir'];

		$table_stats   = $this->optimizer->getTableStats( $sort_by, $sort_dir );
		$show_overhead = false;

		foreach ( $table_stats as $table ) {
			if ( strtoupper( (string) ( $table['engine'] ?? '' ) ) === 'MYISAM' ) {
				$show_overhead = true;
				break;
			}
		}

		$tables_url = AdminShell::pageUrl( 'pivot-performance-toolkit-database-table' );

		return array(
			'table_stats'   => $table_stats,
			'show_overhead' => $show_overhead,
			'sort_by'       => $sort_by,
			'sort_dir'      => $sort_dir,
			'size_sort_url' => TableSort::nextSizeUrl( $tables_url, $sort_by, $sort_dir ),
			'overview_url'  => AdminShell::pageUrl( 'pivot-performance-toolkit-database' ),
			'tables_url'    => $tables_url,
		);
	}
}
