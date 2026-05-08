<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class CdnIntegrationsPage implements AdminPageInterface
{
    public function slug(): string
    {
        return 'performance-toolkit-cdn-integrations';
    }

    public function menuTitle(): string
    {
        return __('CDN & Integrations', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit CDN & Integrations', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'dashicons-admin-site-alt3';
    }

    public function renderContent(): void
    {
        ?>
        <section id="ptk-cdn-integrations" class="ptk-card">
            <h2><?php esc_html_e('CDN & Integrations', 'performance-toolkit'); ?></h2>
            <p><?php esc_html_e('CDN and third-party integration settings will be added here.', 'performance-toolkit'); ?></p>
        </section>
        <?php
    }
}

