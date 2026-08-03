<?php
/**
 * Database optimization operations.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DatabaseOptimizer {

	public function countRevisions(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
		);
	}

	public function countAutoDrafts(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
		);
	}

	public function countTrashedPosts(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);
	}

	public function countSpamComments(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
		);
	}

	public function countTrashedComments(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
		);
	}

	public function countExpiredTransients(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 AND option_value < %d",
				'_transient_timeout_%',
				time()
			)
		);
	}

	// ── Delete ───────────────────────────────────────────────────────────────

	public function deleteRevisions(): int {
		global $wpdb;

		$ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision'"
		) ?: array();

		$count = 0;
		foreach ( $ids as $id ) {
			wp_delete_post_revision( (int) $id );
			++$count;
		}

		return $count;
	}

	public function deleteAutoDrafts(): int {
		global $wpdb;

		$affected = $wpdb->query(
			"DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
		);

		return false === $affected ? 0 : (int) $affected;
	}

	public function deleteTrashedPosts(): int {
		global $wpdb;

		$ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'"
		) ?: array();

		$count = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				++$count;
			}
		}

		return $count;
	}

	public function deleteSpamComments(): int {
		global $wpdb;

		$ids = $wpdb->get_col(
			"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
		) ?: array();

		$count = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_comment( (int) $id, true ) ) {
				++$count;
			}
		}

		return $count;
	}

	public function deleteTrashedComments(): int {
		global $wpdb;

		$ids = $wpdb->get_col(
			"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
		) ?: array();

		$count = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_comment( (int) $id, true ) ) {
				++$count;
			}
		}

		return $count;
	}

	public function deleteExpiredTransients(): int {
		global $wpdb;

		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT REPLACE(option_name, '_transient_timeout_', '')
                 FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 AND option_value < %d",
				'_transient_timeout_%',
				time()
			)
		) ?: array();

		$count = 0;
		foreach ( $keys as $key ) {
			if ( delete_transient( $key ) ) {
				++$count;
			}
		}

		return $count;
	}

	// ── Optimize tables ──────────────────────────────────────────────────────

	public function optimizeTables(): int {
		global $wpdb;

		$tables = $wpdb->get_col( 'SHOW TABLES' ) ?: array();
		$count  = 0;

		foreach ( $tables as $table ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( 'OPTIMIZE TABLE `' . esc_sql( $table ) . '`' );
			++$count;
		}

		return $count;
	}

	// ── Stats ────────────────────────────────────────────────────────────────

	/**
	 * @return array{size_bytes: int, myisam_overhead_bytes: int}
	 */
	public function getDatabaseStats(): array {
		global $wpdb;

		$row = $wpdb->get_row(
			"SELECT
                SUM(data_length + index_length)                    AS db_size,
                SUM(CASE WHEN engine = 'MyISAM' THEN data_free ELSE 0 END) AS myisam_overhead
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE()"
		);

		return array(
			'size_bytes'            => (int) ( $row->db_size ?? 0 ),
			'myisam_overhead_bytes' => (int) ( $row->myisam_overhead ?? 0 ),
		);
	}

	/**
	 * @return array<int, array{name: string, engine: string, rows: int, size_bytes: int, overhead_bytes: int}>
	 */
	public function getTableStats( string $sort_by = 'size', string $sort_dir = 'desc' ): array {
		global $wpdb;

		$sortable_columns = array(
			'name'   => 'tbl_name',
			'engine' => 'tbl_engine',
			'rows'   => 'tbl_rows',
			'size'   => 'tbl_size',
		);

		$order_by  = $sortable_columns[ $sort_by ] ?? 'tbl_size';
		$direction = 'asc' === strtolower( $sort_dir ) ? 'ASC' : 'DESC';

		// $order_by is restricted to a hardcoded whitelist; $direction is a ternary
		// returning only 'ASC' or 'DESC'. ORDER BY identifiers cannot use prepare().
		$rows = $wpdb->get_results(
			"SELECT
                table_name                 AS tbl_name,
                engine                     AS tbl_engine,
                table_rows                 AS tbl_rows,
                data_length + index_length AS tbl_size,
                CASE WHEN engine = 'MyISAM' THEN data_free ELSE 0 END AS tbl_overhead
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE()
             ORDER BY {$order_by} {$direction}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		) ?: array();

		$result = array();
		foreach ( $rows as $row ) {
			$result[] = array(
				'name'           => (string) $row->tbl_name,
				'engine'         => (string) $row->tbl_engine,
				'rows'           => (int) $row->tbl_rows,
				'size_bytes'     => (int) $row->tbl_size,
				'overhead_bytes' => (int) $row->tbl_overhead,
			);
		}

		return $result;
	}

	// ── Helpers ──────────────────────────────────────────────────────────────

	public static function formatBytes( int $bytes ): string {
		if ( $bytes >= 1048576 ) {
			return number_format( $bytes / 1048576, 2 ) . ' MB';
		}

		if ( $bytes >= 1024 ) {
			return number_format( $bytes / 1024, 2 ) . ' KB';
		}

		return $bytes . ' B';
	}
}
