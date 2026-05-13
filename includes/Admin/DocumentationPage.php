<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Views\BladeEngine;

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
        echo BladeEngine::view('admin.documentation-page', $this->getViewData());
    }

    /**
     * @return array<string, string>
     */
    private function getViewData(): array
    {
        return array(
            'docs_url' => self::DOCS_URL,
        );
    }
}
