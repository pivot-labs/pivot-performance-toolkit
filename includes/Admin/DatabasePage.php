<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Database\DatabaseOptimizer;

final class DatabasePage implements AdminPageInterface
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

    public function renderContent(): void
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
        ?>

        <?php if ($cleaned_task) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php if ($cleaned_task === 'optimize') : ?>
                        <?php
                        printf(
                            /* translators: %d = number of tables */
                            esc_html__('%d database table(s) optimized successfully.', 'performance-toolkit'),
                            $cleaned_count
                        );
                        ?>
                    <?php else : ?>
                        <?php
                        printf(
                            /* translators: 1: item type label, 2: count */
                            esc_html__('%1$s: %2$d item(s) removed successfully.', 'performance-toolkit'),
                            esc_html($task_labels[$cleaned_task] ?? $cleaned_task),
                            $cleaned_count
                        );
                        ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Overview -->
        <section class="ptk-card">
            <h2><?php esc_html_e('Database overview', 'performance-toolkit'); ?></h2>
            <div class="ptk-db-stats">
                <div class="ptk-stat">
                    <span class="ptk-stat-label"><?php esc_html_e('Total size', 'performance-toolkit'); ?></span>
                    <strong><?php echo esc_html(DatabaseOptimizer::formatBytes($stats['size_bytes'])); ?></strong>
                </div>
                <?php if ($stats['myisam_overhead_bytes'] > 0) : ?>
                <div class="ptk-stat">
                    <span class="ptk-stat-label"><?php esc_html_e('Reclaimable (MyISAM)', 'performance-toolkit'); ?></span>
                    <strong class="ptk-stat-warn"><?php echo esc_html(DatabaseOptimizer::formatBytes($stats['myisam_overhead_bytes'])); ?></strong>
                </div>
                <?php endif; ?>
                <div class="ptk-stat">
                    <span class="ptk-stat-label"><?php esc_html_e('Tables', 'performance-toolkit'); ?></span>
                    <strong><?php echo esc_html((string) count($table_stats)); ?></strong>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::CLEANUP_ACTION); ?>" />
                <input type="hidden" name="ptk_task" value="optimize" />
                <?php wp_nonce_field('ptk_database_cleanup'); ?>
                <?php
                submit_button(
                    __('Optimize all tables', 'performance-toolkit'),
                    'secondary',
                    'submit',
                    false
                );
                ?>
            </form>
        </section>

        <!-- Cleanup -->
        <section class="ptk-card">
            <h2><?php esc_html_e('Cleanup', 'performance-toolkit'); ?></h2>
            <p style="margin:0 0 16px;color:#646970"><?php esc_html_e('Remove unnecessary data to keep your database lean and fast.', 'performance-toolkit'); ?></p>

            <div class="ptk-cleanup-list">
                <?php foreach ($cleanup_items as $task => $item) : ?>
                    <div class="ptk-cleanup-item">
                        <div class="ptk-cleanup-item-info">
                            <div class="ptk-cleanup-item-label"><?php echo esc_html($item['label']); ?></div>
                            <div class="ptk-cleanup-item-desc"><?php echo esc_html($item['desc']); ?></div>
                        </div>
                        <span class="ptk-cleanup-badge <?php echo $item['count'] > 0 ? 'has-items' : ''; ?>">
                            <?php echo esc_html(number_format_i18n($item['count'])); ?>
                        </span>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="<?php echo esc_attr(self::CLEANUP_ACTION); ?>" />
                            <input type="hidden" name="ptk_task" value="<?php echo esc_attr($task); ?>" />
                            <?php wp_nonce_field('ptk_database_cleanup'); ?>
                            <button
                                type="submit"
                                class="button button-secondary ptk-cleanup-btn"
                                <?php disabled($item['count'] === 0); ?>
                            >
                                <?php esc_html_e('Clean', 'performance-toolkit'); ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Table breakdown -->
        <?php if (! empty($table_stats)) : ?>
        <section class="ptk-card">
            <h2><?php esc_html_e('Table breakdown', 'performance-toolkit'); ?></h2>
            <table class="ptk-table-list">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Table', 'performance-toolkit'); ?></th>
                        <th><?php esc_html_e('Engine', 'performance-toolkit'); ?></th>
                        <th><?php esc_html_e('Rows', 'performance-toolkit'); ?></th>
                        <th><?php esc_html_e('Size', 'performance-toolkit'); ?></th>
                        <th><?php esc_html_e('Overhead', 'performance-toolkit'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_stats as $table) : ?>
                        <tr>
                            <td><code><?php echo esc_html($table['name']); ?></code></td>
                            <td><span class="ptk-engine-badge"><?php echo esc_html($table['engine']); ?></span></td>
                            <td><?php echo esc_html(number_format_i18n($table['rows'])); ?></td>
                            <td><?php echo esc_html(DatabaseOptimizer::formatBytes($table['size_bytes'])); ?></td>
                            <td>
                                <?php if ($table['overhead_bytes'] > 0) : ?>
                                    <span class="ptk-overhead-badge">
                                        <?php echo esc_html(DatabaseOptimizer::formatBytes($table['overhead_bytes'])); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="ptk-overhead-ok">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
        <?php
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
