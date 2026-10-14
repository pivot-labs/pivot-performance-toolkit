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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- this feeds a live "how much cruft is there right now" cleanup dashboard; caching would show stale counts immediately after the admin runs a delete action.
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
		);
	}

	public function countAutoDrafts(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- this feeds a live "how much cruft is there right now" cleanup dashboard; caching would show stale counts immediately after the admin runs a delete action.
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
		);
	}

	public function countTrashedPosts(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- this feeds a live "how much cruft is there right now" cleanup dashboard; caching would show stale counts immediately after the admin runs a delete action.
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);
	}

	public function countSpamComments(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- this feeds a live "how much cruft is there right now" cleanup dashboard; caching would show stale counts immediately after the admin runs a delete action.
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
		);
	}

	public function countTrashedComments(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- this feeds a live "how much cruft is there right now" cleanup dashboard; caching would show stale counts immediately after the admin runs a delete action.
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
		);
	}

	public function countExpiredTransients(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- this feeds a live "how much cruft is there right now" cleanup dashboard; caching would show stale counts immediately after the admin runs a delete action.
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- one-off lookup immediately consumed by the delete loop below, not a repeated read worth caching.
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- a DELETE mutation; there is nothing here to cache.
		$affected = $wpdb->query(
			"DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
		);

		return false === $affected ? 0 : (int) $affected;
	}

	public function deleteTrashedPosts(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- one-off lookup immediately consumed by the delete loop below, not a repeated read worth caching.
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- one-off lookup immediately consumed by the delete loop below, not a repeated read worth caching.
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- one-off lookup immediately consumed by the delete loop below, not a repeated read worth caching.
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- one-off lookup immediately consumed by the delete loop below, not a repeated read worth caching.
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
			/*
			 * delete_transient() is cache-aware: with an external/persistent
			 * object cache active (as with this plugin's own object-cache.php
			 * drop-in), it calls wp_cache_delete() instead of touching
			 * wp_options at all. These rows are stale DB leftovers that were
			 * never in the object cache to begin with, so that call finds
			 * nothing to delete and reports success without removing
			 * anything — the row (and the count this feeds) never changes.
			 * Delete the option rows directly instead, same as every other
			 * cleanup method in this file, and also clear any object-cache
			 * copy so a stale cached value can't outlive its DB row.
			 */
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- a DELETE mutation on stale DB rows the cache-aware delete_transient() can't reach; see comment above.
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name IN (%s, %s)",
					'_transient_' . $key,
					'_transient_timeout_' . $key
				)
			);

			wp_cache_delete( $key, 'transient' );
			wp_cache_delete( $key, 'transient_timeout' );

			if ( $deleted ) {
				++$count;
			}
		}

		return $count;
	}

	// ── Optimize tables ──────────────────────────────────────────────────────

	public function optimizeTables(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- one-off admin-triggered maintenance action, not a repeated read worth caching.
		$tables = $wpdb->get_col( 'SHOW TABLES' ) ?: array();
		$count  = 0;

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- OPTIMIZE TABLE is a maintenance mutation; there is nothing here to cache. The table name is bound as an identifier with %i.
			$wpdb->query( $wpdb->prepare( 'OPTIMIZE TABLE %i', $table ) );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- live database-size status display; caching would show stale figures right after the admin runs a cleanup/optimize action.
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
			'name'   => 'name',
			'engine' => 'engine',
			'rows'   => 'rows',
			'size'   => 'size_bytes',
		);

		$sort_key  = $sortable_columns[ $sort_by ] ?? 'size_bytes';
		$ascending = 'asc' === strtolower( $sort_dir );

		// Constant query (no interpolated values); the sort happens in PHP below, so no
		// request-derived value ever reaches the SQL.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- live per-table size/row-count status display; caching would show stale figures right after the admin runs a cleanup/optimize action.
		$rows = $wpdb->get_results(
			"SELECT
                table_name                 AS tbl_name,
                engine                     AS tbl_engine,
                table_rows                 AS tbl_rows,
                data_length + index_length AS tbl_size,
                CASE WHEN engine = 'MyISAM' THEN data_free ELSE 0 END AS tbl_overhead
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE()"
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

		usort(
			$result,
			static function ( array $a, array $b ) use ( $sort_key, $ascending ): int {
				$order = $a[ $sort_key ] <=> $b[ $sort_key ];

				if ( 0 === $order ) {
					$order = strcmp( $a['name'], $b['name'] );
				}

				return $ascending ? $order : -$order;
			}
		);

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
