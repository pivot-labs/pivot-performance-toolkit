<?php
/**
 * Database admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Database\DatabaseOptimizer;

final class DatabasePage extends BladeAdminPage {

	private const CLEANUP_ACTION = 'pivot_performance_toolkit_database_cleanup';

	private DatabaseOptimizer $optimizer;

	public function __construct( DatabaseOptimizer $optimizer ) {
		$this->optimizer = $optimizer;
		add_action( 'admin_post_' . self::CLEANUP_ACTION, array( $this, 'handleCleanup' ) );
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-database';
	}

	public function menuTitle(): string {
		return __( 'Database', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Database', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'database-zap';
	}

	public function view(): string {
		return 'admin.database-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		$sort_by  = isset( $_GET['sort'] ) ? sanitize_key( (string) wp_unslash( $_GET['sort'] ) ) : 'size';
		$sort_dir = isset( $_GET['sort_dir'] ) ? sanitize_key( (string) wp_unslash( $_GET['sort_dir'] ) ) : 'desc';

		if ( ! in_array( $sort_by, array( 'name', 'engine', 'rows', 'size' ), true ) ) {
			$sort_by = 'size';
		}

		if ( ! in_array( $sort_dir, array( 'asc', 'desc' ), true ) ) {
			$sort_dir = 'desc';
		}

		$stats         = $this->optimizer->getDatabaseStats();
		$table_stats   = $this->optimizer->getTableStats( $sort_by, $sort_dir );
		$cleaned_task  = isset( $_GET['pivot_performance_toolkit_cleaned'] ) ? sanitize_key( (string) wp_unslash( $_GET['pivot_performance_toolkit_cleaned'] ) ) : '';
		$cleaned_count = isset( $_GET['pivot_performance_toolkit_count'] ) ? (int) wp_unslash( $_GET['pivot_performance_toolkit_count'] ) : 0;
		$show_overhead = false;
		$has_innodb    = false;
		$has_myisam    = false;

		foreach ( $table_stats as $table ) {
			$engine = strtoupper( (string) ( $table['engine'] ?? '' ) );

			if ( 'MYISAM' === $engine ) {
				$show_overhead = true;
				$has_myisam    = true;
			}

			if ( 'INNODB' === $engine ) {
				$has_innodb = true;
			}
		}

		$engine_display = __( 'Unknown', 'pivot-performance-toolkit' );

		if ( $has_innodb && $has_myisam ) {
			$engine_display = 'InnoDB, MyISAM';
		} elseif ( $has_innodb ) {
			$engine_display = 'InnoDB';
		} elseif ( $has_myisam ) {
			$engine_display = 'MyISAM';
		}

		$cleanup_items = array(
			'revisions'      => array(
				'label' => __( 'Post revisions', 'pivot-performance-toolkit' ),
				'desc'  => __( 'Saved history snapshots of edited posts and pages.', 'pivot-performance-toolkit' ),
				'count' => $this->optimizer->countRevisions(),
			),
			'auto_drafts'    => array(
				'label' => __( 'Auto-drafts', 'pivot-performance-toolkit' ),
				'desc'  => __( 'Unsaved draft posts created automatically by WordPress.', 'pivot-performance-toolkit' ),
				'count' => $this->optimizer->countAutoDrafts(),
			),
			'trash_posts'    => array(
				'label' => __( 'Trashed posts', 'pivot-performance-toolkit' ),
				'desc'  => __( 'Posts and pages sitting in the trash.', 'pivot-performance-toolkit' ),
				'count' => $this->optimizer->countTrashedPosts(),
			),
			'spam_comments'  => array(
				'label' => __( 'Spam comments', 'pivot-performance-toolkit' ),
				'desc'  => __( 'Comments marked as spam.', 'pivot-performance-toolkit' ),
				'count' => $this->optimizer->countSpamComments(),
			),
			'trash_comments' => array(
				'label' => __( 'Trashed comments', 'pivot-performance-toolkit' ),
				'desc'  => __( 'Comments moved to the trash.', 'pivot-performance-toolkit' ),
				'count' => $this->optimizer->countTrashedComments(),
			),
			'transients'     => array(
				'label' => __( 'Expired transients', 'pivot-performance-toolkit' ),
				'desc'  => __( 'Cached data that has already passed its expiry time.', 'pivot-performance-toolkit' ),
				'count' => $this->optimizer->countExpiredTransients(),
			),
		);

		$task_labels = array(
			'revisions'      => __( 'Post revisions', 'pivot-performance-toolkit' ),
			'auto_drafts'    => __( 'Auto-drafts', 'pivot-performance-toolkit' ),
			'trash_posts'    => __( 'Trashed posts', 'pivot-performance-toolkit' ),
			'spam_comments'  => __( 'Spam comments', 'pivot-performance-toolkit' ),
			'trash_comments' => __( 'Trashed comments', 'pivot-performance-toolkit' ),
			'transients'     => __( 'Expired transients', 'pivot-performance-toolkit' ),
			'optimize'       => __( 'Tables optimized', 'pivot-performance-toolkit' ),
		);

		return array(
			'stats'              => $stats,
			'table_stats'        => $table_stats,
			'cleanup_items'      => $cleanup_items,
			'task_labels'        => $task_labels,
			'cleaned_task'       => $cleaned_task,
			'cleaned_count'      => $cleaned_count,
			'sort_by'            => $sort_by,
			'sort_dir'           => $sort_dir,
			'show_overhead'      => $show_overhead,
			'engine_display'     => $engine_display,
			'cleanup_action'     => self::CLEANUP_ACTION,
			'db_size_formatted'  => DatabaseOptimizer::formatBytes( (int) $stats['size_bytes'] ),
			'myisam_reclaimable' => DatabaseOptimizer::formatBytes( (int) $stats['myisam_overhead_bytes'] ),
			'overview_url'       => add_query_arg(
				array(
					'page'    => 'pivot-performance-toolkit',
					'section' => 'database',
					'tab'     => 'pivot-performance-toolkit-database',
				),
				admin_url( 'admin.php' )
			),
			'tables_url'         => add_query_arg(
				array(
					'page'    => 'pivot-performance-toolkit',
					'section' => 'database',
					'tab'     => 'pivot-performance-toolkit-database-table',
				),
				admin_url( 'admin.php' )
			),
		);
	}

	public function handleCleanup(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( 'pivot_performance_toolkit_database_cleanup' );

		$task  = isset( $_POST['pivot_performance_toolkit_task'] ) ? sanitize_key( (string) wp_unslash( $_POST['pivot_performance_toolkit_task'] ) ) : '';
		$count = 0;

		switch ( $task ) {
			case 'revisions':
				$count = $this->optimizer->deleteRevisions();
				break;
			case 'auto_drafts':
				$count = $this->optimizer->deleteAutoDrafts();
				break;
			case 'trash_posts':
				$count = $this->optimizer->deleteTrashedPosts();
				break;
			case 'spam_comments':
				$count = $this->optimizer->deleteSpamComments();
				break;
			case 'trash_comments':
				$count = $this->optimizer->deleteTrashedComments();
				break;
			case 'transients':
				$count = $this->optimizer->deleteExpiredTransients();
				break;
			case 'optimize':
				$count = $this->optimizer->optimizeTables();
				break;
			default:
				wp_safe_redirect( admin_url( 'admin.php?page=' . $this->slug() ) );
				exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                              => 'pivot-performance-toolkit',
					'section'                           => 'database',
					'tab'                               => 'pivot-performance-toolkit-database',
					'pivot_performance_toolkit_cleaned' => $task,
					'pivot_performance_toolkit_count'   => $count,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
