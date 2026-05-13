<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Database\DatabaseOptimizer;

final class DatabasePage extends BladeAdminPage
{
    private const CLEANUP_ACTION = 'ptk_database_cleanup';

    private DatabaseOptimizer $optimizer;

    public function __construct(DatabaseOptimizer $optimizer)
    {
        $this->optimizer = $optimizer;
        add_action('admin_post_' . self::CLEANUP_ACTION, array($this, 'handleCleanup'));
    }

    public function slug(): string
    {
        return 'performance-toolkit-database';
    }

    public function menuTitle(): string
    {
        return __('Database', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Database', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'database-zap';
    }

    public function view(): string
    {
        return 'admin.database-page';
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildViewData(): array
    {
        $stats          = $this->optimizer->getDatabaseStats();
        $table_stats    = $this->optimizer->getTableStats();
        $cleaned_task   = isset($_GET['ptk_cleaned']) ? sanitize_key((string) wp_unslash($_GET['ptk_cleaned'])) : '';
        $cleaned_count  = isset($_GET['ptk_count']) ? (int) wp_unslash($_GET['ptk_count']) : 0;

        $cleanup_items = array(
            'revisions'        => array(
                'label' => __('Post revisions', 'performance-toolkit'),
                'desc'  => __('Saved history snapshots of edited posts and pages.', 'performance-toolkit'),
                'count' => $this->optimizer->countRevisions(),
            ),
            'auto_drafts'      => array(
                'label' => __('Auto-drafts', 'performance-toolkit'),
                'desc'  => __('Unsaved draft posts created automatically by WordPress.', 'performance-toolkit'),
                'count' => $this->optimizer->countAutoDrafts(),
            ),
            'trash_posts'      => array(
                'label' => __('Trashed posts', 'performance-toolkit'),
                'desc'  => __('Posts and pages sitting in the trash.', 'performance-toolkit'),
                'count' => $this->optimizer->countTrashedPosts(),
            ),
            'spam_comments'    => array(
                'label' => __('Spam comments', 'performance-toolkit'),
                'desc'  => __('Comments marked as spam.', 'performance-toolkit'),
                'count' => $this->optimizer->countSpamComments(),
            ),
            'trash_comments'   => array(
                'label' => __('Trashed comments', 'performance-toolkit'),
                'desc'  => __('Comments moved to the trash.', 'performance-toolkit'),
                'count' => $this->optimizer->countTrashedComments(),
            ),
            'transients'       => array(
                'label' => __('Expired transients', 'performance-toolkit'),
                'desc'  => __('Cached data that has already passed its expiry time.', 'performance-toolkit'),
                'count' => $this->optimizer->countExpiredTransients(),
            ),
        );

        $task_labels = array(
            'revisions'      => __('Post revisions', 'performance-toolkit'),
            'auto_drafts'    => __('Auto-drafts', 'performance-toolkit'),
            'trash_posts'    => __('Trashed posts', 'performance-toolkit'),
            'spam_comments'  => __('Spam comments', 'performance-toolkit'),
            'trash_comments' => __('Trashed comments', 'performance-toolkit'),
            'transients'     => __('Expired transients', 'performance-toolkit'),
            'optimize'       => __('Tables optimized', 'performance-toolkit'),
        );

        return array(
            'stats'               => $stats,
            'table_stats'         => $table_stats,
            'cleanup_items'       => $cleanup_items,
            'task_labels'         => $task_labels,
            'cleaned_task'        => $cleaned_task,
            'cleaned_count'       => $cleaned_count,
            'cleanup_action'      => self::CLEANUP_ACTION,
            'db_size_formatted'   => DatabaseOptimizer::formatBytes((int) $stats['size_bytes']),
            'myisam_reclaimable'  => DatabaseOptimizer::formatBytes((int) $stats['myisam_overhead_bytes']),
        );
    }

    public function handleCleanup(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_database_cleanup');

        $task  = isset($_POST['ptk_task']) ? sanitize_key((string) wp_unslash($_POST['ptk_task'])) : '';
        $count = 0;

        switch ($task) {
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
                wp_safe_redirect(admin_url('admin.php?page=' . $this->slug()));
                exit;
        }

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'        => $this->slug(),
                    'ptk_cleaned' => $task,
                    'ptk_count'   => $count,
                ),
                admin_url('admin.php')
            )
        );
        exit;
    }
}
