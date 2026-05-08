<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class SystemStatusPage implements AdminPageInterface
{
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
        ?>
        <section id="ptk-system-status" class="ptk-card">
            <h2><?php esc_html_e('System Status', 'performance-toolkit'); ?></h2>
            <p><?php esc_html_e('Environment diagnostics and health checks will be added here.', 'performance-toolkit'); ?></p>
        </section>
        <?php
    }
}

