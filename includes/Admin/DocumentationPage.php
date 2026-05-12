<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class DocumentationPage implements AdminPageInterface
{
    private const DOCS_URL = 'http://docs.wpperformancetoolkit.com';

    public function slug(): string
    {
        return 'performance-toolkit-documentation';
    }

    public function menuTitle(): string
    {
        return __('Documentation', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Documentation', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'book-open';
    }

    public function renderContent(): void
    {
        ?>
        <section id="ptk-documentation" class="ptk-card">
            <h2><?php esc_html_e('Documentation', 'performance-toolkit'); ?></h2>
            <p><?php esc_html_e('Full guides, setup instructions, and troubleshooting are available in the external documentation site.', 'performance-toolkit'); ?></p>
            <p>
                <a
                    class="button button-primary"
                    href="<?php echo esc_url(self::DOCS_URL); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?php esc_html_e('Open Documentation', 'performance-toolkit'); ?>
                </a>
            </p>
        </section>
        <?php
    }
}
