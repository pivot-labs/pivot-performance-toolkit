<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class SystemStatusPage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
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
        );

        ?>
        <section id="ptk-system-status" class="ptk-card">
            <h2><?php esc_html_e('System Status', 'performance-toolkit'); ?></h2>
            <table class="ptk-table-list ptk-status-table">
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($row['label']); ?></th>
                            <td><?php echo esc_html((string) $row['value']); ?></td>
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

