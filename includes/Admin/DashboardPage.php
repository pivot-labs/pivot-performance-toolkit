<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

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
        ?>
        <section id="ptk-dashboard" class="ptk-card">
            <h2><?php esc_html_e('Optimization Overview', 'performance-toolkit'); ?></h2>
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
            </div>
        </section>
        <?php
    }
}

