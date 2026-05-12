<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Media\ImageOptimizerDetector;
use PerformanceToolkit\Utils\FilesystemCheck;

final class SystemStatusPage implements AdminPageInterface
{
    private Settings $settings;

    private ImageOptimizerDetector $optimizer_detector;

    public function __construct(Settings $settings, ImageOptimizerDetector $optimizer_detector)
    {
        $this->settings           = $settings;
        $this->optimizer_detector = $optimizer_detector;
    }

    public function slug(): string
    {
        return 'performance-toolkit-system-status';
    }

    public function menuTitle(): string
    {
        return __('System Status', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit System Status', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'activity';
    }

    public function renderContent(): void
    {
        global $wpdb;

        $wpdb_row = $wpdb->get_row(
            "SELECT SUM(data_length + index_length) AS db_size
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE()"
        );

        $mysql_size = (int) ($wpdb_row->db_size ?? 0);
        $cache_on = $this->settings->getBool('enable_page_cache');
        $active_optimizers = $this->optimizer_detector->activeOptimizers();
        $fs_status = FilesystemCheck::getCachedStatus();

        // Format filesystem status
        $fs_status_label = $fs_status['writable']
            ? __('Writable', 'performance-toolkit')
            : __('Read-only / Not writable', 'performance-toolkit');

        $fs_status_color = $fs_status['writable'] ? '#28a745' : '#dc3545';
        $fs_status_value = sprintf(
            '<span style="color: %s; font-weight: bold;">%s</span>',
            $fs_status_color,
            esc_html($fs_status_label)
        );

        $rows = array(
            array(
                'label' => __('PHP Version', 'performance-toolkit'),
                'value' => PHP_VERSION,
            ),
            array(
                'label' => __('WordPress Version', 'performance-toolkit'),
                'value' => get_bloginfo('version'),
            ),
            array(
                'label' => __('MySQL Version', 'performance-toolkit'),
                'value' => $wpdb->db_version(),
            ),
            array(
                'label' => __('Plugin Version', 'performance-toolkit'),
                'value' => defined('PERFORMANCE_TOOLKIT_VERSION') ? PERFORMANCE_TOOLKIT_VERSION : __('Unknown', 'performance-toolkit'),
            ),
            array(
                'label' => __('MySQL Size', 'performance-toolkit'),
                'value' => self::formatBytes($mysql_size),
            ),
            array(
                'label' => __('Page Cache', 'performance-toolkit'),
                'value' => $cache_on ? __('On', 'performance-toolkit') : __('Off', 'performance-toolkit'),
            ),
            array(
                'label' => __('Cache Directory Status', 'performance-toolkit'),
                'value' => $fs_status_value,
                'is_html' => true,
            ),
            array(
                'label' => __('Image Optimizer Plugins', 'performance-toolkit'),
                'value' => $active_optimizers === array() ? __('None detected', 'performance-toolkit') : implode(', ', $active_optimizers),
            ),
        );

        ?>
        <section id="ptk-system-status" class="ptk-card">
            <h2><?php esc_html_e('System Status', 'performance-toolkit'); ?></h2>

            <?php if (! $fs_status['writable']) : ?>
                <div style="margin-bottom: 16px; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
                    <p style="margin: 0 0 8px;">
                        <strong><?php esc_html_e('⚠ Filesystem Warning', 'performance-toolkit'); ?></strong>
                    </p>
                    <p style="margin: 0;">
                        <?php esc_html_e('The Performance Toolkit cache directory is not writable. Caching and minification are disabled. Contact your hosting provider to ensure the cache directory has write permissions.', 'performance-toolkit'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <table class="ptk-table-list ptk-status-table">
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($row['label']); ?></th>
                            <td>
                                <?php
                                if (isset($row['is_html']) && $row['is_html']) {
                                    echo wp_kses_post($row['value']);
                                } else {
                                    echo esc_html((string) $row['value']);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}

