<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Views\BladeEngine;

final class AdminShell
{
    private const PAGE_BY_SECTION = array(
        'overview'      => 'performance-toolkit',
        'caching'       => 'performance-toolkit-cache',
        'optimization'  => 'performance-toolkit-file-optimization',
        'database'      => 'performance-toolkit-database',
        'tools'         => 'performance-toolkit-tools',
        'system-status' => 'performance-toolkit-system-status',
    );

    private const SECTION_LABELS = array(
        'overview'      => 'Overview',
        'caching'       => 'Caching',
        'optimization'  => 'Optimization',
        'database'      => 'Database',
        'tools'         => 'Tools',
        'system-status' => 'System Status',
    );

    private const SECTION_DESCRIPTIONS = array(
        'overview'      => 'View performance highlights and quick status details for your site.',
        'caching'       => 'Configure page and browser caching behavior for faster page delivery.',
        'optimization'  => 'Tune file and media optimization settings to reduce payload size.',
        'database'      => 'Review and clean database overhead to keep queries fast.',
        'tools'         => 'Export, import, and maintenance utilities for advanced site operations.',
        'system-status' => 'Inspect runtime, server, and filesystem health signals for troubleshooting.',
    );

    private const PAGE_HEADINGS = array(
        // Section-based headings can be added here if different from SECTION_LABELS
    );

    private const PAGE_DESCRIPTIONS = array(
        // Section-based descriptions can be added here if different from SECTION_DESCRIPTIONS
    );

    /**
     * @param AdminPageInterface[] $pages
     */
    public static function render(array $pages): void
    {
        // Determine current section from query parameter, default to 'overview'
        $current_section = isset($_GET['section'])
            ? sanitize_key((string) wp_unslash($_GET['section']))
            : 'overview';

        // If a specific tab/page slug is provided, use that; otherwise use the default page for the section
        $tab_override = isset($_GET['tab'])
            ? sanitize_key((string) wp_unslash($_GET['tab']))
            : '';

        if ($tab_override !== '' && isset($pages[$tab_override])) {
            $current_page_slug = $tab_override;
        } else {
            $current_page_slug = self::PAGE_BY_SECTION[$current_section] ?? 'performance-toolkit';
        }

        $current_page = $pages[$current_page_slug] ?? null;

        if (! $current_page instanceof AdminPageInterface) {
            return;
        }

        $shell_data = array(
            'icon_url'         => PERFORMANCE_TOOLKIT_URL . 'assets/img/performance-toolkit.svg',
            'plugin_version'   => defined('PERFORMANCE_TOOLKIT_VERSION') ? PERFORMANCE_TOOLKIT_VERSION : '',
            'help_url'         => 'https://docs.wpperformancetoolkit.com/',
            'primary_nav'      => self::buildPrimaryNav($current_section),
            'secondary_nav'    => self::buildSecondaryNav($current_section, $current_page_slug),
            'page_heading'     => __(self::PAGE_HEADINGS[$current_page_slug] ?? (self::SECTION_LABELS[$current_section] ?? 'Overview'), 'performance-toolkit'),
            'page_description' => __(self::PAGE_DESCRIPTIONS[$current_page_slug] ?? (self::SECTION_DESCRIPTIONS[$current_section] ?? ''), 'performance-toolkit'),
            'page_view'        => null,
            'page_data'        => array(),
            'content'          => '',
        );

        if ($current_page instanceof AdminPageViewInterface) {
            $shell_data['page_view'] = $current_page->view();
            $shell_data['page_data'] = $current_page->viewData();
        } else {
            ob_start();
            $current_page->renderContent();
            $shell_data['content'] = (string) ob_get_clean();
        }

        echo BladeEngine::view('admin.shell', $shell_data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildPrimaryNav(string $current_section): array
    {
        $items = array(
            array('key' => 'overview', 'label' => __('Overview', 'performance-toolkit'), 'icon' => 'layout-dashboard'),
            array('key' => 'caching', 'label' => __('Caching', 'performance-toolkit'), 'icon' => 'rocket'),
            array('key' => 'optimization', 'label' => __('Optimization', 'performance-toolkit'), 'icon' => 'sliders-horizontal'),
            array('key' => 'database', 'label' => __('Database', 'performance-toolkit'), 'icon' => 'database'),
            array('key' => 'tools', 'label' => __('Tools', 'performance-toolkit'), 'icon' => 'wrench'),
            array('key' => 'system-status', 'label' => __('System Status', 'performance-toolkit'), 'icon' => 'activity'),
        );

        foreach ($items as &$item) {
            $item['url'] = add_query_arg('section', $item['key'], admin_url('admin.php?page=performance-toolkit'));
            $item['active'] = $item['key'] === $current_section;
        }
        unset($item);

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildSecondaryNav(string $current_section, string $current_page_slug): array
    {
        if ($current_section === 'caching') {
            return self::withSecondaryState(
                'caching',
                array(
                    array('slug' => 'performance-toolkit-cache', 'label' => __('Page Cache', 'performance-toolkit')),
                    array('slug' => 'performance-toolkit-browser-cache', 'label' => __('Browser Cache', 'performance-toolkit')),
                    array('slug' => 'performance-toolkit-cdn-integrations', 'label' => __('CDN', 'performance-toolkit')),
                    array('slug' => 'performance-toolkit-advanced-rules', 'label' => __('Rules', 'performance-toolkit')),
                ),
                $current_page_slug
            );
        }

        if ($current_section === 'optimization') {
            return self::withSecondaryState(
                'optimization',
                array(
                    array('slug' => 'performance-toolkit-file-optimization', 'label' => __('Files', 'performance-toolkit')),
                    array('slug' => 'performance-toolkit-media-optimization', 'label' => __('Media', 'performance-toolkit')),
                ),
                $current_page_slug
            );
        }

        if ($current_section === 'system-status') {
            $base = add_query_arg('section', 'system-status', admin_url('admin.php?page=performance-toolkit'));

            return array(
                array('label' => __('Summary', 'performance-toolkit'), 'url' => $base . '#summary', 'active' => true),
                array('label' => __('Server', 'performance-toolkit'), 'url' => $base . '#server', 'active' => false),
                array('label' => __('WordPress', 'performance-toolkit'), 'url' => $base . '#wordpress', 'active' => false),
                array('label' => __('Directories & Permissions', 'performance-toolkit'), 'url' => $base . '#directories', 'active' => false),
                array('label' => __('PHP Info', 'performance-toolkit'), 'url' => $base . '#php-info', 'active' => false),
                array('label' => __('Database', 'performance-toolkit'), 'url' => $base . '#database', 'active' => false),
            );
        }

        return array();
    }

    /**
     * @param array<int, array{slug:string,label:string}> $items
     * @return array<int, array<string, mixed>>
     */
    private static function withSecondaryState(string $section, array $items, string $current_page_slug): array
    {
        $base_url = add_query_arg('section', $section, admin_url('admin.php?page=performance-toolkit'));

        foreach ($items as &$item) {
            $item['url'] = add_query_arg('tab', $item['slug'], $base_url);
            $item['active'] = $item['slug'] === $current_page_slug;
        }
        unset($item);

        return $items;
    }
}
