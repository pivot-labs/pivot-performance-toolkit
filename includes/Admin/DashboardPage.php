<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Utils\FilesystemCheck;

final class DashboardPage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function slug(): string
    {
        return 'performance-toolkit';
    }

    public function menuTitle(): string
    {
        return __('Dashboard', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Dashboard', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'layout-dashboard';
    }

    public function renderContent(): void
    {
        $options = $this->settings->all();
        $fs_status = FilesystemCheck::getCachedStatus();
        ?>
        <section id="ptk-dashboard" class="ptk-card">
            <h2><?php esc_html_e('Optimization Overview', 'performance-toolkit'); ?></h2>

            <?php if (! $fs_status['writable']) : ?>
                <div style="margin-bottom: 16px; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
                    <p style="margin: 0;">
                        <strong><?php esc_html_e('⚠ Warning:', 'performance-toolkit'); ?></strong>
                        <?php esc_html_e('Cache directory not writable. Caching is disabled. See System Status for details.', 'performance-toolkit'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="ptk-stats-grid">
                <div class="ptk-stat">
                    <span class="ptk-stat-label"><?php esc_html_e('Page cache', 'performance-toolkit'); ?></span>
                    <strong><?php echo ! empty($options['enable_page_cache']) ? esc_html__('Enabled', 'performance-toolkit') : esc_html__('Disabled', 'performance-toolkit'); ?></strong>
                </div>
                <div class="ptk-stat">
                    <span class="ptk-stat-label"><?php esc_html_e('Script defer', 'performance-toolkit'); ?></span>
                    <strong><?php echo ! empty($options['defer_scripts']) ? esc_html__('Enabled', 'performance-toolkit') : esc_html__('Disabled', 'performance-toolkit'); ?></strong>
                </div>
                <div class="ptk-stat">
                    <span class="ptk-stat-label"><?php esc_html_e('Image lazy loading', 'performance-toolkit'); ?></span>
                    <strong><?php echo ! empty($options['lazy_load_images']) ? esc_html__('Enabled', 'performance-toolkit') : esc_html__('Disabled', 'performance-toolkit'); ?></strong>
                </div>
                <div class="ptk-stat">
                    <span class="ptk-stat-label"><?php esc_html_e('Cache directory', 'performance-toolkit'); ?></span>
                    <strong style="color: <?php echo $fs_status['writable'] ? '#28a745' : '#dc3545'; ?>;">
                        <?php echo $fs_status['writable'] ? esc_html__('Writable', 'performance-toolkit') : esc_html__('Read-only', 'performance-toolkit'); ?>
                    </strong>
                </div>
            </div>
        </section>
        <?php
    }
}

