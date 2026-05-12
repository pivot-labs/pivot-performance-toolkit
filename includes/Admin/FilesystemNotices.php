<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Contracts\ModuleInterface;
use PerformanceToolkit\Utils\FilesystemCheck;

final class FilesystemNotices implements ModuleInterface
{
    public function register(): void
    {
        add_action('admin_notices', array($this, 'displayFilesystemNotice'));
    }

    public function displayFilesystemNotice(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $status = FilesystemCheck::getCachedStatus();

        if ($status['writable']) {
            return;
        }

        $error_messages = $status['errors'] ?? array();

        if (empty($error_messages)) {
            return;
        }

        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php esc_html_e('Performance Toolkit – Filesystem Issue', 'performance-toolkit'); ?></strong>
            </p>
            <p>
                <?php esc_html_e('The cache directory is not writable. Performance Toolkit will continue to work, but page caching and asset minification will be disabled until the issue is resolved.', 'performance-toolkit'); ?>
            </p>
            <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
                <?php foreach ($error_messages as $error) : ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>
                <?php
                printf(
                    /* translators: %s: link to system status page */
                    wp_kses_post(__('For more details, visit the <a href="%s">System Status page</a>.', 'performance-toolkit')),
                    esc_url(add_query_arg('page', 'performance-toolkit-system-status', admin_url('admin.php')))
                );
                ?>
            </p>
            <p style="color: #666; font-size: 0.9em;">
                <?php echo wp_kses_post(__('<strong>To fix:</strong> Ensure the <code>wp-content/cache/performance-toolkit</code> directory exists and is writable by the web server. Usually: <code>chmod 755 wp-content/cache/performance-toolkit</code> or contact your hosting provider.', 'performance-toolkit')); ?>
            </p>
        </div>
        <?php
    }
}


