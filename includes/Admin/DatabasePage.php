<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class DatabasePage implements AdminPageInterface
{
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
        ?>
        <section id="ptk-database" class="ptk-card">
            <h2><?php esc_html_e('Database', 'performance-toolkit'); ?></h2>
            <p><?php esc_html_e('Database cleanup and analysis tools will be added here.', 'performance-toolkit'); ?></p>
        </section>
        <?php
    }
}

