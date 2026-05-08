<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class ToolsPage implements AdminPageInterface
{
    public function slug(): string
    {
        return 'performance-toolkit-tools';
    }

    public function menuTitle(): string
    {
        return __('Tools', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Tools', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'wrench';
    }

    public function renderContent(): void
    {
        ?>
        <section id="ptk-tools" class="ptk-card">
            <h2><?php esc_html_e('Tools', 'performance-toolkit'); ?></h2>
            <p><?php esc_html_e('Maintenance and utility tools will be added here.', 'performance-toolkit'); ?></p>
        </section>
        <?php
    }
}

